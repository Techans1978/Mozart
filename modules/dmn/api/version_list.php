<?php
// modules/dmn/api/version_list.php
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../../config.php';
require_once ROOT_PATH . '/system/config/connect.php';
require_once ROOT_PATH . '/system/config/autenticacao.php';

if (session_status() === PHP_SESSION_NONE) session_start();
proteger_pagina();

$decisionId = intval($_GET['decision_id'] ?? 0);
if ($decisionId <= 0) {
  http_response_code(400);
  echo json_encode(['ok'=>false,'error'=>'decision_id inválido']);
  exit;
}

$sql = "
SELECT id, decision_id, type, version_num, checksum, notes,
       created_at, published_at
FROM moz_dmn_version
WHERE decision_id = ?
ORDER BY 
  CASE type WHEN 'published' THEN 1 ELSE 2 END,
  version_num DESC,
  created_at DESC";

$st = $conn->prepare($sql);
$st->bind_param("i", $decisionId);
$st->execute();
$res = $st->get_result();

$items = [];
while ($r = $res->fetch_assoc()) {
  $items[] = $r;
}
$st->close();

echo json_encode([
  'ok' => true,
  'items' => $items
], JSON_UNESCAPED_UNICODE);
