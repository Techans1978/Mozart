<?php
require_once __DIR__ . '/../../../config.php';
require_once ROOT_PATH . '/system/config/autenticacao.php';
require_once ROOT_PATH . '/system/config/connect.php';
if (session_status()===PHP_SESSION_NONE) session_start();
proteger_pagina();

$id = (int)($_GET['id'] ?? 0);
if($id<=0) die('id invalido');

$conn->query("UPDATE bpmsai_flow SET is_active = IF(is_active=1,0,1) WHERE id=".$id." LIMIT 1");
header('Location: /modules/bpm/bpmsai-listar.php');
exit;
