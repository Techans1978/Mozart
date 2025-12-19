<?php
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../../config.php';
require_once ROOT_PATH . '/system/config/connect.php';
require_once ROOT_PATH . '/system/config/autenticacao.php';

if (session_status()===PHP_SESSION_NONE) session_start();
proteger_pagina();

$payload = json_decode(file_get_contents('php://input'), true);
if (json_last_error()!==JSON_ERROR_NONE) { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'JSON inválido']); exit; }

$id = (int)($payload['id'] ?? 0);
$notes = (string)($payload['notes'] ?? '');
if ($id<=0) { echo json_encode(['ok'=>false,'error'=>'id inválido']); exit; }

// pega draft mais recente
$st = $conn->prepare("SELECT * FROM moz_ds_version WHERE dataset_id=? AND type='draft' ORDER BY created_at DESC LIMIT 1");
$st->bind_param("i",$id);
$st->execute();
$draft = $st->get_result()->fetch_assoc();
$st->close();

if (!$draft) { echo json_encode(['ok'=>false,'error'=>'Sem draft para publicar']); exit; }

// versão num
$st = $conn->prepare("SELECT COALESCE(MAX(version_num),0) AS m FROM moz_ds_version WHERE dataset_id=? AND type='published'");
$st->bind_param("i",$id);
$st->execute();
$m = (int)($st->get_result()->fetch_assoc()['m'] ?? 0);
$st->close();
$newVer = $m + 1;

// cria published
$st = $conn->prepare("INSERT INTO moz_ds_version (dataset_id,type,version_num,checksum,notes,config_json,created_at,published_at) VALUES (?, 'published', ?, ?, ?, ?, NOW(), NOW())");
$st->bind_param("iisss", $id, $newVer, $draft['checksum'], $notes, $draft['config_json']);
$st->execute();
$pub_id = (int)$conn->insert_id;
$st->close();

echo json_encode(['ok'=>true,'published_id'=>$pub_id,'version_num'=>$newVer], JSON_UNESCAPED_UNICODE);
