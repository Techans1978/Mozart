<?php
// modules/forms/actions/forms_set_categories.php
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
function go($id){ header('Location: '.BASE_URL.'/public/modules/forms/forms_gerenciar.php?id='.$id); exit; }

$formId = (int)($_POST['form_id'] ?? 0);
$cats   = $_POST['category_ids'] ?? [];

if ($formId<=0) { flash('Form inválido.'); go($formId); }
if (!is_array($cats)) $cats = [];

$cats = array_values(array_unique(array_filter(array_map('intval', $cats), fn($v)=>$v>0)));

$conn->begin_transaction();
try {
  // limpa vínculos atuais
  $st = $conn->prepare("DELETE FROM forms_form_category WHERE form_id=?");
  $st->bind_param("i",$formId);
  $st->execute();
  $st->close();

  // insere novos
  if (count($cats)) {
    $st = $conn->prepare("INSERT INTO forms_form_category (form_id, category_id) VALUES (?,?)");
    foreach($cats as $cid){
      $st->bind_param("ii",$formId,$cid);
      $st->execute();
    }
    $st->close();
  }

  $conn->commit();
  flash('Categorias do formulário atualizadas.');
  go($formId);
} catch (Throwable $e) {
  $conn->rollback();
  flash('Erro ao salvar categorias: '.$e->getMessage());
  go($formId);
}
