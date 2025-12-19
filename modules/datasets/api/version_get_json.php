<?php
// modules/datasets/api/version_get_json.php
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../../config.php';
require_once ROOT_PATH . '/system/config/connect.php';
require_once ROOT_PATH . '/system/config/autenticacao.php';

if (session_status()===PHP_SESSION_NONE) session_start();
proteger_pagina();

$id = (int)($_GET['id'] ?? 0);
if ($id<=0) { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'id inválido']); exit; }

$st = $conn->prepare("SELECT id, dataset_id, type, version_num, checksum, notes, config_json, created_at, published_at FROM moz_ds_version WHERE id=? LIMIT 1");
$st->bind_param("i", $id);
$st->execute();
$item = $st->get_result()->fetch_assoc();
$st->close();

if (!$item) { echo json_encode(['ok'=>false,'error'=>'Versão não encontrada']); exit; }

echo json_encode(['ok'=>true,'item'=>$item], JSON_UNESCAPED_UNICODE);
