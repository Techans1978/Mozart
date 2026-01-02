<?php
header('Content-Type: application/json; charset=utf-8');
require_once dirname(__DIR__, 2) . '/config/connect.php'; // $conn

$in = json_decode(file_get_contents('php://input'), true);

$sourceId = isset($in['source_process_id']) ? (int)$in['source_process_id'] : 0;
$newCode  = isset($in['code']) ? preg_replace('/[^a-zA-Z0-9_\-]/', '_', $in['code']) : '';
$newName  = isset($in['name']) ? trim($in['name']) : '';

if (!$sourceId || !$newCode || !$newName) {
  http_response_code(400);
  echo json_encode(['error' => 'source_process_id, code, name são obrigatórios']);
  exit;
}

try {
  $conn->begin_transaction();

  // pega processo + versão atual
  $st = $conn->prepare("
    SELECT p.id, p.current_version_id, v.bpmn_xml, v.snapshot_json
      FROM bpm_process p
      JOIN bpm_process_version v ON v.id = p.current_version_id
     WHERE p.id = ?
     LIMIT 1
  ");
  $st->bind_param("i", $sourceId);
  $st->execute();
  $rs = $st->get_result();
  $src = $rs->fetch_assoc();
  $st->close();

  if (!$src) throw new Exception('processo origem não encontrado ou sem versão atual');

  // cria novo processo (draft)
  $status = 'draft';
  $st = $conn->prepare("INSERT INTO bpm_process (code, name, status, current_version) VALUES (?, ?, ?, 0)");
  $st->bind_param("sss", $newCode, $newName, $status);
  $st->execute();
  $newProcessId = (int)$conn->insert_id;
  $st->close();

  // cria versão 1 copiando xml/snapshot
  $version = 1;
  $xml = $src['bpmn_xml'];
  $snap = $src['snapshot_json'];

  $st = $conn->prepare("
    INSERT INTO bpm_process_version (process_id, version, status, bpmn_xml, snapshot_json)
    VALUES (?, ?, ?, ?, ?)
  ");
  $st->bind_param("iisss", $newProcessId, $version, $status, $xml, $snap);
  $st->execute();
  $newVersionId = (int)$conn->insert_id;
  $st->close();

  // aponta processo para versão 1
  $st = $conn->prepare("
    UPDATE bpm_process
       SET current_version = 1,
           current_version_id = ?,
           updated_at = NOW()
     WHERE id = ?
  ");
  $st->bind_param("ii", $newVersionId, $newProcessId);
  $st->execute();
  $st->close();

  $conn->commit();

  echo json_encode([
    'ok' => true,
    'process_id' => $newProcessId,
    'version' => 1,
    'version_id' => $newVersionId
  ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
  if ($conn) { @$conn->rollback(); }
  http_response_code(500);
  echo json_encode(['error' => $e->getMessage()]);
}
