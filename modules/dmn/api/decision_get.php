<?php
ini_set('display_errors', 0);

require_once __DIR__ . '/../../../config.php';
require_once ROOT_PATH . '/system/config/connect.php';
require_once ROOT_PATH . '/system/config/autenticacao.php';
require_once ROOT_PATH . '/modules/dmn/includes/dmn_helpers.php';

if (session_status()===PHP_SESSION_NONE) session_start();
proteger_pagina();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) dmn_json(['ok'=>false,'error'=>'Informe id.'], 400);

// decision
$st = $conn->prepare("SELECT * FROM moz_dmn_decision WHERE id=? LIMIT 1");
$st->bind_param("i", $id);
$st->execute();
$dec = $st->get_result()->fetch_assoc();
$st->close();

if (!$dec) dmn_json(['ok'=>false,'error'=>'Decision não encontrada.'], 404);

// draft xml
$draft = null;
$st = $conn->prepare("SELECT id, xml, checksum, created_at FROM moz_dmn_version WHERE decision_id=? AND type='draft' ORDER BY id DESC LIMIT 1");
$st->bind_param("i", $id);
$st->execute();
$draft = $st->get_result()->fetch_assoc();
$st->close();

// published latest
$pub = null;
$st = $conn->prepare("SELECT id, version_num, xml, checksum, published_at, created_at FROM moz_dmn_version WHERE decision_id=? AND type='published' ORDER BY version_num DESC LIMIT 1");
$st->bind_param("i", $id);
$st->execute();
$pub = $st->get_result()->fetch_assoc();
$st->close();

dmn_json([
  'ok'=>true,
  'decision'=>$dec,
  'draft'=>$draft,
  'published'=>$pub
]);
