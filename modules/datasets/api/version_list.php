<?php
// modules/datasets/api/version_list.php
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../../config.php';
require_once ROOT_PATH . '/system/config/connect.php';
require_once ROOT_PATH . '/system/config/autenticacao.php';

if (session_status()===PHP_SESSION_NONE) session_start();
proteger_pagina();

$dataset_id = (int)($_GET['dataset_id'] ?? 0);
if ($dataset_id<=0) { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'dataset_id inválido']); exit; }

$sql = "
SELECT id, dataset_id, type, version_num, checksum, notes, created_at, published_at
FROM moz_ds_version
WHERE dataset_id = ?
ORDER BY
  CASE type WHEN 'published' THEN 1 ELSE 2 END,
  version_num DESC,
  created_at DESC
";
$st = $conn->prepare($sql);
$st->bind_param("i", $dataset_id);
$st->execute();
$res = $st->get_result();

$items = [];
while($r=$res->fetch_assoc()) $items[]=$r;
$st->close();

echo json_encode(['ok'=>true,'items'=>$items], JSON_UNESCAPED_UNICODE);
