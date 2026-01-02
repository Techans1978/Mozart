<?php
header('Content-Type: application/json; charset=utf-8');
require_once dirname(__DIR__, 2) . '/config/connect.php'; // $conn

$in = json_decode(file_get_contents('php://input'), true);

$sourceCode = isset($in['source_code']) ? preg_replace('/[^a-zA-Z0-9_\-]/', '_', $in['source_code']) : '';
$newCode    = isset($in['new_code']) ? preg_replace('/[^a-zA-Z0-9_\-]/', '_', $in['new_code']) : '';
$newName    = isset($in['new_name']) ? trim($in['new_name']) : '';

if (!$sourceCode || !$newCode) {
  http_response_code(400);
  echo json_encode(['ok'=>false,'error'=>'missing source_code/new_code']); exit;
}
if (!$newName) $newName = $newCode;

$conn->begin_transaction();

try {
  // fonte
  $stmt = $conn->prepare("SELECT id, name, current_version, current_version_id FROM bpm_process WHERE code=? LIMIT 1");
  $stmt->bind_param("s", $sourceCode);
  $stmt->execute();
  $src = $stmt->get_result()->fetch_assoc();
  $stmt->close();
  if (!$src) throw new Exception("source process not found");

  $srcPid = (int)$src['id'];
  $srcVerId = (int)($src['current_version_id'] ?? 0);

  if (!$srcVerId) {
    $stmt = $conn->prepare("SELECT id FROM bpm_process_version WHERE process_id=? ORDER BY version DESC, id DESC LIMIT 1");
    $stmt->bind_param("i", $srcPid);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$row) throw new Exception("source version not found");
    $srcVerId = (int)$row['id'];
  }

  $stmt = $conn->prepare("SELECT bpmn_xml FROM bpm_process_version WHERE id=? LIMIT 1");
  $stmt->bind_param("i", $srcVerId);
  $stmt->execute();
  $xml = $stmt->get_result()->fetch_assoc()['bpmn_xml'] ?? '';
  $stmt->close();

  // garante que new_code não existe
  $stmt = $conn->prepare("SELECT id FROM bpm_process WHERE code=? LIMIT 1");
  $stmt->bind_param("s", $newCode);
  $stmt->execute();
  $exists = $stmt->get_result()->fetch_assoc();
  $stmt->close();
  if ($exists) throw new Exception("new_code already exists");

  // cria novo processo
  $stmt = $conn->prepare("INSERT INTO bpm_process (code, name, status, current_version) VALUES (?, ?, 'draft', 1)");
  $stmt->bind_param("ss", $newCode, $newName);
  $stmt->execute();
  $newPid = (int)$stmt->insert_id;
  $stmt->close();

  // versão 1 draft
  $stmt = $conn->prepare("INSERT INTO bpm_process_version (process_id, version, semver, status, bpmn_xml, snapshot_json)
                          VALUES (?, 1, '1.0.0', 'draft', ?, NULL)");
  $stmt->bind_param("is", $newPid, $xml);
  $stmt->execute();
  $newVerId = (int)$stmt->insert_id;
  $stmt->close();

  $stmt = $conn->prepare("UPDATE bpm_process SET current_version_id=? WHERE id=?");
  $stmt->bind_param("ii", $newVerId, $newPid);
  $stmt->execute();
  $stmt->close();

  $conn->commit();
  echo json_encode(['ok'=>true,'new_process'=>['id'=>$newPid,'code'=>$newCode,'name'=>$newName]]);
} catch (Throwable $e) {
  $conn->rollback();
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}
