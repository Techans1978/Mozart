<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../../config.php';
require_once ROOT_PATH . '/system/config/autenticacao.php';
require_once ROOT_PATH . '/system/config/connect.php';
if (session_status()===PHP_SESSION_NONE) session_start();
proteger_pagina();

$_SESSION['bpmsai_wizard']['last_human_test'] = [
  'status' => 'never',
  'messages' => [],
  'at' => date('c')
];

echo json_encode(['ok'=>true], JSON_UNESCAPED_UNICODE);
