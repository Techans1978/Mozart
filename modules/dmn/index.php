<?php
// modules/dmn/index.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../config.php';
require_once ROOT_PATH . '/system/config/autenticacao.php';
require_once ROOT_PATH . '/system/config/connect.php';

if (session_status() === PHP_SESSION_NONE) session_start();
proteger_pagina();

// Se você quiser no futuro decidir por área (front/back), dá pra trocar aqui.
// Por enquanto, DMN é back-office:
header('Location: ' . BASE_URL . '/modules/dmn/dmn_list.php');
exit;
