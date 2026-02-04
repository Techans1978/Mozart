<?php
// modules/forms/actions/forms_publish.php
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
$verId  = (int)($_POST['version_id'] ?? 0);
if ($formId<=0 || $verId<=0){ flash('Dados inválidos.'); go($formId); }

$conn->begin_transaction();

try {
  // carrega versão
  $stmt = $conn->prepare("SELECT id, version, status FROM forms_form_version WHERE id=? AND form_id=? LIMIT 1");
  $stmt->bind_param("ii",$verId,$formId);
  $stmt->execute();
  $v = $stmt->get_result()->fetch_assoc();
  $stmt->close();
  if(!$v) throw new Exception('Versão não encontrada.');
  if (($v['status'] ?? '') !== 'draft') throw new Exception('Somente draft pode ser publicada.');

  $versionNum = (int)$v['version'];

  // (opcional) arquiva publicadas anteriores
  $stmt = $conn->prepare("UPDATE forms_form_version SET status='archived'
                          WHERE form_id=? AND status='published'");
  $stmt->bind_param("i",$formId);
  $stmt->execute();
  $stmt->close();

  // publica esta
  $stmt = $conn->prepare("UPDATE forms_form_version SET status='published', updated_at=NOW() WHERE id=? LIMIT 1");
  $stmt->bind_param("i",$verId);
  $stmt->execute();
  $stmt->close();

  // atualiza form
  $stmt = $conn->prepare("UPDATE forms_form SET status='active', current_version=? WHERE id=? LIMIT 1");
  $stmt->bind_param("ii",$versionNum,$formId);
  $stmt->execute();
  $stmt->close();

  $conn->commit();
  flash("Publicado v{$versionNum} com sucesso.");
  go($formId);

} catch (Throwable $e) {
  $conn->rollback();
  flash('Erro ao publicar: '.$e->getMessage());
  go($formId);
}
