<?php
// public/modules/forms/runtime/api/submit.php
ini_set('display_errors',1); ini_set('display_startup_errors',1); error_reporting(E_ALL);

require_once __DIR__ . '/../../../../config.php';
require_once ROOT_PATH . '/system/config/connect.php';
require_once ROOT_PATH . '/system/config/autenticacao.php';

if (!isset($conn) && isset($mysqli)) $conn = $mysqli;
if (!($conn instanceof mysqli)) { http_response_code(500); die('Sem DB'); }

if (session_status() !== PHP_SESSION_ACTIVE) session_start();
proteger_pagina();
$conn->set_charset('utf8mb4');

function j($arr, int $code=200){
  http_response_code($code);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode($arr, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
  exit;
}

$raw = file_get_contents('php://input');
$in = json_decode($raw,true);
if (!is_array($in)) j(['ok'=>false,'error'=>'invalid json'],400);

$formCode = trim((string)($in['form_code'] ?? ''));
$version  = (int)($in['version'] ?? 0);
$payload  = $in['payload'] ?? null;
$meta     = $in['meta'] ?? null;

if ($formCode==='' || $version<=0 || !is_array($payload)) {
  j(['ok'=>false,'error'=>'missing fields'],400);
}

$stmt=$conn->prepare("SELECT id, code FROM forms_form WHERE code=? LIMIT 1");
$stmt->bind_param("s",$formCode);
$stmt->execute();
$form=$stmt->get_result()->fetch_assoc();
$stmt->close();
if(!$form) j(['ok'=>false,'error'=>'form not found'],404);

$formId = (int)$form['id'];

// valida se a versão existe (e preferencialmente published)
$stmt=$conn->prepare("SELECT id, status FROM forms_form_version WHERE form_id=? AND version=? LIMIT 1");
$stmt->bind_param("ii",$formId,$version);
$stmt->execute();
$vrow=$stmt->get_result()->fetch_assoc();
$stmt->close();
if(!$vrow) j(['ok'=>false,'error'=>'version not found'],404);

// usuário (ajuste conforme sua sessão)
$userId = (int)($_SESSION['user_id'] ?? 0);
$meta2 = is_array($meta) ? $meta : [];
$meta2['ip'] = $_SERVER['REMOTE_ADDR'] ?? null;
$meta2['ua'] = $_SERVER['HTTP_USER_AGENT'] ?? null;

$payloadJson = json_encode($payload, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
$metaJson    = json_encode($meta2, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);

if (!$payloadJson) j(['ok'=>false,'error'=>'payload json failed'],500);

$stmt=$conn->prepare("INSERT INTO forms_form_submission (form_id, form_code, version, status, payload_json, meta_json, created_by)
                      VALUES (?,?,?,'new',CAST(? AS JSON),CAST(? AS JSON),?)");
$stmt->bind_param("isissi",$formId,$formCode,$version,$payloadJson,$metaJson,$userId);
$ok=$stmt->execute();
$insId = (int)$stmt->insert_id;
$err = $stmt->error;
$stmt->close();

if(!$ok) j(['ok'=>false,'error'=>'db insert failed','detail'=>$err],500);

j(['ok'=>true,'id'=>$insId]);
