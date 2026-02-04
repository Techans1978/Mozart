<?php
// modules/forms/actions/submissions_delete.php
ini_set('display_errors',1); ini_set('display_startup_errors',1); error_reporting(E_ALL);

require_once __DIR__ . '/../../../config.php';
require_once ROOT_PATH . '/system/config/autenticacao.php';
require_once ROOT_PATH . '/system/config/connect.php';

if (!isset($conn) && isset($mysqli)) $conn = $mysqli;
if (!($conn instanceof mysqli)) die('Conexão MySQLi $conn não encontrada.');

if (session_status() !== PHP_SESSION_ACTIVE) session_start();
proteger_pagina();
$conn->set_charset('utf8mb4');

function flash($m){ $_SESSION['__flash']=['m'=>$m]; }
function back($q=''){
  $u = BASE_URL.'/public/modules/forms/submissions_listar.php';
  if ($q) $u .= '?'.$q;
  header('Location: '.$u);
  exit;
}

$id = (int)($_POST['id'] ?? 0);
$back = trim((string)($_POST['back'] ?? ''));

if ($id<=0){ flash('ID inválido.'); back($back); }

$stmt = $conn->prepare("DELETE FROM forms_form_submission WHERE id=? LIMIT 1");
$stmt->bind_param("i",$id);
$stmt->execute();
$stmt->close();

flash('Submissão excluída.');
back($back);
