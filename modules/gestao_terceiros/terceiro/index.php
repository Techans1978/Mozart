<?php
session_start();

// Verifica se o usuário está logado
if (isset($_SESSION['usuario_id'])) {
    // Redireciona para o dashboard
    header("Location: dashboard.php");
    exit;
} else {
    // Redireciona para a página de login
    header("Location: login.php");
    exit;
}
?>