<?php
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__, 3).'/config.php';
require_once ROOT_PATH.'/system/config/autenticacao.php';
require_once ROOT_PATH.'/system/config/connect.php';
proteger_pagina();

// ===== helpers =====
function json_out($arr, $code=200){
  http_response_code($code);
  echo json_encode($arr, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
  exit;
}
function mozart_storage(): string {
  $dir = __DIR__ . '/../storage/processes';
  if (!is_dir($dir)) { @mkdir($dir, 0775, true); }
  return $dir;
}
function slug_code($s){
  $s = trim((string)$s);
  $s = preg_replace('/\s+/', '_', $s);
  $s = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $s);
  return substr($s, 0, 80);
}

$in = json_decode(file_get_contents('php://input'), true);
if (!is_array($in)) json_out(['error'=>'invalid json'], 400);

// payload esperado
$processId = (int)($in['process_id'] ?? 0);
$code      = slug_code($in['code'] ?? '');
$name      = trim((string)($in['name'] ?? ''));
$status    = (string)($in['status'] ?? 'draft'); // draft|published|archived
$xml       = (string)($in['xml'] ?? '');
$snapshot  = $in['snapshot'] ?? null;            // array|object (vira JSON)
$version   = (int)($in['version'] ?? 0);          // opcional: salvar numa versão específica

if ($status !== 'draft' && $status !== 'published' && $status !== 'archived') $status = 'draft';
if ($name === '' || $code === '' || $xml === '') {
  json_out(['error'=>'name, code and xml are required'], 400);
}

// ===== 1) backup em arquivo (sempre) =====
$dir = mozart_storage();
$file = $dir . '/' . $code . '_v' . ($version > 0 ? $version : 'X') . '.bpmn';
@file_put_contents($file, $xml);

// ===== 2) DB: cria/atualiza processo + cria/atualiza versão + atualiza ponteiro =====
$conn->begin_transaction();

/** 2) salva banco (MySQLi) — NOVO (bpm_process + bpm_process_version) **/
try {
  $in = json_decode(file_get_contents('php://input'), true);

  $processId = isset($in['process_id']) ? (int)$in['process_id'] : 0;
  $code = isset($in['code']) ? preg_replace('/[^a-zA-Z0-9_\-]/', '_', $in['code']) : '';
  $pname = isset($in['name']) ? trim($in['name']) : '';
  $xml = $in['xml'] ?? '';
  $status = (isset($in['status']) && in_array($in['status'], ['draft','published','archived'])) ? $in['status'] : 'draft';

  // snapshot pode vir como array ou string json
  $snapshotJson = null;
  if (isset($in['snapshot'])) {
    $snapshotJson = is_string($in['snapshot']) ? $in['snapshot'] : json_encode($in['snapshot'], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
  }

  if (!$xml) { throw new Exception('xml obrigatório'); }
  if (!$processId && !$code) { throw new Exception('process_id ou code obrigatório'); }
  if (!$pname) { $pname = $code ?: ('process_' . date('Ymd_His')); }

  $conn->begin_transaction();

  // 1) resolve process_id por code (se não veio)
  if (!$processId && $code) {
    $st = $conn->prepare("SELECT id FROM bpm_process WHERE code = ? LIMIT 1");
    $st->bind_param("s", $code);
    $st->execute();
    $rs = $st->get_result();
    if ($r = $rs->fetch_assoc()) $processId = (int)$r['id'];
    $st->close();
  }

  // 2) se não existe, cria bpm_process
  if (!$processId) {
    $st = $conn->prepare("INSERT INTO bpm_process (code, name, status, current_version) VALUES (?, ?, ?, 0)");
    $st->bind_param("sss", $code, $pname, $status);
    $st->execute();
    $processId = (int)$conn->insert_id;
    $st->close();
  } else {
    // garante que existe
    $st = $conn->prepare("SELECT id FROM bpm_process WHERE id = ? LIMIT 1");
    $st->bind_param("i", $processId);
    $st->execute();
    $rs = $st->get_result();
    $ok = (bool)$rs->fetch_assoc();
    $st->close();
    if (!$ok) throw new Exception('process_id não encontrado');
  }

  // 3) trava e calcula próxima versão
  $st = $conn->prepare("SELECT current_version FROM bpm_process WHERE id = ? FOR UPDATE");
  $st->bind_param("i", $processId);
  $st->execute();
  $rs = $st->get_result();
  $row = $rs->fetch_assoc();
  $st->close();

  $cur = (int)($row['current_version'] ?? 0);
  $nextVersion = $cur + 1;

  // 4) cria versão
  $st = $conn->prepare("
    INSERT INTO bpm_process_version (process_id, version, status, bpmn_xml, snapshot_json)
    VALUES (?, ?, ?, ?, ?)
  ");
  $st->bind_param("iisss", $processId, $nextVersion, $status, $xml, $snapshotJson);
  $st->execute();
  $versionId = (int)$conn->insert_id;
  $st->close();

  // 5) atualiza ponteiros do processo
  $st = $conn->prepare("
    UPDATE bpm_process
       SET code = COALESCE(NULLIF(?, ''), code),
           name = ?,
           status = ?,
           current_version = ?,
           current_version_id = ?,
           updated_at = NOW()
     WHERE id = ?
  ");
  $st->bind_param("sssiii", $code, $pname, $status, $nextVersion, $versionId, $processId);
  $st->execute();
  $st->close();

  $conn->commit();

  echo json_encode([
    'ok' => true,
    'process_id' => $processId,
    'version_id' => $versionId,
    'version' => $nextVersion,
    'status' => $status
  ], JSON_UNESCAPED_UNICODE);
  exit;

} catch (Throwable $e) {
  if ($conn && $conn->errno === 0) { /* noop */ }
  if ($conn) { @$conn->rollback(); }
  // não derruba o salvamento em arquivo (você já salvou antes), mas avisa
  echo json_encode(['ok' => true, 'warning' => 'db_save_failed', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
  exit;
}
