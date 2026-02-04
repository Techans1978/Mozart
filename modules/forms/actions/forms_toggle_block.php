<?php
// modules/forms/actions/forms_toggle_block.php
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

$id = (int)($_POST['id'] ?? 0);
if ($id<=0){ flash('ID inválido.'); go($id); }

$stmt = $conn->prepare("SELECT status FROM forms_form WHERE id=? LIMIT 1");
$stmt->bind_param("i",$id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();
if(!$row){ flash('Form não encontrado.'); go($id); }

$cur = (string)$row['status'];
$new = ($cur==='blocked') ? 'active' : 'blocked';

$stmt = $conn->prepare("UPDATE forms_form SET status=? WHERE id=? LIMIT 1");
$stmt->bind_param("si",$new,$id);
$stmt->execute();
$stmt->close();

flash($new==='blocked' ? 'Form bloqueado.' : 'Form desbloqueado.');
go($id);
