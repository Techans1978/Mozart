<?php
// modules/bpm/api/catalog.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../../config.php';
require_once ROOT_PATH . '/system/config/autenticacao.php';
require_once ROOT_PATH . '/system/config/connect.php';

if (session_status() === PHP_SESSION_NONE) session_start();
proteger_pagina();

header('Content-Type: application/json; charset=utf-8');

$forms = [];
$sql = "SELECT id, nome, slug, tipo, categoria
        FROM moz_forms
        WHERE ativo = 1
        ORDER BY nome ASC";

if ($res = $conn->query($sql)) {
  while ($row = $res->fetch_assoc()) $forms[] = $row;
  $res->free();
}

echo json_encode([
  'forms'  => $forms,
  'users'  => [],
  'groups' => [],
  'perfis' => []
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
