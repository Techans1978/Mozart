<?php
// modules/forms/actions/forms_sections_delete.php — remove seção do schema_json (draft)
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
function go(int $formId){ header('Location: '.BASE_URL.'/public/modules/forms/wizard/2.php?form_id='.$formId); exit; }

$formId = (int)($_POST['form_id'] ?? 0);
$sectionId = trim((string)($_POST['section_id'] ?? ''));
if ($formId<=0 || $sectionId==='') { flash('Dados inválidos.'); go($formId); }

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
if (($row['status'] ?? '') !== 'draft') { flash('Essa versão não está em draft.'); go($formId); }

$schema = json_decode($row['schema_json'], true);
if (!is_array($schema)) $schema = ['meta'=>[],'sections'=>[],'globals'=>[]];
if (!isset($schema['sections']) || !is_array($schema['sections'])) $schema['sections'] = [];

$before = count($schema['sections']);
$schema['sections'] = array_values(array_filter($schema['sections'], function($s) use ($sectionId){
  return (string)($s['id'] ?? '') !== $sectionId;
}));
$after = count($schema['sections']);

$schemaJson = json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
if (!$schemaJson) { flash('Falha ao gerar JSON do schema.'); go($formId); }

$stmt = $conn->prepare("UPDATE forms_form_version SET schema_json=CAST(? AS JSON) WHERE id=? LIMIT 1");
$verId = (int)$row['id'];
$stmt->bind_param("si",$schemaJson,$verId);
$stmt->execute();
$stmt->close();

flash(($after < $before) ? 'Seção excluída.' : 'Seção não encontrada.');
go($formId);
