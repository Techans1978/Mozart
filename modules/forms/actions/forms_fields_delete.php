<?php
// modules/forms/actions/forms_fields_delete.php — remove field da seção no schema_json (draft)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../../config.php';
require_once ROOT_PATH . '/system/config/autenticacao.php';
require_once ROOT_PATH . '/system/config/connect.php';

if (!isset($conn) && isset($mysqli)) { $conn = $mysqli; }
if (!($conn instanceof mysqli)) { die('Conexão MySQLi $conn não encontrada.'); }

if (session_status() !== PHP_SESSION_ACTIVE) session_start();
proteger_pagina();

$conn->set_charset('utf8mb4');
function flash(string $m){ $_SESSION['__flash']=['m'=>$m]; }
function go(int $formId, string $sectionId){
  header('Location: '.BASE_URL.'/public/modules/forms/wizard/3.php?form_id='.$formId.'&section_id='.urlencode($sectionId));
  exit;
}

$formId = (int)($_POST['form_id'] ?? 0);
$sectionId = trim((string)($_POST['section_id'] ?? ''));
$fieldId = trim((string)($_POST['field_id'] ?? ''));
if ($formId<=0 || $sectionId==='' || $fieldId===''){ flash('Dados inválidos.'); go($formId,$sectionId); }

$stmt = $conn->prepare("SELECT current_version FROM forms_form WHERE id=? LIMIT 1");
$stmt->bind_param("i",$formId);
$stmt->execute();
$form = $stmt->get_result()->fetch_assoc();
$stmt->close();
if(!$form){ flash('Formulário não encontrado.'); go($formId,$sectionId); }

$ver = max(1,(int)$form['current_version']);

$stmt = $conn->prepare("SELECT id, schema_json, status FROM forms_form_version WHERE form_id=? AND version=? LIMIT 1");
$stmt->bind_param("ii",$formId,$ver);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();
if(!$row){ flash('Versão não encontrada.'); go($formId,$sectionId); }
if (($row['status'] ?? '') !== 'draft'){ flash('Essa versão não está em draft.'); go($formId,$sectionId); }

$schema = json_decode($row['schema_json'], true);
if (!is_array($schema)) $schema = ['meta'=>[],'sections'=>[],'globals'=>[]];

$secIdx = -1;
for ($i=0;$i<count($schema['sections'] ?? []);$i++){
  if ((string)($schema['sections'][$i]['id'] ?? '') === $sectionId){ $secIdx=$i; break; }
}
if ($secIdx<0){ flash('Seção não encontrada.'); go($formId,$sectionId); }

$fields = $schema['sections'][$secIdx]['fields'] ?? [];
if (!is_array($fields)) $fields = [];
$before = count($fields);

$fields = array_values(array_filter($fields, function($f) use ($fieldId){
  return (string)($f['id'] ?? '') !== $fieldId;
}));
$after = count($fields);

$schema['sections'][$secIdx]['fields'] = $fields;

$schemaJson = json_encode($schema, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
if (!$schemaJson){ flash('Falha ao gerar JSON do schema.'); go($formId,$sectionId); }

$stmt = $conn->prepare("UPDATE forms_form_version SET schema_json=CAST(? AS JSON) WHERE id=? LIMIT 1");
$verId = (int)$row['id'];
$stmt->bind_param("si",$schemaJson,$verId);
$stmt->execute();
$stmt->close();

flash(($after<$before) ? 'Campo excluído.' : 'Campo não encontrado.');
go($formId,$sectionId);
