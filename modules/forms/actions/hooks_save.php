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
function go_list(){ header('Location: '.BASE_URL.'/public/modules/forms/hooks_listar.php'); exit; }

$id = (int)($_POST['id'] ?? 0);
$form_code = strtoupper(trim((string)($_POST['form_code'] ?? '')));
$endpoint  = trim((string)($_POST['endpoint'] ?? ''));
$method    = strtoupper(trim((string)($_POST['method'] ?? 'POST')));
$secret    = trim((string)($_POST['secret'] ?? ''));
$ativo     = (int)($_POST['ativo'] ?? 1);

if ($form_code==='' || $endpoint===''){ flash('Preencha form_code e endpoint.'); go_list(); }
if (!preg_match('/^[A-Z0-9_:\-\.]{2,80}$/', $form_code)){ flash('form_code inválido.'); go_list(); }
if ($method!=='POST') $method='POST';

// allowlist endpoint interno
$isInternal = (strpos($endpoint,'://')===false && str_starts_with($endpoint,'/')) || (strpos($endpoint, BASE_URL)===0);
if(!$isInternal){ flash('Endpoint não permitido (somente interno).'); go_list(); }

if ($id>0){
  $st=$conn->prepare("UPDATE forms_reprocess_hook
                      SET form_code=?, endpoint=?, method=?, secret=?, ativo=?, updated_at=NOW()
                      WHERE id=? LIMIT 1");
  $st->bind_param("ssssii",$form_code,$endpoint,$method,$secret,$ativo,$id);
  $st->execute(); $st->close();
  flash('Hook atualizado.');
  go_list();
}

// insert
$st=$conn->prepare("INSERT INTO forms_reprocess_hook (form_code, endpoint, method, secret, ativo, updated_at)
                    VALUES (?,?,?,?,?,NOW())");
$st->bind_param("ssssi",$form_code,$endpoint,$method,$secret,$ativo);
$ok=$st->execute();
$err=$st->error;
$st->close();

if(!$ok){
  flash('Erro ao salvar (talvez já exista hook para esse form_code): '.$err);
  go_list();
}
flash('Hook criado.');
go_list();
