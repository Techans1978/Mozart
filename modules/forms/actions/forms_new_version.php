<?php
// modules/forms/actions/forms_new_version.php
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
function go_manage($id){ header('Location: '.BASE_URL.'/public/modules/forms/forms_gerenciar.php?id='.$id); exit; }
function go_list(){ header('Location: '.BASE_URL.'/public/modules/forms/forms_listar.php'); exit; }

$formId = (int)($_POST['id'] ?? 0);
if ($formId<=0){ flash('ID inválido.'); go_list(); }

$conn->begin_transaction();

try {
  $stmt=$conn->prepare("SELECT id, code, current_version FROM forms_form WHERE id=? LIMIT 1");
  $stmt->bind_param("i",$formId);
  $stmt->execute();
  $form=$stmt->get_result()->fetch_assoc();
  $stmt->close();
  if(!$form) throw new Exception('Form não encontrado.');

  // max version
  $stmt=$conn->prepare("SELECT MAX(version) AS mv FROM forms_form_version WHERE form_id=?");
  $stmt->bind_param("i",$formId);
  $stmt->execute();
  $mv=(int)($stmt->get_result()->fetch_assoc()['mv'] ?? 0);
  $stmt->close();
  $newVer = max(1,$mv+1);

  // schema base: last published, else current
  $stmt=$conn->prepare("SELECT schema_json FROM forms_form_version WHERE form_id=? AND status='published' ORDER BY version DESC LIMIT 1");
  $stmt->bind_param("i",$formId);
  $stmt->execute();
  $v=$stmt->get_result()->fetch_assoc();
  $stmt->close();

  if(!$v){
    $cv = max(1,(int)$form['current_version']);
    $stmt=$conn->prepare("SELECT schema_json FROM forms_form_version WHERE form_id=? AND version=? LIMIT 1");
    $stmt->bind_param("ii",$formId,$cv);
    $stmt->execute();
    $v=$stmt->get_result()->fetch_assoc();
    $stmt->close();
  }
  if(!$v) throw new Exception('Sem schema base.');

  $schemaJson = (string)$v['schema_json'];

  $stmt=$conn->prepare("INSERT INTO forms_form_version (form_id, version, status, schema_json, created_at, updated_at)
                        VALUES (?, ?, 'draft', CAST(? AS JSON), NOW(), NOW())");
  $stmt->bind_param("iis",$formId,$newVer,$schemaJson);
  $stmt->execute();
  $stmt->close();

  // aponta current_version para nova draft (pra wizard abrir nela)
  $stmt=$conn->prepare("UPDATE forms_form SET current_version=? WHERE id=? LIMIT 1");
  $stmt->bind_param("ii",$newVer,$formId);
  $stmt->execute();
  $stmt->close();

  $conn->commit();
  flash("Nova versão criada: v{$newVer} (draft).");
  go_manage($formId);

} catch (Throwable $e) {
  $conn->rollback();
  flash('Erro: '.$e->getMessage());
  go_manage($formId);
}
