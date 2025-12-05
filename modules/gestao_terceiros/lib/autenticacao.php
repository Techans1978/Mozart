<?php
session_start();

// Verifica se o usuário está logado
function proteger_pagina() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: ../login.php");
        exit();
    }
}
?>