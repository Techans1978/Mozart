<?php
// modules/datasets/api/run.php
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../../config.php';
require_once ROOT_PATH . '/system/config/connect.php';
require_once ROOT_PATH . '/system/config/autenticacao.php';
require_once __DIR__ . '/../lib/DsEngine.php';
require_once __DIR__ . '/../lib/JsonUtil.php';

if (session_status()===PHP_SESSION_NONE) session_start();
proteger_pagina();

$raw = file_get_contents('php://input');
$payload = json_decode($raw, true);
if (json_last_error() !== JSON_ERROR_NONE) {
  http_response_code(400);
  echo json_encode(['ok'=>false,'error'=>'JSON inválido']);
  exit;
}

$dataset_key = trim($payload['dataset_key'] ?? '');
$params = $payload['params'] ?? [];
$options = $payload['options'] ?? [];
$caller = $payload['caller'] ?? null;

if ($dataset_key === '') {
  http_response_code(400);
  echo json_encode(['ok'=>false,'error'=>'dataset_key obrigatório']);
  exit;
}
if (!is_array($params)) $params = [];

$started = microtime(true);

try {
  // 1) resolve dataset
  $st = $conn->prepare("SELECT * FROM moz_ds_dataset WHERE dataset_key=? LIMIT 1");
  $st->bind_param("s", $dataset_key);
  $st->execute();
  $ds = $st->get_result()->fetch_assoc();
  $st->close();

  if (!$ds) throw new Exception("Dataset não encontrada");
  if (($ds['status'] ?? '') !== 'active') throw new Exception("Dataset inativa");

  // 2) pegar versão publicada (fallback draft se não houver)
  $st = $conn->prepare("
    SELECT * FROM moz_ds_version
    WHERE dataset_id=? AND type='published'
    ORDER BY version_num DESC LIMIT 1
  ");
  $st->bind_param("i", $ds['id']);
  $st->execute();
  $ver = $st->get_result()->fetch_assoc();
  $st->close();

  if (!$ver) {
    // fallback draft (admin-only no futuro; por enquanto deixa pra dev)
    $st = $conn->prepare("
      SELECT * FROM moz_ds_version
      WHERE dataset_id=? AND type='draft'
      ORDER BY created_at DESC LIMIT 1
    ");
    $st->bind_param("i", $ds['id']);
    $st->execute();
    $ver = $st->get_result()->fetch_assoc();
    $st->close();
  }
  if (!$ver) throw new Exception("Dataset sem versão (draft/published)");

  $config = JsonUtil::decode($ver['config_json'], null);
  if (!$config) throw new Exception("config_json inválido");

  // 3) executar
  $result = DsEngine::run($config, $params);
  $rows = $result['rows'] ?? [];
  $meta = $result['meta'] ?? [];

  $exec_ms = (int)round((microtime(true)-$started)*1000);

  // 4) log
  $rowsCount = is_array($rows) ? count($rows) : 0;
  $paramsJson = JsonUtil::encode($params);

  $st = $conn->prepare("
    INSERT INTO moz_ds_exec_log
      (dataset_id, dataset_key, version_id, exec_ms, is_cached, status, error_msg, params_json, result_rows_count, caller, created_at)
    VALUES (?, ?, ?, ?, 0, 'ok', NULL, ?, ?, ?, NOW())
  ");
  $callerStr = $caller ? (string)$caller : null;
  $st->bind_param("isiisisis", $ds['id'], $dataset_key, $ver['id'], $exec_ms, $paramsJson, $rowsCount, $callerStr);
  // bind_param acima: tipos precisam bater com null; se der bronca no seu PHP, trocamos pra strings simples.
  @$st->execute();
  @$st->close();

  echo json_encode([
    'ok' => true,
    'dataset_key' => $dataset_key,
    'dataset_id' => (int)$ds['id'],
    'version_id' => (int)$ver['id'],
    'version_num' => (int)($ver['version_num'] ?? 0),
    'exec_ms' => $exec_ms,
    'rows' => $rows,
    'meta' => $meta
  ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
  $exec_ms = (int)round((microtime(true)-$started)*1000);
  // tenta logar erro
  try {
    $paramsJson = JsonUtil::encode($params);
    $st = $conn->prepare("
      INSERT INTO moz_ds_exec_log
        (dataset_id, dataset_key, version_id, exec_ms, is_cached, status, error_msg, params_json, result_rows_count, caller, created_at)
      VALUES (NULL, ?, NULL, ?, 0, 'error', ?, ?, NULL, ?, NOW())
    ");
    $callerStr = $caller ? (string)$caller : null;
    $err = $e->getMessage();
    $st->bind_param("sisss", $dataset_key, $exec_ms, $err, $paramsJson, $callerStr);
    @$st->execute();
    @$st->close();
  } catch(Throwable $x){}

  echo json_encode(['ok'=>false,'error'=>$e->getMessage(), 'exec_ms'=>$exec_ms], JSON_UNESCAPED_UNICODE);
}
