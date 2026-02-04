<?php
// modules/forms/actions/forms_rules_save.php
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

function flash(string $m){ $_SESSION['__flash']=['m'=>$m]; }
function go(int $formId){
  header('Location: '.BASE_URL.'/public/modules/forms/wizard/4.php?form_id='.$formId);
  exit;
}

$formId = (int)($_POST['form_id'] ?? 0);
$ruleId = trim((string)($_POST['rule_id'] ?? ''));
$name   = trim((string)($_POST['name'] ?? ''));

$adv_when = trim((string)($_POST['adv_when'] ?? ''));
$adv_then = trim((string)($_POST['adv_then'] ?? ''));
$adv_else = trim((string)($_POST['adv_else'] ?? ''));

if ($formId<=0 || $name==='' || $adv_when==='' || $adv_then==='') {
  flash('Preencha nome + WHEN + THEN.');
  go($formId>0?$formId:0);
}

$stmt = $conn->prepare("SELECT current_version FROM forms_form WHERE id=? LIMIT 1");
$stmt->bind_param("i",$formId);
$stmt->execute();
$form = $stmt->get_result()->fetch_assoc();
$stmt->close();
if(!$form){ flash('Formulário não encontrado.'); go($formId); }

$ver = max(1,(int)$form['current_version']);

$stmt = $conn->prepare("SELECT id, schema_json, status FROM forms_form_version WHERE form_id=? AND version=? LIMIT 1");
$stmt->bind_param("ii",$formId,$ver);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();
if(!$row){ flash('Versão não encontrada.'); go($formId); }
if (($row['status'] ?? '') !== 'draft'){ flash('Essa versão não está em draft.'); go($formId); }

$schema = json_decode($row['schema_json'], true);
if (!is_array($schema)) $schema = ['meta'=>[],'sections'=>[],'globals'=>[],'rules'=>[]];
if (!isset($schema['rules']) || !is_array($schema['rules'])) $schema['rules'] = [];

$when = json_decode($adv_when, true);
if ($when === null) { flash('WHEN JSON inválido.'); go($formId); }
$then = json_decode($adv_then, true);
if ($then === null) { flash('THEN JSON inválido.'); go($formId); }

$else = null;
if ($adv_else !== '') {
  $else = json_decode($adv_else, true);
  if ($else === null) { flash('ELSE JSON inválido.'); go($formId); }
}

if ($ruleId === '') {
  $ruleId = 'r_' . substr(bin2hex(random_bytes(6)),0,12);
}

$rule = [
  'id' => $ruleId,
  'name' => $name,
  'when' => $when,
  'then' => $then,
];

if ($else !== null) $rule['else'] = $else;

$found = false;
for ($i=0;$i<count($schema['rules']);$i++){
  if ((string)($schema['rules'][$i]['id'] ?? '') === $ruleId){
    $schema['rules'][$i] = $rule;
    $found = true;
    break;
  }
}
if (!$found) $schema['rules'][] = $rule;

$schemaJson = json_encode($schema, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
if (!$schemaJson){ flash('Falha ao gerar JSON do schema.'); go($formId); }

$stmt = $conn->prepare("UPDATE forms_form_version SET schema_json=CAST(? AS JSON) WHERE id=? LIMIT 1");
$verId = (int)$row['id'];
$stmt->bind_param("si",$schemaJson,$verId);
$stmt->execute();
$stmt->close();

flash($found ? 'Regra atualizada.' : 'Regra adicionada.');
go($formId);
