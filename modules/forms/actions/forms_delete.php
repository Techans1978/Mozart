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
if ($id<=0) {
  flash('ID inválido.');
  header('Location: '.BASE_URL.'/public/modules/forms/forms_listar.php'); exit;
}

// Cascade já deleta versions por FK ON DELETE CASCADE
$stmt = $conn->prepare("DELETE FROM forms_form WHERE id=? LIMIT 1");
$stmt->bind_param("i",$id);
$stmt->execute();
$stmt->close();

flash('Formulário excluído.');
header('Location: '.BASE_URL.'/public/modules/forms/forms_listar.php');
