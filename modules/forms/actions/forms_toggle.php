<?php
ini_set('display_errors',1); ini_set('display_startup_errors',1); error_reporting(E_ALL);

require_once __DIR__ . '/../../../config.php';
require_once ROOT_PATH . '/system/config/autenticacao.php';
require_once ROOT_PATH . '/system/config/connect.php';

if (!isset($conn) && isset($mysqli)) { $conn = $mysqli; }
if (!($conn instanceof mysqli)) die('Conexão MySQLi $conn não encontrada.');

if (session_status() !== PHP_SESSION_ACTIVE) session_start();
proteger_pagina();

$conn->set_charset('utf8mb4');
function flash($m){ $_SESSION['__flash']=['m'=>$m]; }

$id = (int)($_POST['id'] ?? 0);
$to = $_POST['to'] ?? '';
$allowed = ['draft','published','archived'];
if ($id<=0 || !in_array($to,$allowed,true)) {
  flash('Ação inválida.');
  header('Location: '.BASE_URL.'/public/modules/forms/forms_listar.php'); exit;
}

$stmt = $conn->prepare("UPDATE forms_form SET status=? WHERE id=? LIMIT 1");
$stmt->bind_param("si",$to,$id);
$stmt->execute();
$stmt->close();

flash("Status atualizado para: $to.");
header('Location: '.BASE_URL.'/public/modules/forms/forms_listar.php');
