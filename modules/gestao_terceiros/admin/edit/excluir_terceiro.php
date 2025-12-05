<?php
// ex: public/modules/gestao_terceiros/admin/edit/excluir_promotor.php
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(E_ALL);

require_once __DIR__.'/../../../../config.php';
require_once ROOT_PATH.'/system/config/autenticacao.php';
require_once ROOT_PATH.'/system/config/connect.php';
require_once ROOT_PATH.'/modules/gestao_terceiros/config/gest_connect.php'; // Inclui o arquivo de conexão correto

if (session_status()===PHP_SESSION_NONE) session_start();
proteger_pagina();

// Valida ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    // Se quiser pode setar uma mensagem de erro em sessão
    // $_SESSION['flash_error'] = 'ID inválido para exclusão.';
    header("Location: listar_terceiros.php");
    exit;
}

// Prepara DELETE
$sql  = "DELETE FROM promotores WHERE id = ?";
$stmt = $conn_terc->prepare($sql);

if (!$stmt) {
    // Fatal discreto, mas sem quebrar a tela
    // $_SESSION['flash_error'] = 'Erro ao preparar exclusão: ' . $conn_terc->error;
    header("Location: listar_terceiros.php");
    exit;
}

$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    // $_SESSION['flash_success'] = 'Registro excluído com sucesso!';
} else {
    // $_SESSION['flash_error'] = 'Erro ao excluir o registro: ' . $stmt->error;
}

$stmt->close();
$conn_terc->close();

header("Location: listar_terceiros.php"); // Redireciona de volta para a listagem após a exclusão
exit;
