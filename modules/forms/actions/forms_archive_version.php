<?php
// modules/forms/actions/forms_archive_version.php
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

$stmt = $conn->prepare("UPDATE forms_form_version SET status='archived', updated_at=NOW() WHERE id=? AND form_id=? LIMIT 1");
$stmt->bind_param("ii",$verId,$formId);
$stmt->execute();
$stmt->close();

flash('Versão arquivada.');
go($formId);
