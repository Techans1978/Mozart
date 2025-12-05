<?php
// modules/forms/actions/forms_delete.php
ini_set('display_errors',1); ini_set('display_startup_errors',1); error_reporting(E_ALL);

require_once __DIR__ . '/../../../config.php';
require_once ROOT_PATH . '/system/config/autenticacao.php';
require_once ROOT_PATH . '/system/config/connect.php';

if (!isset($conn) && isset($mysqli)) { $conn = $mysqli; }
if (!($conn instanceof mysqli)) { die('Erro: conexão MySQLi não encontrada.'); }
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    $_SESSION['__flash'] = ['m' => 'ID inválido para exclusão.'];
    header('Location: ../forms_listar.php');
    exit;
}

// Verifica se existe
$sql = "SELECT id FROM moz_forms WHERE id = ?";
$st = $conn->prepare($sql);
$st->bind_param('i', $id);
$st->execute();
$rs = $st->get_result();
$row = $rs ? $rs->fetch_assoc() : null;
$st->close();

if (!$row) {
    $_SESSION['__flash'] = ['m' => 'Formulário não encontrado.'];
    header('Location: ../forms_listar.php');
    exit;
}

/*
    ⚠️ Aqui você poderá adicionar verificações futuras:
    - Se o form está vinculado a um processo BPM
    - Se está vinculado ao helpdesk (serviço, categoria, etapa)
    - Se possui respostas gravadas

    Por enquanto, deixamos a exclusão direta.
*/

$sql = "DELETE FROM moz_forms WHERE id = ?";
$st = $conn->prepare($sql);
$st->bind_param('i', $id);
$st->execute();
$st->close();

$_SESSION['__flash'] = ['m' => 'Formulário excluído com sucesso.'];
header('Location: ../forms_listar.php');
exit;
