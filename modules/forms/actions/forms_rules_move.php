<?php
// modules/forms/actions/forms_rules_move.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../../config.php';
require_once ROOT_PATH . '/system/config/autenticacao.php';
require_once ROOT_PATH . '/system/config/connect.php';

if (!isset($conn) && isset($mysqli)) { $conn = $mysqli; }
if (!($conn instanceof mysqli)) die('Conexão MySQLi $conn não encontrada.');

if (session_status() !== PHP_SESSION_ACTIVE) session_start();
proteger_pagina();

$conn->set_charset('utf8mb4');
function go(int $formId){
  header('Location: '.BASE_URL.'/public/modules/forms/wizard/4.php?form_id='.$formId);
  exit;
}

$formId = (int)($_POST['form_id'] ?? 0);
$ruleId = trim((string)($_POST['rule_id'] ?? ''));
$dir = $_POST['dir'] ?? '';
if ($formId<=0 || $ruleId==='' || !in_array($dir,['up','down'],true)) go($formId);

$stmt = $conn->prepare("SELECT current_version FROM forms_form WHERE id=? LIMIT 1");
$stmt->bind_param("i",$formId);
$stmt->execute();
$form = $stmt->get_result()->fetch_assoc();
$stmt->close();
if(!$form) go($formId);

$ver = max(1,(int)$form['current_version']);

$stmt = $conn->prepare("SELECT id, schema_json, status FROM forms_form_version WHERE form_id=? AND version=? LIMIT 1");
$stmt->bind_param("ii",$formId,$ver);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();
if(!$row || ($row['status'] ?? '') !== 'draft') go($formId);

$schema = json_decode($row['schema_json'], true);
if (!is_array($schema)) $schema = ['rules'=>[]];
if (!isset($schema['rules']) || !is_array($schema['rules'])) $schema['rules'] = [];

$rules = $schema['rules'];

$idx = -1;
for ($i=0;$i<count($rules);$i++){
  if ((string)($rules[$i]['id'] ?? '') === $ruleId){ $idx=$i; break; }
}
if ($idx<0) go($formId);

$newIdx = ($dir==='up') ? $idx-1 : $idx+1;
if ($newIdx < 0 || $newIdx >= count($rules)) go($formId);

$tmp = $rules[$idx];
$rules[$idx] = $rules[$newIdx];
$rules[$newIdx] = $tmp;

$schema['rules'] = $rules;

$schemaJson = json_encode($schema, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
if (!$schemaJson) go($formId);

$stmt = $conn->prepare("UPDATE forms_form_version SET schema_json=CAST(? AS JSON) WHERE id=? LIMIT 1");
$verId = (int)$row['id'];
$stmt->bind_param("si",$schemaJson,$verId);
$stmt->execute();
$stmt->close();

go($formId);
