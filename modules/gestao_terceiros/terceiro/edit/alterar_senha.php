<?php
require '../lib/conexao.php';
require '../lib/autenticacao.php';
proteger_pagina();

session_start();

if (!isset($_SESSION['user_id'])) {
    die("Usuário não autenticado.");
}

$user_id = $_SESSION['user_id'];

// Verifica se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $senha_atual = $_POST['senha_atual'] ?? '';
    $nova_senha = $_POST['nova_senha'] ?? '';
    $confirmar_senha = $_POST['confirmar_senha'] ?? '';

    // Validação básica
    if (empty($senha_atual) || empty($nova_senha) || empty($confirmar_senha)) {
        die("Todos os campos são obrigatórios.");
    }

    if ($nova_senha !== $confirmar_senha) {
        die("A nova senha e a confirmação não coincidem.");
    }

    // Buscar senha atual no banco
    $stmt = $conn->prepare("SELECT password_hash FROM promotores WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $usuario = $result->fetch_assoc();

    if (!$usuario || !password_verify($senha_atual, $usuario['password_hash'])) {
        die("Senha atual incorreta.");
    }

    // Atualizar a senha
    $nova_senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
    $update = $conn->prepare("UPDATE promotores SET password_hash = ? WHERE id = ?");
    $update->bind_param("si", $nova_senha_hash, $user_id);

    if ($update->execute()) {
        echo "<script>alert('Senha alterada com sucesso!'); window.location.href='terceiro_senha.php';</script>";
    } else {
        die("Erro ao atualizar a senha. Tente novamente.");
    }

    $update->close();
    $stmt->close();
    $conn->close();
}
?>