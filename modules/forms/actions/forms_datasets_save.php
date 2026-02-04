<?php
// modules/forms/actions/forms_datasets_save.php
ini_set('display_errors',1); ini_set('display_startup_errors',1); error_reporting(E_ALL);

require_once __DIR__ . '/../../../config.php';
require_once ROOT_PATH . '/system/config/autenticacao.php';
require_once ROOT_PATH . '/system/config/connect.php';

if (!isset($conn) && isset($mysqli)) $conn = $mysqli;
if (!($conn instanceof mysqli)) die('Conexão MySQLi $conn não encontrada.');
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
proteger_pagina();
$conn->set_charset('utf8mb4');

function flash($m){ $_SESSION['__flash']=['m'=>$m]; }
function go($formId){ header('Location: '.BASE_URL.'/public/modules/forms/wizard/5.php?form_id='.$formId); exit; }

$formId = (int)($_POST['form_id'] ?? 0);
$datasetId = trim((string)($_POST['dataset_id'] ?? ''));
$name = trim((string)($_POST['name'] ?? ''));
$mode = trim((string)($_POST['mode'] ?? 'static'));

$key = trim((string)($_POST['key'] ?? 'value'));
$label = trim((string)($_POST['label'] ?? 'label'));

$optionsJson = trim((string)($_POST['options'] ?? ''));
$endpoint = trim((string)($_POST['endpoint'] ?? ''));
$method = trim((string)($_POST['method'] ?? 'GET'));
$sql = trim((string)($_POST['sql'] ?? ''));
$paramsJson = trim((string)($_POST['params'] ?? ''));

if ($formId<=0 || $name===''){ flash('Dados inválidos.'); go($formId); }
if (!in_array($mode,['static','endpoint','sql'],true)){ flash('Mode inválido.'); go($formId); }
if (!in_array($method,['GET','POST'],true)) $method='GET';

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
if (!isset($schema['globals']) || !is_array($schema['globals'])) $schema['globals'] = [];
if (!isset($schema['globals']['datasets']) || !is_array($schema['globals']['datasets'])) $schema['globals']['datasets'] = [];

$datasets = $schema['globals']['datasets'];

if ($datasetId === '') {
  $base = preg_replace('/[^a-zA-Z0-9]+/','_', strtolower($name));
  $base = trim($base,'_');
  if ($base==='') $base='dataset';
  $datasetId = 'ds_'.$base.'_'.substr(bin2hex(random_bytes(4)),0,8);
}

// parse options/params
$options = null;
if ($optionsJson !== '') {
  $tmp = json_decode($optionsJson, true);
  if ($tmp === null) { flash('Options JSON inválido.'); go($formId); }
  $options = $tmp;
}
$params = null;
if ($paramsJson !== '') {
  $tmp = json_decode($paramsJson, true);
  if ($tmp === null) { flash('Params JSON inválido.'); go($formId); }
  $params = $tmp;
}

$ds = [
  'id' => $datasetId,
  'name' => $name,
  'mode' => $mode,
  'key' => ($key!==''?$key:'value'),
  'label' => ($label!==''?$label:'label'),
];

if ($mode === 'static') {
  $ds['options'] = is_array($options) ? $options : [];
} elseif ($mode === 'endpoint') {
  $ds['endpoint'] = $endpoint;
  $ds['method'] = $method;
  if (is_array($params)) $ds['params'] = $params;
} else { // sql
  $ds['sql'] = $sql;
  if (is_array($params)) $ds['params'] = $params;
}

$found = false;
for ($i=0;$i<count($datasets);$i++){
  if ((string)($datasets[$i]['id'] ?? '') === $datasetId){
    $datasets[$i] = $ds;
    $found = true;
    break;
  }
}
if (!$found) $datasets[] = $ds;

$schema['globals']['datasets'] = $datasets;

$schemaJson = json_encode($schema, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
if (!$schemaJson){ flash('Falha ao gerar JSON.'); go($formId); }

$stmt = $conn->prepare("UPDATE forms_form_version SET schema_json=CAST(? AS JSON) WHERE id=? LIMIT 1");
$verId = (int)$row['id'];
$stmt->bind_param("si",$schemaJson,$verId);
$stmt->execute();
$stmt->close();

flash($found ? 'Dataset atualizado.' : 'Dataset adicionado.');
go($formId);
