<?php
// modules/bpm/_lib/bpm_ext_runner.php
// Mozart BPM — FASE 7: runner de extensões (php/python/webhook) + log em bpm_extension_run

if (!function_exists('bpm_ext_parse_asset_ref')) {

    function bpm_ext_parse_asset_ref(string $ref): ?int {
        $ref = trim($ref);
        if ($ref === '') return null;
        if (preg_match('/^asset:(\d+)$/i', $ref, $m)) return (int)$m[1];
        if (ctype_digit($ref)) return (int)$ref; // tolerância
        return null;
    }

    function bpm_ext_now_mysql(): string {
        return date('Y-m-d H:i:s');
    }

    function bpm_ext_json($v): ?string {
        if ($v === null) return null;
        return json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    function bpm_ext_load_asset(mysqli $conn, int $processVersionId, int $assetId): array {
        $sql = "SELECT id, version_id, kind, path_or_name, blob_content, meta_json, hash_sha1, created_at
                FROM bpm_asset
                WHERE id = ? AND version_id = ?
                LIMIT 1";
        $st = $conn->prepare($sql);
        if (!$st) throw new Exception("Erro ao preparar select bpm_asset: ".$conn->error);
        $st->bind_param("ii", $assetId, $processVersionId);
        $st->execute();
        $res = $st->get_result()->fetch_assoc();
        $st->close();

        if (!$res) {
            throw new Exception("Asset {$assetId} não encontrado ou não pertence à versão {$processVersionId}");
        }

        $meta = [];
        if (!empty($res['meta_json'])) {
            $tmp = json_decode($res['meta_json'], true);
            if (is_array($tmp)) $meta = $tmp;
        }

        return [
            'id'          => (int)$res['id'],
            'version_id'  => (int)$res['version_id'],
            'kind'        => (string)$res['kind'],
            'path_or_name'=> (string)$res['path_or_name'],
            'blob'        => $res['blob_content'], // string binária
            'meta'        => $meta,
            'hash_sha1'   => (string)($res['hash_sha1'] ?? ''),
        ];
    }

    /**
     * Execução em processo separado com timeout simples.
     * Retorna [exit_code, stdout, stderr, timed_out]
     */
    function bpm_ext_proc_run(string $cmd, string $stdin = '', int $timeoutMs = 10000): array {
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $proc = proc_open($cmd, $descriptors, $pipes);
        if (!is_resource($proc)) {
            return [127, '', 'proc_open falhou', false];
        }

        // stdin
        fwrite($pipes[0], $stdin);
        fclose($pipes[0]);

        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);

        $stdout = '';
        $stderr = '';
        $timedOut = false;

        $start = microtime(true);
        while (true) {
            $status = proc_get_status($proc);
            $running = $status['running'] ?? false;

            $stdout .= stream_get_contents($pipes[1]);
            $stderr .= stream_get_contents($pipes[2]);

            if (!$running) break;

            $elapsedMs = (int)((microtime(true) - $start) * 1000);
            if ($elapsedMs > $timeoutMs) {
                $timedOut = true;
                // tenta terminar
                proc_terminate($proc, 9);
                break;
            }

            usleep(20000);
        }

        // final flush
        $stdout .= stream_get_contents($pipes[1]);
        $stderr .= stream_get_contents($pipes[2]);

        fclose($pipes[1]);
        fclose($pipes[2]);

        $exit = proc_close($proc);
        if ($timedOut) $exit = 124;

        return [$exit, $stdout, $stderr, $timedOut];
    }

    /**
     * Runner PHP:
     * - wrapper lê JSON no STDIN => $INPUT array
     * - inclui asset.php => deve retornar array $OUT (ou null)
     * - imprime JSON do retorno
     */
    function bpm_ext_run_php(array $asset, array $input, int $timeoutMs = 10000): array {
        $tmpDir = sys_get_temp_dir();
        $id = $asset['id'];

        $assetFile = $tmpDir . "/mozart_ext_asset_{$id}_" . uniqid() . ".php";
        $wrapFile  = $tmpDir . "/mozart_ext_wrap_{$id}_" . uniqid() . ".php";

        // grava código do asset
        file_put_contents($assetFile, $asset['blob'] ?? '');

        // wrapper
        $wrap = <<<PHP
<?php
\$raw = stream_get_contents(STDIN);
\$INPUT = json_decode(\$raw, true);
if (!is_array(\$INPUT)) \$INPUT = [];
\$OUT = include {$thisIsPhpString($assetFile)};
if (is_array(\$OUT)) {
  echo json_encode(['ok'=>true,'vars'=>(\$OUT['vars'] ?? []),'result'=>\$OUT], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
} else {
  echo json_encode(['ok'=>true,'vars'=>[],'result'=>\$OUT], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
}
PHP;

        // helper local: quote string php
        // (não pode ser function dentro heredoc, então montamos antes)
        PHP;

        // cria helper de quoting
        $qAsset = var_export($assetFile, true);
        $wrap = str_replace("{$thisIsPhpString($assetFile)}", $qAsset, $wrap);

        file_put_contents($wrapFile, $wrap);

        $cmd = "php " . escapeshellarg($wrapFile);
        [$exit, $stdout, $stderr, $timedOut] = bpm_ext_proc_run($cmd, bpm_ext_json($input) ?: '{}', $timeoutMs);

        @unlink($assetFile);
        @unlink($wrapFile);

        if ($exit !== 0) {
            return [
                'ok' => false,
                'error' => $timedOut ? 'timeout' : 'php exit != 0',
                'stdout' => $stdout,
                'stderr' => $stderr,
            ];
        }

        $out = json_decode($stdout, true);
        if (!is_array($out)) {
            return [
                'ok' => false,
                'error' => 'php stdout não é JSON válido',
                'stdout' => $stdout,
                'stderr' => $stderr,
            ];
        }

        return [
            'ok' => !empty($out['ok']),
            'vars' => (isset($out['vars']) && is_array($out['vars'])) ? $out['vars'] : [],
            'stdout' => $stdout,
            'stderr' => $stderr,
            'raw' => $out,
        ];
    }

    /**
     * Runner Python:
     * - script do asset deve ler STDIN (JSON) e imprimir JSON em stdout
     */
    function bpm_ext_run_python(array $asset, array $input, int $timeoutMs = 15000): array {
        $tmpDir = sys_get_temp_dir();
        $id = $asset['id'];

        $pyFile = $tmpDir . "/mozart_ext_py_{$id}_" . uniqid() . ".py";
        file_put_contents($pyFile, $asset['blob'] ?? '');

        $cmd = "python3 " . escapeshellarg($pyFile);
        [$exit, $stdout, $stderr, $timedOut] = bpm_ext_proc_run($cmd, bpm_ext_json($input) ?: '{}', $timeoutMs);

        @unlink($pyFile);

        if ($exit !== 0) {
            return [
                'ok' => false,
                'error' => $timedOut ? 'timeout' : 'python exit != 0',
                'stdout' => $stdout,
                'stderr' => $stderr,
            ];
        }

        $out = json_decode($stdout, true);
        if (!is_array($out)) {
            return [
                'ok' => false,
                'error' => 'python stdout não é JSON válido',
                'stdout' => $stdout,
                'stderr' => $stderr,
            ];
        }

        return [
            'ok' => !empty($out['ok']),
            'vars' => (isset($out['vars']) && is_array($out['vars'])) ? $out['vars'] : [],
            'stdout' => $stdout,
            'stderr' => $stderr,
            'raw' => $out,
        ];
    }

    function bpm_ext_run_webhook(array $asset, array $input, array $impl = []): array {
        $meta = $asset['meta'] ?? [];
        $url = (string)($meta['url'] ?? '');
        if ($url === '') return ['ok'=>false,'error'=>'webhook sem url em meta_json', 'stdout'=>'', 'stderr'=>''];

        $method = strtoupper((string)($impl['method'] ?? ($meta['method'] ?? 'POST')));
        if (!in_array($method, ['POST','PUT','PATCH','GET'], true)) $method = 'POST';

        $headers = $meta['headers'] ?? [];
        if (!is_array($headers)) $headers = [];

        $payload = bpm_ext_json($input) ?: '{}';

        $respBody = '';
        $respErr  = '';

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

            if ($method !== 'GET') {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
                $headers['Content-Type'] = $headers['Content-Type'] ?? 'application/json';
            }

            $hdrList = [];
            foreach ($headers as $k => $v) $hdrList[] = $k . ': ' . $v;
            if ($hdrList) curl_setopt($ch, CURLOPT_HTTPHEADER, $hdrList);

            $respBody = curl_exec($ch);
            if ($respBody === false) $respErr = curl_error($ch);
            $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($respErr !== '' || $httpCode >= 400) {
                return ['ok'=>false,'error'=>"webhook http {$httpCode}", 'stdout'=>(string)$respBody, 'stderr'=>(string)$respErr];
            }

        } else {
            $ctx = [
                'http' => [
                    'method' => $method,
                    'header' => '',
                    'content' => ($method !== 'GET') ? $payload : null,
                    'ignore_errors' => true,
                ]
            ];
            $hdr = '';
            foreach ($headers as $k => $v) $hdr .= $k . ': ' . $v . "\r\n";
            if ($method !== 'GET') $hdr .= "Content-Type: application/json\r\n";
            $ctx['http']['header'] = $hdr;

            $context = stream_context_create($ctx);
            $respBody = @file_get_contents($url, false, $context);
            if ($respBody === false) {
                $respErr = 'file_get_contents falhou';
                return ['ok'=>false,'error'=>'webhook falhou', 'stdout'=>'', 'stderr'=>$respErr];
            }
        }

        $out = json_decode((string)$respBody, true);
        if (!is_array($out)) {
            // webhook pode retornar texto; aceitamos ok=true sem vars
            return ['ok'=>true,'vars'=>[], 'stdout'=>(string)$respBody, 'stderr'=>''];
        }

        return [
            'ok' => !empty($out['ok']) || true,
            'vars' => (isset($out['vars']) && is_array($out['vars'])) ? $out['vars'] : [],
            'stdout' => (string)$respBody,
            'stderr' => '',
            'raw' => $out
        ];
    }

    function bpm_ext_log_run(
        mysqli $conn,
        int $instanceId,
        ?int $tokenId,
        string $nodeId,
        int $processVersionId,
        int $assetId,
        string $extType,
        string $status,
        string $startedAt,
        string $finishedAt,
        int $durationMs,
        array $input,
        $output,
        string $stdout = '',
        string $stderr = '',
        ?string $errorMessage = null,
        ?int $createdBy = null
    ): void {
        $sql = "INSERT INTO bpm_extension_run
                (instance_id, token_id, node_id, process_version_id, asset_id, ext_type, status,
                 started_at, finished_at, duration_ms,
                 input_json, output_json, stdout, stderr, error_message, created_by)
                VALUES
                (?, ?, ?, ?, ?, ?, ?,
                 ?, ?, ?,
                 ?, ?, ?, ?, ?, ?)";

        $st = $conn->prepare($sql);
        if (!$st) throw new Exception("Erro ao preparar insert bpm_extension_run: ".$conn->error);

        $inJson  = bpm_ext_json($input);
        $outJson = bpm_ext_json($output);

        $st->bind_param(
            "iisiissssisssssi",
            $instanceId,
            $tokenId,
            $nodeId,
            $processVersionId,
            $assetId,
            $extType,
            $status,
            $startedAt,
            $finishedAt,
            $durationMs,
            $inJson,
            $outJson,
            $stdout,
            $stderr,
            $errorMessage,
            $createdBy
        );
        $st->execute();
        $st->close();
    }

    /**
     * Resolve impl:
     * 1) tenta bpm_process_version_element.config_json
     * 2) fallback mozart:config do BPMN (target['mozart'])
     */
    function bpm_ext_resolve_impl(mysqli $conn, int $processVersionId, string $nodeId, array $targetMozartCfg = []): ?array {
        // 1) Banco (preferido)
        $sql = "SELECT config_json
                FROM bpm_process_version_element
                WHERE process_version_id = ? AND element_id = ?
                LIMIT 1";
        $st = $conn->prepare($sql);
        if ($st) {
            $st->bind_param("is", $processVersionId, $nodeId);
            $st->execute();
            $st->bind_result($cfgJson);
            if ($st->fetch() && $cfgJson) {
                $st->close();
                $cfg = json_decode($cfgJson, true);
                if (is_array($cfg)) {
                    if (isset($cfg['impl']) && is_array($cfg['impl'])) return $cfg['impl'];
                    if (isset($cfg['exec_kind']) && $cfg['exec_kind'] === 'service' && isset($cfg['ref'])) return $cfg; // tolerância
                }
            } else {
                $st->close();
            }
        }

        // 2) XML mozart:config
        if (!empty($targetMozartCfg)) {
            if (isset($targetMozartCfg['impl']) && is_array($targetMozartCfg['impl'])) return $targetMozartCfg['impl'];
        }

        return null;
    }

}
