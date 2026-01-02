<?php
header('Content-Type: application/json; charset=utf-8');
require_once dirname(__DIR__, 2) . '/config/connect.php'; // $conn

$code = isset($_GET['code']) ? preg_replace('/[^a-zA-Z0-9_\-]/', '_', $_GET['code']) : '';
if (!$code) { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'missing code']); exit; }

$stmt = $conn->prepare("SELECT id, code, name FROM bpm_process WHERE code=? LIMIT 1");
$stmt->bind_param("s", $code);
$stmt->execute();
$proc = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$proc) { echo json_encode(['ok'=>true,'versions'=>[]]); exit; }

$processId = (int)$proc['id'];

$stmt = $conn->prepare("SELECT id, version, semver, status, created_at, updated_at
                        FROM bpm_process_version
                        WHERE process_id=?
                        ORDER BY version DESC, id DESC");
$stmt->bind_param("i", $processId);
$stmt->execute();
$res = $stmt->get_result();

$versions = [];
while ($row = $res->fetch_assoc()) $versions[] = $row;
$stmt->close();

echo json_encode(['ok'=>true,'process'=>$proc,'versions'=>$versions]);
