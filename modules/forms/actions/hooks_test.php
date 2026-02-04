<?php
ini_set('display_errors',1); ini_set('display_startup_errors',1); error_reporting(E_ALL);

require_once __DIR__ . '/../../../config.php';
require_once ROOT_PATH . '/system/config/autenticacao.php';
require_once ROOT_PATH . '/system/config/connect.php';

if (!isset($conn) && isset($mysqli)) $conn = $mysqli;
if (!($conn instanceof mysqli)) die('Sem DB');

if (session_status()!==PHP_SESSION_ACTIVE) session_start();
proteger_pagina();
$conn->set_charset('utf8mb4');

function flash($m){ $_SESSION['__flash']=['m'=>$m]; }
function go(){ header('Location: '.BASE_URL.'/public/modules/forms/hooks_listar.php'); exit; }

$id=(int)($_POST['id']??0);
if($id<=0){ flash('ID inválido.'); go(); }

$st=$conn->prepare("SELECT * FROM forms_reprocess_hook WHERE id=? LIMIT 1");
$st->bind_param("i",$id);
$st->execute();
$hook=$st->get_result()->fetch_assoc();
$st->close();
if(!$hook){ flash('Hook não encontrado.'); go(); }

$endpoint = trim((string)$hook['endpoint']);
$secret   = trim((string)($hook['secret'] ?? ''));

$isInternal = (strpos($endpoint,'://')===false && str_starts_with($endpoint,'/')) || (strpos($endpoint, BASE_URL)===0);
if(!$isInternal){ flash('Endpoint não permitido.'); go(); }

$url = $endpoint;
if (str_starts_with($url,'/')) $url = BASE_URL.$url;

$body = [
  'submission_id' => 0,
  'form_code' => (string)$hook['form_code'],
  'version' => 1,
  'payload' => ['__test'=>true, 'msg'=>'ping'],
  'meta' => ['source'=>'hooks_test']
];
$bodyJson = json_encode($body, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);

$headers=['Content-Type: application/json'];
if($secret!==''){
  $headers[]='X-Forms-Signature: '.hash_hmac('sha256',$bodyJson,$secret);
  $headers[]='X-Forms-Test: 1';
}

$ch=curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 8);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $bodyJson);
$resp = curl_exec($ch);
$http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
$err  = curl_error($ch);
curl_close($ch);

if($resp===false){
  flash('Teste falhou: '.$err);
  go();
}
flash("Teste OK? HTTP {$http} — resposta: ".substr((string)$resp,0,180));
go();
