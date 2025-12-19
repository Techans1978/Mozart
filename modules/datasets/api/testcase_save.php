<?php
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../../config.php';
require_once ROOT_PATH . '/system/config/connect.php';
require_once ROOT_PATH . '/system/config/autenticacao.php';

if (session_status()===PHP_SESSION_NONE) session_start();
proteger_pagina();

$payload = json_decode(file_get_contents('php://input'), true);
if (json_last_error()!==JSON_ERROR_NONE){ http_response_code(400); echo json_encode(['ok'=>false,'error'=>'JSON inválido']); exit; }

$dataset_id = (int)($payload['dataset_id'] ?? 0);
$name = trim($payload['name'] ?? '');
$params = $payload['params'] ?? [];
$expected = $payload['expected'] ?? null;

if ($dataset_id<=0 || $name===''){ echo json_encode(['ok'=>false,'error'=>'dataset_id e name obrigatórios']); exit; }
if (!is_array($params)) { echo json_encode(['ok'=>false,'error'=>'params deve ser objeto']); exit; }

$params_json = json_encode($params, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
$expected_json = json_encode($expected ?? new stdClass(), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);

$st = $conn->prepare("INSERT INTO moz_ds_testcase (dataset_id,name,params_json,expected_json,is_active,created_at,updated_at) VALUES (?,?,?,?,1,NOW(),NOW())");
$st->bind_param("isss", $dataset_id, $name, $params_json, $expected_json);
$st->execute();
$id = (int)$conn->insert_id;
$st->close();

echo json_encode(['ok'=>true,'id'=>$id], JSON_UNESCAPED_UNICODE);
