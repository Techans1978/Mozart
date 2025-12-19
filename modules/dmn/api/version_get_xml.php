<?php
// modules/dmn/api/version_get_xml.php
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../../config.php';
require_once ROOT_PATH . '/system/config/connect.php';
require_once ROOT_PATH . '/system/config/autenticacao.php';

if (session_status() === PHP_SESSION_NONE) session_start();
proteger_pagina();

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
  http_response_code(400);
  echo json_encode(['ok'=>false,'error'=>'id inválido']);
  exit;
}

$sql = "SELECT id, decision_id, type, version_num, xml, checksum, created_at, published_at
        FROM moz_dmn_version
        WHERE id = ?
        LIMIT 1";

$st = $conn->prepare($sql);
$st->bind_param("i", $id);
$st->execute();
$row = $st->get_result()->fetch_assoc();
$st->close();

if (!$row) {
  echo json_encode(['ok'=>false,'error'=>'Versão não encontrada']);
  exit;
}

echo json_encode([
  'ok' => true,
  'item' => $row
], JSON_UNESCAPED_UNICODE);
