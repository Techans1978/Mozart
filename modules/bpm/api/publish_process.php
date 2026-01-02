<?php
header('Content-Type: application/json; charset=utf-8');
require_once dirname(__DIR__, 2) . '/config/connect.php'; // $conn

$in = json_decode(file_get_contents('php://input'), true);
$processId = isset($in['process_id']) ? (int)$in['process_id'] : 0;
$version = isset($in['version']) ? (int)$in['version'] : 0;

if (!$processId) {
  http_response_code(400);
  echo json_encode(['error' => 'process_id obrigatório']);
  exit;
}

try {
  $conn->begin_transaction();

  // se não veio versão, usa a atual do processo
  if ($version <= 0) {
    $st = $conn->prepare("SELECT current_version FROM bpm_process WHERE id = ? LIMIT 1");
    $st->bind_param("i", $processId);
    $st->execute();
    $rs = $st->get_result();
    $row = $rs->fetch_assoc();
    $st->close();
    $version = (int)($row['current_version'] ?? 0);
  }

  if ($version <= 0) throw new Exception('processo sem versão atual');

  // pega o id da versão
  $st = $conn->prepare("SELECT id FROM bpm_process_version WHERE process_id = ? AND version = ? LIMIT 1");
  $st->bind_param("ii", $processId, $version);
  $st->execute();
  $rs = $st->get_result();
  $vrow = $rs->fetch_assoc();
  $st->close();
  if (!$vrow) throw new Exception('versão não encontrada');

  $versionId = (int)$vrow['id'];

  // publica versão
  $st = $conn->prepare("UPDATE bpm_process_version SET status = 'published', updated_at = NOW() WHERE id = ?");
  $st->bind_param("i", $versionId);
  $st->execute();
  $st->close();

  // publica processo e aponta para a versão
  $st = $conn->prepare("
    UPDATE bpm_process
       SET status = 'published',
           current_version = ?,
           current_version_id = ?,
           updated_at = NOW()
     WHERE id = ?
  ");
  $st->bind_param("iii", $version, $versionId, $processId);
  $st->execute();
  $st->close();

  $conn->commit();

  echo json_encode(['ok' => true, 'process_id' => $processId, 'version' => $version, 'version_id' => $versionId]);
} catch (Throwable $e) {
  if ($conn) { @$conn->rollback(); }
  http_response_code(500);
  echo json_encode(['error' => $e->getMessage()]);
}
