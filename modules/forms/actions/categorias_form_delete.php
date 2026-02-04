<?php
// modules/forms/actions/categorias_form_delete.php
ini_set('display_errors',1); ini_set('display_startup_errors',1); error_reporting(E_ALL);

require_once __DIR__ . '/../../../config.php';
require_once ROOT_PATH . '/system/config/autenticacao.php';
require_once ROOT_PATH . '/system/config/connect.php';

if (!isset($conn) && isset($mysqli)) { $conn = $mysqli; }
if (!($conn instanceof mysqli)) { die('Erro: conexão MySQLi não encontrada.'); }
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
proteger_pagina();

$conn->set_charset('utf8mb4');

function flash($m){ $_SESSION['__flash']=['m'=>$m]; }

function table_exists(mysqli $db, string $t): bool {
  $rt = $db->real_escape_string($t);
  $r = $db->query("SHOW TABLES LIKE '$rt'");
  return $r && $r->num_rows > 0;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
  flash('ID inválido para exclusão.');
  header('Location: ../categorias_form_listar.php');
  exit;
}

// Verifica se existe
$sql = "SELECT id, nome, slug FROM moz_form_category WHERE id = ? LIMIT 1";
$st  = $conn->prepare($sql);
if (!$st) { die('prepare: '.$conn->error); }
$st->bind_param('i', $id);
$st->execute();
$row = $st->get_result()->fetch_assoc();
$st->close();

if (!$row) {
  flash('Categoria de formulário não encontrada.');
  header('Location: ../categorias_form_listar.php');
  exit;
}

// 🔒 Trava: não excluir se a categoria estiver sendo usada por formulários
if (table_exists($conn, 'forms_form_category')) {
  $sql = "SELECT 1 FROM forms_form_category WHERE category_id=? LIMIT 1";
  $st  = $conn->prepare($sql);
  if (!$st) { die('prepare: '.$conn->error); }
  $st->bind_param('i', $id);
  $st->execute();
  $inUse = $st->get_result()->fetch_assoc();
  $st->close();

  if ($inUse) {
    flash("Não é possível excluir: a categoria está vinculada a um ou mais formulários.");
    header('Location: ../categorias_form_listar.php');
    exit;
  }
}

// Executa exclusão
$sql = "DELETE FROM moz_form_category WHERE id = ? LIMIT 1";
$st  = $conn->prepare($sql);
if (!$st) { die('prepare: '.$conn->error); }
$st->bind_param('i', $id);
$st->execute();
$st->close();

flash('Categoria de formulário excluída com sucesso.');
header('Location: ../categorias_form_listar.php');
exit;
