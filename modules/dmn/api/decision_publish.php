<?php
ini_set('display_errors', 0);

require_once __DIR__ . '/../../../config.php';
require_once ROOT_PATH . '/system/config/connect.php';
require_once ROOT_PATH . '/system/config/autenticacao.php';
require_once ROOT_PATH . '/modules/dmn/includes/dmn_helpers.php';

if (session_status()===PHP_SESSION_NONE) session_start();
proteger_pagina();

$body = dmn_body_json();
$id = (int)($body['id'] ?? 0);
$notes = trim((string)($body['notes'] ?? ''));

if ($id <= 0) dmn_json(['ok'=>false,'error'=>'Informe id.'], 400);

$conn->begin_transaction();

try {
  // pega draft atual
  $st = $conn->prepare("SELECT xml FROM moz_dmn_version WHERE decision_id=? AND type='draft' ORDER BY id DESC LIMIT 1");
  $st->bind_param("i", $id);
  $st->execute();
  $draft = $st->get_result()->fetch_assoc();
  $st->close();

  if (!$draft || trim((string)$draft['xml']) === '') {
    throw new Exception("Sem draft para publicar.");
  }

  $xml = (string)$draft['xml'];
  $checksum = dmn_checksum($xml);

  // próximo version_num
  $st = $conn->prepare("SELECT COALESCE(MAX(version_num),0) mx FROM moz_dmn_version WHERE decision_id=? AND type='published'");
  $st->bind_param("i", $id);
  $st->execute();
  $mx = (int)($st->get_result()->fetch_assoc()['mx'] ?? 0);
  $st->close();

  $next = $mx + 1;

  // insere published
  $st = $conn->prepare("INSERT INTO moz_dmn_version(decision_id, type, version_num, xml, checksum, notes, created_at, published_at)
                        VALUES(?, 'published', ?, ?, ?, ?, NOW(), NOW())");
  if (!$st) throw new Exception($conn->error);
  $st->bind_param("iisss", $id, $next, $xml, $checksum, $notes);
  if (!$st->execute()) throw new Exception($st->error);
  $st->close();

  // atualiza decision status
  $st = $conn->prepare("UPDATE moz_dmn_decision SET status='published', updated_at=NOW() WHERE id=?");
  $st->bind_param("i", $id);
  if (!$st->execute()) throw new Exception($st->error);
  $st->close();

  $conn->commit();
  dmn_json(['ok'=>true, 'id'=>$id, 'version_num'=>$next]);

} catch (Throwable $e) {
  $conn->rollback();
  dmn_json(['ok'=>false,'error'=>$e->getMessage()], 500);
}
