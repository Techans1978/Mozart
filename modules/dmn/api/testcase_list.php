<?php
ini_set('display_errors', 0);

require_once __DIR__ . '/../../../config.php';
require_once ROOT_PATH . '/system/config/connect.php';
require_once ROOT_PATH . '/system/config/autenticacao.php';
require_once ROOT_PATH . '/modules/dmn/includes/dmn_helpers.php';

if (session_status()===PHP_SESSION_NONE) session_start();
proteger_pagina();

$decisionId = (int)($_GET['decision_id'] ?? 0);
if ($decisionId <= 0) dmn_json(['ok'=>false,'error'=>'Informe decision_id.'], 400);

$sql = "SELECT id, name, context_json, expected_json, is_active, created_at
        FROM moz_dmn_testcase
        WHERE decision_id=?
        ORDER BY id DESC";

$st = $conn->prepare($sql);
if (!$st) dmn_json(['ok'=>false,'error'=>$conn->error], 500);
$st->bind_param("i", $decisionId);
$st->execute();
$rs = $st->get_result();

$items = [];
while ($r = $rs->fetch_assoc()) $items[] = $r;
$st->close();

dmn_json(['ok'=>true,'items'=>$items]);
