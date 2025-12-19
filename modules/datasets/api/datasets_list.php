<?php
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../../config.php';
require_once ROOT_PATH . '/system/config/connect.php';
require_once ROOT_PATH . '/system/config/autenticacao.php';

if (session_status()===PHP_SESSION_NONE) session_start();
proteger_pagina();

$q = trim($_GET['q'] ?? '');
$status = trim($_GET['status'] ?? '');
$type = trim($_GET['type'] ?? '');
$tag = trim($_GET['tag'] ?? '');

$where = [];
$params = [];
$types = '';

if ($q !== '') {
  $where[] = "(name LIKE ? OR dataset_key LIKE ?)";
  $like = "%{$q}%";
  $params[] = $like; $params[] = $like;
  $types .= "ss";
}
if ($status !== '') {
  $where[] = "status = ?";
  $params[] = $status;
  $types .= "s";
}
if ($type !== '') {
  $where[] = "type = ?";
  $params[] = $type;
  $types .= "s";
}
if ($tag !== '') {
  $where[] = "tags_json LIKE ?";
  $params[] = '%"'.$tag.'"%';
  $types .= "s";
}

$sql = "SELECT id, dataset_key, name, type, status, updated_at FROM moz_ds_dataset";
if ($where) $sql .= " WHERE " . implode(" AND ", $where);
$sql .= " ORDER BY updated_at DESC LIMIT 500";

$st = $conn->prepare($sql);
if ($params) $st->bind_param($types, ...$params);
$st->execute();
$res = $st->get_result();

$items = [];
while($r=$res->fetch_assoc()) $items[] = $r;
$st->close();

echo json_encode(['ok'=>true,'items'=>$items], JSON_UNESCAPED_UNICODE);
