<?php
require_once __DIR__ . '/../../../config.php';
require_once ROOT_PATH . '/system/config/autenticacao.php';
require_once ROOT_PATH . '/system/config/connect.php';
if (session_status()===PHP_SESSION_NONE) session_start();
proteger_pagina();

$id = (int)($_GET['id'] ?? 0);
if($id<=0) die('id invalido');

// MVP: deleta direto (versões caem por cascade).
$conn->query("DELETE FROM bpmsai_flow WHERE id=".$id." LIMIT 1");
header('Location: /modules/bpm/bpmsai-listar.php');
exit;
