<?php
ini_set('display_errors', 0);

require_once __DIR__ . '/../../../config.php';
require_once ROOT_PATH . '/system/config/connect.php';
require_once ROOT_PATH . '/system/config/autenticacao.php';
require_once ROOT_PATH . '/modules/dmn/includes/dmn_helpers.php';

if (session_status()===PHP_SESSION_NONE) session_start();
proteger_pagina();

$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  $action = $action ?: 'list';

  if ($action === 'list') {
    $onlyActive = isset($_GET['active']) ? (int)$_GET['active'] : 0;

    $sql = "SELECT id, parent_id, name, slug, icon, sort, is_active, created_at, updated_at
            FROM moz_dmn_category";
    if ($onlyActive) $sql .= " WHERE is_active=1";
    $sql .= " ORDER BY sort ASC, name ASC";

    $rows = [];
    $rs = $conn->query($sql);
    while ($rs && ($r=$rs->fetch_assoc())) $rows[] = $r;

    dmn_json(['ok'=>true, 'items'=>$rows]);
  }

  dmn_json(['ok'=>false, 'error'=>'Ação GET inválida.'], 400);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $body = dmn_body_json();
  $action = $body['action'] ?? '';

  if ($action === 'save') {
    $id = (int)($body['id'] ?? 0);
    $parent_id = isset($body['parent_id']) && $body['parent_id'] !== '' ? (int)$body['parent_id'] : null;
    $name = trim((string)($body['name'] ?? ''));
    $slug = trim((string)($body['slug'] ?? ''));
    $icon = trim((string)($body['icon'] ?? ''));
    $sort = (int)($body['sort'] ?? 0);
    $is_active = isset($body['is_active']) ? (int)$body['is_active'] : 1;

    if ($name === '') dmn_json(['ok'=>false,'error'=>'Informe name.'], 400);
    if ($slug === '') $slug = dmn_slug($name);

    if ($id > 0) {
      $sql = "UPDATE moz_dmn_category
              SET parent_id=?, name=?, slug=?, icon=?, sort=?, is_active=?
              WHERE id=?";
      $st = $conn->prepare($sql);
      if (!$st) dmn_json(['ok'=>false,'error'=>$conn->error], 500);
      // parent_id pode ser NULL
      $st->bind_param("isssiii", $parent_id, $name, $slug, $icon, $sort, $is_active, $id);
      $ok = $st->execute();
      $err = $st->error;
      $st->close();
      if (!$ok) dmn_json(['ok'=>false,'error'=>$err], 500);

      dmn_json(['ok'=>true, 'id'=>$id]);
    } else {
      $sql = "INSERT INTO moz_dmn_category(parent_id, name, slug, icon, sort, is_active)
              VALUES(?,?,?,?,?,?)";
      $st = $conn->prepare($sql);
      if (!$st) dmn_json(['ok'=>false,'error'=>$conn->error], 500);
      $st->bind_param("isssii", $parent_id, $name, $slug, $icon, $sort, $is_active);
      $ok = $st->execute();
      $err = $st->error;
      $newId = $conn->insert_id;
      $st->close();
      if (!$ok) dmn_json(['ok'=>false,'error'=>$err], 500);

      dmn_json(['ok'=>true, 'id'=>$newId]);
    }
  }

  if ($action === 'delete') {
    $id = (int)($body['id'] ?? 0);
    if ($id <= 0) dmn_json(['ok'=>false,'error'=>'Informe id.'], 400);

    // não deixa apagar se tem decisões
    $st = $conn->prepare("SELECT COUNT(*) c FROM moz_dmn_decision WHERE category_id=?");
    $st->bind_param("i", $id);
    $st->execute();
    $c = (int)($st->get_result()->fetch_assoc()['c'] ?? 0);
    $st->close();
    if ($c > 0) dmn_json(['ok'=>false,'error'=>'Categoria tem decisões vinculadas.'], 400);

    $st = $conn->prepare("DELETE FROM moz_dmn_category WHERE id=?");
    $st->bind_param("i", $id);
    $ok = $st->execute();
    $err = $st->error;
    $st->close();
    if (!$ok) dmn_json(['ok'=>false,'error'=>$err], 500);

    dmn_json(['ok'=>true]);
  }

  dmn_json(['ok'=>false,'error'=>'Ação POST inválida.'], 400);
}

dmn_json(['ok'=>false,'error'=>'Método inválido.'], 405);
