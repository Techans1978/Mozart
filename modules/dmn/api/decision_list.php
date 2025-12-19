<?php
ini_set('display_errors', 0);

require_once __DIR__ . '/../../../config.php';
require_once ROOT_PATH . '/system/config/connect.php';
require_once ROOT_PATH . '/system/config/autenticacao.php';
require_once ROOT_PATH . '/modules/dmn/includes/dmn_helpers.php';

if (session_status()===PHP_SESSION_NONE) session_start();
proteger_pagina();

$q = trim((string)($_GET['q'] ?? ''));
$status = trim((string)($_GET['status'] ?? ''));
$categoryId = (int)($_GET['category_id'] ?? 0);
$tag = trim((string)($_GET['tag'] ?? ''));

$where = [];
$params = [];
$types = '';

if ($q !== '') {
  $where[] = "(d.name LIKE ? OR d.rule_key LIKE ?)";
  $like = "%{$q}%";
  $params[] = $like; $params[] = $like;
  $types .= 'ss';
}
if ($status !== '' && in_array($status, ['draft','published','archived'], true)) {
  $where[] = "d.status = ?";
  $params[] = $status;
  $types .= 's';
}
if ($categoryId > 0) {
  $where[] = "d.category_id = ?";
  $params[] = $categoryId;
  $types .= 'i';
}
if ($tag !== '') {
  // tags é JSON: busca simples por string dentro (ok pro v1)
  $where[] = "JSON_SEARCH(d.tags, 'one', ?) IS NOT NULL";
  $params[] = $tag;
  $types .= 's';
}

$sql = "SELECT
          d.id, d.category_id, d.rule_key, d.name, d.description, d.status, d.tags,
          d.created_at, d.updated_at,
          c.name AS category_name
        FROM moz_dmn_decision d
        LEFT JOIN moz_dmn_category c ON c.id = d.category_id";

if ($where) $sql .= " WHERE " . implode(" AND ", $where);
$sql .= " ORDER BY d.updated_at DESC, d.id DESC LIMIT 500";

$st = $conn->prepare($sql);
if (!$st) dmn_json(['ok'=>false,'error'=>$conn->error], 500);

if ($params) {
  $st->bind_param($types, ...$params);
}

$st->execute();
$rs = $st->get_result();
$items = [];
while ($r = $rs->fetch_assoc()) $items[] = $r;
$st->close();

dmn_json(['ok'=>true, 'items'=>$items]);
