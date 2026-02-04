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

$st=$conn->prepare("DELETE FROM forms_reprocess_hook WHERE id=? LIMIT 1");
$st->bind_param("i",$id);
$st->execute(); $st->close();

flash('Hook excluído.');
go();
