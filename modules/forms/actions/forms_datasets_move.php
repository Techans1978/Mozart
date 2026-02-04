<?php
// modules/forms/actions/forms_datasets_move.php
ini_set('display_errors',1); ini_set('display_startup_errors',1); error_reporting(E_ALL);
require_once __DIR__ . '/../../../config.php';
require_once ROOT_PATH . '/system/config/autenticacao.php';
require_once ROOT_PATH . '/system/config/connect.php';
if (!isset($conn) && isset($mysqli)) $conn = $mysqli;
if (!($conn instanceof mysqli)) die('Conexão MySQLi $conn não encontrada.');
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
proteger_pagina();
$conn->set_charset('utf8mb4');

function go($formId){ header('Location: '.BASE_URL.'/public/modules/forms/wizard/5.php?form_id='.$formId); exit; }

$formId = (int)($_POST['form_id'] ?? 0);
$datasetId = trim((string)($_POST['dataset_id'] ?? ''));
$dir = $_POST['dir'] ?? '';
if ($formId<=0 || $datasetId==='' || !in_array($dir,['up','down'],true)) go($formId);

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
if(!$row || ($row['status'] ?? '')!=='draft') go($formId);

$schema = json_decode($row['schema_json'], true);
if (!is_array($schema)) $schema = ['globals'=>['datasets'=>[]]];
if (!isset($schema['globals']['datasets']) || !is_array($schema['globals']['datasets'])) $schema['globals']['datasets'] = [];

$ds = $schema['globals']['datasets'];

$idx=-1;
for($i=0;$i<count($ds);$i++){
  if ((string)($ds[$i]['id'] ?? '') === $datasetId){ $idx=$i; break; }
}
if ($idx<0) go($formId);

$newIdx = ($dir==='up') ? $idx-1 : $idx+1;
if ($newIdx<0 || $newIdx>=count($ds)) go($formId);

$tmp = $ds[$idx];
$ds[$idx] = $ds[$newIdx];
$ds[$newIdx] = $tmp;

$schema['globals']['datasets'] = $ds;

$schemaJson = json_encode($schema, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
if (!$schemaJson) go($formId);

$stmt = $conn->prepare("UPDATE forms_form_version SET schema_json=CAST(? AS JSON) WHERE id=? LIMIT 1");
$verId = (int)$row['id'];
$stmt->bind_param("si",$schemaJson,$verId);
$stmt->execute();
$stmt->close();

go($formId);
