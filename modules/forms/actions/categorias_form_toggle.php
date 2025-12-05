<?php
// modules/forms/actions/categorias_form_toggle.php
ini_set('display_errors',1); ini_set('display_startup_errors',1); error_reporting(E_ALL);

require_once __DIR__ . '/../../../config.php';
require_once ROOT_PATH . '/system/config/autenticacao.php';
require_once ROOT_PATH . '/system/config/connect.php';

if (!isset($conn) && isset($mysqli)) { $conn = $mysqli; }
if (!($conn instanceof mysqli)) { die('Erro: conexão MySQLi não encontrada.'); }
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    $_SESSION['__flash'] = ['m' => 'ID inválido para alterar status da categoria.'];
    header('Location: ../categorias_form_listar.php');
    exit;
}

// Busca estado atual
$sql = "SELECT ativo FROM moz_form_category WHERE id = ?";
$st  = $conn->prepare($sql);
if (!$st) { die('prepare: '.$conn->error); }
$st->bind_param('i', $id);
$st->execute();
$rs  = $st->get_result();
$row = $rs ? $rs->fetch_assoc() : null;
$st->close();

if (!$row) {
    $_SESSION['__flash'] = ['m' => 'Categoria de formulário não encontrada.'];
    header('Location: ../categorias_form_listar.php');
    exit;
}

$novo = ((int)$row['ativo'] === 1) ? 0 : 1;

// Atualiza
$sql = "UPDATE moz_form_category SET ativo = ? WHERE id = ?";
$st  = $conn->prepare($sql);
if (!$st) { die('prepare: '.$conn->error); }
$st->bind_param('ii', $novo, $id);
$st->execute();
$st->close();

$_SESSION['__flash'] = ['m' => 'Status da categoria alterado com sucesso.'];
header('Location: ../categorias_form_listar.php');
exit;
