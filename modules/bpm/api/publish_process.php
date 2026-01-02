<?php
header('Content-Type: application/json; charset=utf-8');
require_once dirname(__DIR__, 2) . '/config/connect.php'; // $conn

$in = json_decode(file_get_contents('php://input'), true);
$code = isset($in['code']) ? preg_replace('/[^a-zA-Z0-9_\-]/', '_', $in['code']) : '';

if (!$code) { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'missing code']); exit; }

$conn->begin_transaction();

try {
  $stmt = $conn->prepare("SELECT id, current_version, current_version_id FROM bpm_process WHERE code=? LIMIT 1");
  $stmt->bind_param("s", $code);
  $stmt->execute();
  $proc = $stmt->get_result()->fetch_assoc();
  $stmt->close();

  if (!$proc) { throw new Exception("process not found"); }

  $processId = (int)$proc['id'];
  $curVer    = max(1, (int)$proc['current_version']);
  $curVerId  = (int)($proc['current_version_id'] ?? 0);

  // garante versionId (se não tiver current_version_id, tenta achar draft)
  if (!$curVerId) {
    $stmt = $conn->prepare("SELECT id FROM bpm_process_version WHERE process_id=? AND version=? ORDER BY id DESC LIMIT 1");
    $stmt->bind_param("ii", $processId, $curVer);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$row) throw new Exception("current version row not found");
    $curVerId = (int)$row['id'];
  }

  // publica a versão atual
  $stmt = $conn->prepare("UPDATE bpm_process_version SET status='published', updated_at=NOW() WHERE id=?");
  $stmt->bind_param("i", $curVerId);
  $stmt->execute();
  $stmt->close();

  // marca processo como published
  $stmt = $conn->prepare("UPDATE bpm_process SET status='published', updated_at=NOW() WHERE id=?");
  $stmt->bind_param("i", $processId);
  $stmt->execute();
  $stmt->close();

  // cria próximo draft (curVer+1) copiando XML publicado
  $stmt = $conn->prepare("SELECT bpmn_xml FROM bpm_process_version WHERE id=? LIMIT 1");
  $stmt->bind_param("i", $curVerId);
  $stmt->execute();
  $xml = $stmt->get_result()->fetch_assoc()['bpmn_xml'] ?? '';
  $stmt->close();

  $nextVer = $curVer + 1;
  $semver  = $nextVer . ".0.0";

  $stmt = $conn->prepare("INSERT INTO bpm_process_version (process_id, version, semver, status, bpmn_xml, snapshot_json)
                          VALUES (?, ?, ?, 'draft', ?, NULL)");
  $stmt->bind_param("iiss", $processId, $nextVer, $semver, $xml);
  $stmt->execute();
  $nextVerId = (int)$stmt->insert_id;
  $stmt->close();

  $stmt = $conn->prepare("UPDATE bpm_process
                          SET current_version=?, current_version_id=?, updated_at=NOW()
                          WHERE id=?");
  $stmt->bind_param("iii", $nextVer, $nextVerId, $processId);
  $stmt->execute();
  $stmt->close();

  $conn->commit();
  echo json_encode(['ok'=>true,'published_version'=>$curVer,'next_draft_version'=>$nextVer]);
} catch (Throwable $e) {
  $conn->rollback();
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}
