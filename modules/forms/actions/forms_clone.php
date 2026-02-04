<?php
// modules/forms/actions/forms_clone.php
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
function go_list(){ header('Location: '.BASE_URL.'/public/modules/forms/forms_listar.php'); exit; }

$id = (int)($_POST['id'] ?? 0);
if ($id<=0){ flash('ID inválido.'); go_list(); }

$conn->begin_transaction();

try {
  // form original
  $stmt=$conn->prepare("SELECT * FROM forms_form WHERE id=? LIMIT 1");
  $stmt->bind_param("i",$id);
  $stmt->execute();
  $form=$stmt->get_result()->fetch_assoc();
  $stmt->close();
  if(!$form) throw new Exception('Form não encontrado.');

  $origCode = (string)$form['code'];
  $newCode = $origCode.'_CLONE_'.substr(bin2hex(random_bytes(3)),0,6);
  $newTitle = ((string)($form['title'] ?? $origCode)).' (Clone)';

  // cria novo form
  $stmt=$conn->prepare("INSERT INTO forms_form (code, title, status, current_version, created_at)
                        VALUES (?,?, 'blocked', 1, NOW())");
  $stmt->bind_param("ss",$newCode,$newTitle);
  $stmt->execute();
  $newFormId = (int)$stmt->insert_id;
  $stmt->close();

  // pega última versão published do original, senão current
  $stmt=$conn->prepare("SELECT schema_json FROM forms_form_version WHERE form_id=? AND status='published' ORDER BY version DESC LIMIT 1");
  $stmt->bind_param("i",$id);
  $stmt->execute();
  $v=$stmt->get_result()->fetch_assoc();
  $stmt->close();

  if(!$v){
    $cv = max(1,(int)$form['current_version']);
    $stmt=$conn->prepare("SELECT schema_json FROM forms_form_version WHERE form_id=? AND version=? LIMIT 1");
    $stmt->bind_param("ii",$id,$cv);
    $stmt->execute();
    $v=$stmt->get_result()->fetch_assoc();
    $stmt->close();
  }
  if(!$v) throw new Exception('Sem schema para clonar.');

  // cria versão 1 draft no clone
  $schemaJson = (string)$v['schema_json'];
  $stmt=$conn->prepare("INSERT INTO forms_form_version (form_id, version, status, schema_json, created_at, updated_at)
                        VALUES (?, 1, 'draft', CAST(? AS JSON), NOW(), NOW())");
  $stmt->bind_param("is",$newFormId,$schemaJson);
  $stmt->execute();
  $stmt->close();

  $conn->commit();
  flash("Clonado: {$newCode} (bloqueado por padrão). Abra no gerenciar e publique quando quiser.");
  go_list();

} catch (Throwable $e) {
  $conn->rollback();
  flash('Erro ao clonar: '.$e->getMessage());
  go_list();
}
