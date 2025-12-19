<?php
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../../config.php';
require_once ROOT_PATH . '/system/config/connect.php';
require_once ROOT_PATH . '/system/config/autenticacao.php';
require_once __DIR__ . '/../lib/JsonUtil.php';

if (session_status()===PHP_SESSION_NONE) session_start();
proteger_pagina();

$payload = json_decode(file_get_contents('php://input'), true);
if (json_last_error()!==JSON_ERROR_NONE) { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'JSON inválido']); exit; }

$id = (int)($payload['id'] ?? 0);
$dataset_key = trim($payload['dataset_key'] ?? '');
$name = trim($payload['name'] ?? '');
$description = (string)($payload['description'] ?? '');
$type = $payload['type'] ?? 'connector';
$tags = $payload['tags'] ?? [];
$cache_ttl_sec = (int)($payload['cache_ttl_sec'] ?? 0);

$config = $payload['config'] ?? null;
if (!$dataset_key || !$name) { echo json_encode(['ok'=>false,'error'=>'dataset_key e name obrigatórios']); exit; }
if (!in_array($type, ['script','connector'], true)) { echo json_encode(['ok'=>false,'error'=>'type inválido']); exit; }
if (!is_array($tags)) $tags=[];

$configJson = JsonUtil::encode($config ?? []);
$checksum = JsonUtil::checksum($configJson);
$tagsJson = JsonUtil::encode(array_values($tags));

$conn->begin_transaction();

try {
  if ($id <= 0) {
    $st = $conn->prepare("INSERT INTO moz_ds_dataset (dataset_key,name,description,type,status,tags_json,cache_ttl_sec,created_at,updated_at) VALUES (?,?,?,?,'active',?,?,NOW(),NOW())");
    $st->bind_param("sssssi", $dataset_key,$name,$description,$type,$tagsJson,$cache_ttl_sec);
    $st->execute();
    $id = (int)$conn->insert_id;
    $st->close();
  } else {
    $st = $conn->prepare("UPDATE moz_ds_dataset SET dataset_key=?, name=?, description=?, type=?, tags_json=?, cache_ttl_sec=?, updated_at=NOW() WHERE id=?");
    $st->bind_param("sssssii", $dataset_key,$name,$description,$type,$tagsJson,$cache_ttl_sec,$id);
    $st->execute();
    $st->close();
  }

  // inserir draft como versão (sempre cria um draft novo)
  $notes = (string)($payload['notes'] ?? '');
  $st = $conn->prepare("INSERT INTO moz_ds_version (dataset_id,type,version_num,checksum,notes,config_json,created_at) VALUES (?, 'draft', NULL, ?, ?, ?, NOW())");
  $st->bind_param("isss", $id, $checksum, $notes, $configJson);
  $st->execute();
  $draft_id = (int)$conn->insert_id;
  $st->close();

  $conn->commit();
  echo json_encode(['ok'=>true,'id'=>$id,'draft_id'=>$draft_id,'checksum'=>$checksum], JSON_UNESCAPED_UNICODE);

} catch(Throwable $e){
  $conn->rollback();
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}
