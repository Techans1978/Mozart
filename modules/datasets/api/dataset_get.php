<?php
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../../config.php';
require_once ROOT_PATH . '/system/config/connect.php';
require_once ROOT_PATH . '/system/config/autenticacao.php';

if (session_status()===PHP_SESSION_NONE) session_start();
proteger_pagina();

$id = intval($_GET['id'] ?? 0);
if ($id<=0) { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'id inválido']); exit; }

$st = $conn->prepare("SELECT * FROM moz_ds_dataset WHERE id=? LIMIT 1");
$st->bind_param("i",$id);
$st->execute();
$ds = $st->get_result()->fetch_assoc();
$st->close();
if (!$ds) { echo json_encode(['ok'=>false,'error'=>'dataset não encontrada']); exit; }

// draft
$st = $conn->prepare("SELECT * FROM moz_ds_version WHERE dataset_id=? AND type='draft' ORDER BY created_at DESC LIMIT 1");
$st->bind_param("i",$id);
$st->execute();
$draft = $st->get_result()->fetch_assoc();
$st->close();

// published last
$st = $conn->prepare("SELECT * FROM moz_ds_version WHERE dataset_id=? AND type='published' ORDER BY version_num DESC LIMIT 1");
$st->bind_param("i",$id);
$st->execute();
$pub = $st->get_result()->fetch_assoc();
$st->close();

echo json_encode(['ok'=>true,'dataset'=>$ds,'draft'=>$draft,'published'=>$pub], JSON_UNESCAPED_UNICODE);
