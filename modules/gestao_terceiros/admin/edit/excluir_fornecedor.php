<?php
// public/modules/gestao_ativos/ativos-form.php
ini_set('display_errors',1); ini_set('display_startup_errors',1); error_reporting(E_ALL);

require_once __DIR__.'/../../../../config.php';
require_once ROOT_PATH.'/system/config/autenticacao.php';
require_once ROOT_PATH.'/system/config/connect.php';
require_once ROOT_PATH.'modules/gestao_terceiros/config/gest_connect.php'; // Inclui o arquivo de conexão
if (session_status()===PHP_SESSION_NONE) session_start();
proteger_pagina();

$id = intval($_GET['id']);

$sql = "DELETE FROM lista_fornecedores WHERE id = ?";
$stmt = $conn_terc->prepare($sql);
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    echo "Fornecedor excluído com sucesso!";
} else {
    echo "Erro ao excluir o fornecedor: " . $stmt->error;
}

$stmt->close();
$conn_terc->close();

header("Location: listagem_fornecedores.php"); // Redireciona de volta para a listagem após a exclusão
exit();
?>
