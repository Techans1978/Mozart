<?php
// modules/dmn/api/evaluate.php
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../../config.php';
require_once ROOT_PATH . '/system/config/connect.php';
require_once ROOT_PATH . '/system/config/autenticacao.php';
require_once ROOT_PATH . '/modules/dmn/lib/DmnEngine.php';

if (session_status() === PHP_SESSION_NONE) session_start();
proteger_pagina(); // depois podemos trocar por token interno do BPM

function jexit(int $code, array $payload): void {
  http_response_code($code);
  echo json_encode($payload, JSON_UNESCAPED_UNICODE);
  exit;
}

$body = json_decode(file_get_contents('php://input'), true);
if (!is_array($body)) $body = [];

$ruleKey = trim((string)($body['rule_key'] ?? ''));
$context = $body['context'] ?? null;

// opcional: para DMN com múltiplas decisions dentro do mesmo XML
$decisionDmnId = trim((string)($body['decision_dmn_id'] ?? ''));

if ($ruleKey === '' || !is_array($context)) {
  jexit(400, ['ok'=>false, 'error'=>'Informe rule_key (string) e context (objeto JSON).']);
}

$start = microtime(true);

$decisionId = null;
$versionNum = null;

try {
  // 1) resolve decision por rule_key
  $sql = "SELECT id FROM moz_dmn_decision WHERE rule_key=? LIMIT 1";
  $st = $conn->prepare($sql);
  if (!$st) throw new Exception("Prepare failed: ".$conn->error);
  $st->bind_param("s", $ruleKey);
  $st->execute();
  $row = $st->get_result()->fetch_assoc();
  $st->close();

  if (!$row) throw new Exception("Rule Key não encontrada: {$ruleKey}");
  $decisionId = (int)$row['id'];

  // 2) pega última versão publicada
  $sql = "SELECT version_num, xml
          FROM moz_dmn_version
          WHERE decision_id=? AND type='published'
          ORDER BY version_num DESC
          LIMIT 1";
  $st = $conn->prepare($sql);
  if (!$st) throw new Exception("Prepare failed: ".$conn->error);
  $st->bind_param("i", $decisionId);
  $st->execute();
  $verRow = $st->get_result()->fetch_assoc();
  $st->close();

  if (!$verRow) throw new Exception("Decision não possui versão publicada.");

  $versionNum = (int)$verRow['version_num'];
  $xml = (string)$verRow['xml'];

  // 3) avalia DMN
  $engine = new DmnEngine();
  $eval = $engine->evaluateDecisionTable($xml, $context, $decisionDmnId ?: null);

  $ms = (int) round((microtime(true) - $start) * 1000);

  // 4) log (JSON columns recebem string JSON normalmente)
  $ctxJson = json_encode($context, JSON_UNESCAPED_UNICODE);
  $resultJson = json_encode($eval, JSON_UNESCAPED_UNICODE);

  $ok = 1;
  $err = null;

  $sql = "INSERT INTO moz_dmn_exec_log
          (rule_key, decision_id, version_num, context_json, result_json, ok, error_msg, exec_ms)
          VALUES (?,?,?,?,?,?,?,?)";
  $st = $conn->prepare($sql);
  if ($st) {
    // s i i s s i s i
    $st->bind_param("siissisi", $ruleKey, $decisionId, $versionNum, $ctxJson, $resultJson, $ok, $err, $ms);
    $st->execute();
    $st->close();
  }

  echo json_encode([
    'ok' => true,
    'rule_key' => $ruleKey,
    'decision_id' => $decisionId,
    'version_num' => $versionNum,
    'exec_ms' => $ms,
    'evaluation' => $eval
  ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
  $ms = (int) round((microtime(true) - $start) * 1000);

  // tenta logar o erro (sem quebrar)
  try {
    $ctxJson = json_encode(is_array($context) ? $context : [], JSON_UNESCAPED_UNICODE);
    $ok = 0;
    $err = $e->getMessage();
    $resNull = null;

    $sql = "INSERT INTO moz_dmn_exec_log
            (rule_key, decision_id, version_num, context_json, result_json, ok, error_msg, exec_ms)
            VALUES (?,?,?,?,?,?,?,?)";
    $st = $conn->prepare($sql);
    if ($st) {
      $st->bind_param("siissisi", $ruleKey, $decisionId, $versionNum, $ctxJson, $resNull, $ok, $err, $ms);
      $st->execute();
      $st->close();
    }
  } catch (Throwable $ignore) {}

  jexit(500, ['ok'=>false, 'error'=>$e->getMessage()]);
}
