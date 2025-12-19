<?php
// modules/dmn/api/resolve_rule_key.php
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../../config.php';
require_once ROOT_PATH . '/system/config/connect.php';
require_once ROOT_PATH . '/system/config/autenticacao.php';

if (session_status() === PHP_SESSION_NONE) session_start();
proteger_pagina();

$ruleKey = trim($_GET['rule_key'] ?? '');

if ($ruleKey === '') {
  http_response_code(400);
  echo json_encode(['ok'=>false,'error'=>'rule_key obrigatório']);
  exit;
}

$sql = "
SELECT d.id AS decision_id,
       d.name,
       v.id AS version_id,
       v.version_num,
       v.published_at
FROM moz_dmn_decision d
LEFT JOIN moz_dmn_version v
  ON v.decision_id = d.id AND v.type='published'
WHERE d.rule_key = ?
ORDER BY v.version_num DESC
LIMIT 1";

$st = $conn->prepare($sql);
$st->bind_param("s", $ruleKey);
$st->execute();
$row = $st->get_result()->fetch_assoc();
$st->close();

if (!$row) {
  echo json_encode(['ok'=>false,'error'=>'Rule key não encontrada']);
  exit;
}

echo json_encode([
  'ok' => true,
  'item' => $row
], JSON_UNESCAPED_UNICODE);
