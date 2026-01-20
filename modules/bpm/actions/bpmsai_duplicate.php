<?php
require_once __DIR__ . '/../../../config.php';
require_once ROOT_PATH . '/system/config/autenticacao.php';
require_once ROOT_PATH . '/system/config/connect.php';
if (session_status()===PHP_SESSION_NONE) session_start();
proteger_pagina();

$flowId = (int)($_GET['flow_id'] ?? 0);
$fromVer = (int)($_GET['from_ver'] ?? 0
