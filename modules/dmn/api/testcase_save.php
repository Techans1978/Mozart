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
$decisionId = (int)($body['decision_id'] ?? 0);
$name = trim((string)($body['name'] ?? ''));
$context = $body['context'] ?? null;
$expected = $body['expected'] ?? null;
$is_active = isset($body['is_active']) ? (int)$body['is_active'] : 1;

if ($decisionId <= 0 || $name === '' || !is_array($context)) {
  dmn_json(['ok'=>false,'error'=>'Informe decision_id, name e context (JSON).'], 400);
}

$ctxJson = json_encode($context, JSON_UNESCAPED_UNICODE);
$expJson = is_array($expected) ? json_encode($expected, JSON_UNESCAPED_UNICODE) : null;

if ($id > 0) {
  $sql = "UPDATE moz_dmn_testcase
          SET name=?, context_json=?, expected_json=?, is_active=?
          WHERE id=? AND decision_id=?";
  $st = $conn->prepare($sql);
  if (!$st) dmn_json(['ok'=>false,'error'=>$conn->error], 500);
  $st->bind_param("sssiii", $name, $ctxJson, $expJson, $is_active, $id, $decisionId);
  if (!$st->execute()) dmn_json(['ok'=>false,'error'=>$st->error], 500);
  $st->close();
  dmn_json(['ok'=>true,'id'=>$id]);
} else {
  $sql = "INSERT INTO moz_dmn_testcase(decision_id, name, context_json, expected_json, is_active, created_at)
          VALUES(?,?,?,?,?,NOW())";
  $st = $conn->prepare($sql);
  if (!$st) dmn_json(['ok'=>false,'error'=>$conn->error], 500);
  $st->bind_param("isssi", $decisionId, $name, $ctxJson, $expJson, $is_active);
  if (!$st->execute()) dmn_json(['ok'=>false,'error'=>$st->error], 500);
  $newId = (int)$conn->insert_id;
  $st->close();
  dmn_json(['ok'=>true,'id'=>$newId]);
}
