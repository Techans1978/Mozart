<?php
// modules/forms/actions/submissions_reprocess.php
ini_set('display_errors',1); ini_set('display_startup_errors',1); error_reporting(E_ALL);

require_once __DIR__ . '/../../../config.php';
require_once ROOT_PATH . '/system/config/autenticacao.php';
require_once ROOT_PATH . '/system/config/connect.php';
require_once ROOT_PATH . '/modules/forms/lib/audit.php';

if (!isset($conn) && isset($mysqli)) $conn = $mysqli;
if (!($conn instanceof mysqli)) die('Conexão MySQLi $conn não encontrada.');
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
proteger_pagina();
$conn->set_charset('utf8mb4');

function flash($m){ $_SESSION['__flash']=['m'=>$m]; }
function back(){
  $u = $_SERVER['HTTP_REFERER'] ?? (BASE_URL.'/public/modules/forms/submissions_listar.php');
  header('Location: '.$u);
  exit;
}

$userId = (int)($_SESSION['user_id'] ?? 0);
$id = (int)($_POST['id'] ?? 0);
if ($id<=0){ flash('ID inválido.'); back(); }

// submission
$stmt=$conn->prepare("SELECT * FROM forms_form_submission WHERE id=? LIMIT 1");
$stmt->bind_param("i",$id);
$stmt->execute();
$s=$stmt->get_result()->fetch_assoc();
$stmt->close();
if(!$s){ flash('Submissão não encontrada.'); back(); }

$formCode = (string)$s['form_code'];

// hook
$stmt=$conn->prepare("SELECT * FROM forms_reprocess_hook WHERE form_code=? AND ativo=1 LIMIT 1");
$stmt->bind_param("s",$formCode);
$stmt->execute();
$hook=$stmt->get_result()->fetch_assoc();
$stmt->close();
if(!$hook){ flash("Sem hook configurado para {$formCode}."); back(); }

$endpoint = trim((string)$hook['endpoint']);
$method   = strtoupper(trim((string)$hook['method'] ?: 'POST'));
$secret   = (string)($hook['secret'] ?? '');

$isInternal = (strpos($endpoint,'://')===false && str_starts_with($endpoint,'/'))
           || (strpos($endpoint, BASE_URL)===0);
if(!$isInternal){
  flash('Endpoint não permitido (somente interno).');
  back();
}

$url = $endpoint;
if (str_starts_with($url,'/')) $url = BASE_URL.$url;

$payload = json_decode($s['payload_json'] ?? '{}', true);
$meta    = json_decode($s['meta_json'] ?? '{}', true);

$bodyArr = [
  'submission_id' => (int)$s['id'],
  'form_code'     => $formCode,
  'version'       => (int)$s['version'],
  'payload'       => is_array($payload)?$payload:[],
  'meta'          => is_array($meta)?$meta:[],
];

$bodyJson = json_encode($bodyArr, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
if(!$bodyJson){ flash('Falha ao montar JSON.'); back(); }

// assinatura opcional (HMAC)
$headers = ['Content-Type: application/json'];
if ($secret !== '') {
  $sig = hash_hmac('sha256', $bodyJson, $secret);
  $headers[] = 'X-Forms-Signature: '.$sig;
}

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

if ($method === 'POST') {
  curl_setopt($ch, CURLOPT_POST, true);
  curl_setopt($ch, CURLOPT_POSTFIELDS, $bodyJson);
} else {
  // fallback seguro: só POST mesmo
  curl_setopt($ch, CURLOPT_POST, true);
  curl_setopt($ch, CURLOPT_POSTFIELDS, $bodyJson);
}

$resp = curl_exec($ch);
$http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
$err  = curl_error($ch);
curl_close($ch);

if ($resp === false || $http<200 || $http>=300) {
  $detail = $resp ?: $err ?: ('HTTP '.$http);

  // marca erro
  $st='error';
  $stmt=$conn->prepare("UPDATE forms_form_submission SET status=? WHERE id=? LIMIT 1");
  $stmt->bind_param("si",$st,$id);
  $stmt->execute(); $stmt->close();

  forms_audit($conn, [
    'form_id' => (int)$s['form_id'],
    'form_code' => $formCode,
    'action' => 'reprocess_error',
    'details' => ['submission_id'=>$id,'endpoint'=>$endpoint,'http'=>$http,'detail'=>$detail],
    'user_id' => $userId,
  ]);

  flash('Falhou reprocessar: '.$detail);
  back();
}

// sucesso: marca processed
$st='processed';
$stmt=$conn->prepare("UPDATE forms_form_submission SET status=? WHERE id=? LIMIT 1");
$stmt->bind_param("si",$st,$id);
$stmt->execute(); $stmt->close();

forms_audit($conn, [
  'form_id' => (int)$s['form_id'],
  'form_code' => $formCode,
  'action' => 'reprocess_ok',
  'details' => ['submission_id'=>$id,'endpoint'=>$endpoint,'http'=>$http],
  'user_id' => $userId,
]);

flash('Reprocessado com sucesso.');
back();
