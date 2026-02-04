<?php
// modules/forms/actions/forms_sections_save.php — cria/edita seção no schema_json (draft)
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
$sectionId = trim((string)($_POST['section_id'] ?? '')); // vazio = criar
$title = trim((string)($_POST['title'] ?? ''));
$description = trim((string)($_POST['description'] ?? ''));
$gap = (int)($_POST['gap'] ?? 12);
if ($gap < 0) $gap = 0;
if ($gap > 48) $gap = 48;

if ($formId<=0 || $title==='') { flash('Dados inválidos.'); go($formId>0?$formId:0); }

$stmt = $conn->prepare("SELECT id, current_version FROM forms_form WHERE id=? LIMIT 1");
$stmt->bind_param("i",$formId);
$stmt->execute();
$form = $stmt->get_result()->fetch_assoc();
$stmt->close();
if(!$form){ flash('Formulário não encontrado.'); go($formId); }

$ver = max(1,(int)$form['current_version']);

// pega schema da versão atual (draft)
$stmt = $conn->prepare("SELECT id, schema_json, status FROM forms_form_version WHERE form_id=? AND version=? LIMIT 1");
$stmt->bind_param("ii",$formId,$ver);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();
if(!$row){ flash('Versão não encontrada.'); go($formId); }

// regra: só editar draft
if (($row['status'] ?? '') !== 'draft') { flash('Essa versão não está em draft.'); go($formId); }

$schema = json_decode($row['schema_json'], true);
if (!is_array($schema)) $schema = ['meta'=>[],'sections'=>[],'globals'=>[]];
if (!isset($schema['sections']) || !is_array($schema['sections'])) $schema['sections'] = [];

$sections = $schema['sections'];

// gera id para seção nova
if ($sectionId === '') {
  $base = preg_replace('/[^a-zA-Z0-9]+/', '_', strtolower($title));
  $base = trim($base,'_');
  if ($base==='') $base='sec';
  $sectionId = 'sec_' . $base . '_' . substr(bin2hex(random_bytes(4)),0,8);
}

// upsert
$found = false;
for ($i=0; $i<count($sections); $i++) {
  if (($sections[$i]['id'] ?? '') === $sectionId) {
    $sections[$i]['title'] = $title;
    $sections[$i]['description'] = $description;
    $sections[$i]['layout'] = array_merge((array)($sections[$i]['layout'] ?? []), ['gap'=>$gap]);
    if (!isset($sections[$i]['fields']) || !is_array($sections[$i]['fields'])) $sections[$i]['fields'] = [];
    if (!isset($sections[$i]['rules']) || !is_array($sections[$i]['rules'])) $sections[$i]['rules'] = [];
    $found = true;
    break;
  }
}

if (!$found) {
  $sections[] = [
    'id' => $sectionId,
    'title' => $title,
    'description' => $description,
    'layout' => ['gap'=>$gap],
    'fields' => [],
    'rules' => [],
  ];
}

$schema['sections'] = $sections;

$schemaJson = json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
if (!$schemaJson) { flash('Falha ao gerar JSON do schema.'); go($formId); }

$stmt = $conn->prepare("UPDATE forms_form_version SET schema_json=CAST(? AS JSON) WHERE id=? LIMIT 1");
$verId = (int)$row['id'];
$stmt->bind_param("si",$schemaJson,$verId);
$stmt->execute();
$stmt->close();

flash($found ? 'Seção atualizada.' : 'Seção adicionada.');
go($formId);
