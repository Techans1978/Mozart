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

$id = (int)($payload['id'] ?? 0);
if ($id<=0){ echo json_encode(['ok'=>false,'error'=>'id inválido']); exit; }

$st = $conn->prepare("SELECT tc.*, ds.dataset_key FROM moz_ds_testcase tc JOIN moz_ds_dataset ds ON ds.id=tc.dataset_id WHERE tc.id=? LIMIT 1");
$st->bind_param("i",$id);
$st->execute();
$tc = $st->get_result()->fetch_assoc();
$st->close();
if (!$tc){ echo json_encode(['ok'=>false,'error'=>'testcase não encontrado']); exit; }

$params = json_decode($tc['params_json'] ?? '{}', true);
if (!is_array($params)) $params = [];

$call = [
  'dataset_key' => $tc['dataset_key'],
  'params'      => $params,
  'caller'      => 'ui:testcase#'.$id
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, BASE_URL . '/modules/datasets/api/run.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json; charset=utf-8']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($call, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
$resp = curl_exec($ch);
$err = curl_error($ch);
$code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($resp === false){
  echo json_encode(['ok'=>false,'error'=>'curl: '.$err]);
  exit;
}

$out = json_decode($resp, true);
if (json_last_error()!==JSON_ERROR_NONE){
  echo json_encode(['ok'=>false,'error'=>'Resposta inválida do run.php','raw'=>$resp,'http'=>$code]);
  exit;
}

echo json_encode([
  'ok'=>true,
  'testcase_id'=>$id,
  'dataset_key'=>$tc['dataset_key'],
  'result'=>$out
], JSON_UNESCAPED_UNICODE);
