<?php
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../../config.php';
require_once ROOT_PATH . '/system/config/connect.php';
require_once ROOT_PATH . '/system/config/autenticacao.php';

if (session_status()===PHP_SESSION_NONE) session_start();
proteger_pagina();

$payload = json_decode(file_get_contents('php://input'), true);
$id = (int)($payload['id'] ?? 0);
if ($id<=0) { echo json_encode(['ok'=>false,'error'=>'id inválido']); exit; }

$st = $conn->prepare("SELECT status FROM moz_ds_dataset WHERE id=? LIMIT 1");
$st->bind_param("i",$id);
$st->execute();
$row = $st->get_result()->fetch_assoc();
$st->close();
if (!$row) { echo json_encode(['ok'=>false,'error'=>'dataset não encontrada']); exit; }

$new = ($row['status']==='active') ? 'inactive' : 'active';

$st = $conn->prepare("UPDATE moz_ds_dataset SET status=?, updated_at=NOW() WHERE id=?");
$st->bind_param("si",$new,$id);
$st->execute();
$st->close();

echo json_encode(['ok'=>true,'status'=>$new], JSON_UNESCAPED_UNICODE);
