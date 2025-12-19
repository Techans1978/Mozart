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
$category_id = isset($body['category_id']) && $body['category_id'] !== '' ? (int)$body['category_id'] : null;
$rule_key = trim((string)($body['rule_key'] ?? ''));
$name = trim((string)($body['name'] ?? ''));
$description = (string)($body['description'] ?? '');
$tags = $body['tags'] ?? null;
$xml = (string)($body['xml'] ?? '');

if ($rule_key === '' || $name === '') dmn_json(['ok'=>false,'error'=>'Informe rule_key e name.'], 400);
if (trim($xml) === '') dmn_json(['ok'=>false,'error'=>'Informe xml.'], 400);

// tags JSON
$tagsJson = null;
if (is_array($tags)) $tagsJson = json_encode($tags, JSON_UNESCAPED_UNICODE);

$conn->begin_transaction();

try {
  if ($id > 0) {
    // update decision
    $sql = "UPDATE moz_dmn_decision
            SET category_id=?, rule_key=?, name=?, description=?, tags=?, updated_at=NOW()
            WHERE id=?";
    $st = $conn->prepare($sql);
    if (!$st) throw new Exception($conn->error);
    $st->bind_param("issssi", $category_id, $rule_key, $name, $description, $tagsJson, $id);
    if (!$st->execute()) throw new Exception($st->error);
    $st->close();
  } else {
    // insert decision
    $sql = "INSERT INTO moz_dmn_decision(category_id, rule_key, name, description, status, tags, created_at, updated_at)
            VALUES(?,?,?,?, 'draft', ?, NOW(), NOW())";
    $st = $conn->prepare($sql);
    if (!$st) throw new Exception($conn->error);
    $st->bind_param("issss", $category_id, $rule_key, $name, $description, $tagsJson);
    if (!$st->execute()) throw new Exception($st->error);
    $id = (int)$conn->insert_id;
    $st->close();
  }

  // upsert draft (mantém 1 draft mais recente)
  $checksum = dmn_checksum($xml);

  $st = $conn->prepare("SELECT id FROM moz_dmn_version WHERE decision_id=? AND type='draft' ORDER BY id DESC LIMIT 1");
  $st->bind_param("i", $id);
  $st->execute();
  $d = $st->get_result()->fetch_assoc();
  $st->close();

  if ($d) {
    $draftId = (int)$d['id'];
    $st = $conn->prepare("UPDATE moz_dmn_version SET xml=?, checksum=?, created_at=NOW() WHERE id=?");
    if (!$st) throw new Exception($conn->error);
    $st->bind_param("ssi", $xml, $checksum, $draftId);
    if (!$st->execute()) throw new Exception($st->error);
    $st->close();
  } else {
    $st = $conn->prepare("INSERT INTO moz_dmn_version(decision_id, type, version_num, xml, checksum, created_at)
                          VALUES(?, 'draft', NULL, ?, ?, NOW())");
    if (!$st) throw new Exception($conn->error);
    $st->bind_param("iss", $id, $xml, $checksum);
    if (!$st->execute()) throw new Exception($st->error);
    $st->close();
  }

  $conn->commit();
  dmn_json(['ok'=>true, 'id'=>$id]);

} catch (Throwable $e) {
  $conn->rollback();
  dmn_json(['ok'=>false,'error'=>$e->getMessage()], 500);
}
