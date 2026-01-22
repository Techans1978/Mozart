<?php
// modules/bpm/actions/categorias_bpm_save.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../../config.php';
require_once ROOT_PATH . '/system/config/autenticacao.php';
require_once ROOT_PATH . '/system/config/connect.php';

if (!isset($conn) && isset($mysqli)) { $conn = $mysqli; }
if (!($conn instanceof mysqli)) { die('Conexão MySQLi $conn não encontrada.'); }

if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
proteger_pagina();

$conn->set_charset('utf8mb4');

function flash(string $msg): void { $_SESSION['__flash']=['m'=>$msg]; }

function redirect_form(int $id=0): void {
  header('Location: '.BASE_URL.'/modules/bpm/categorias_bpm_form.php'.($id?('?id='.$id):''));
  exit;
}
function redirect_list(): void {
  header('Location: '.BASE_URL.'/modules/bpm/categorias_bpm_listar.php');
  exit;
}

function slug_codigo(string $s): string {
  $s = trim($s);
  if ($s === '') return '';
  $t = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s);
  if ($t !== false) $s = $t;
  $s = strtolower($s);
  $s = preg_replace('/[^a-z0-9]+/', '_', $s);
  $s = trim($s, '_');
  return substr($s, 0, 40);
}

function rand_code(int $len = 18): string {
  $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // sem O/0/I/1
  $out = '';
  $max = strlen($alphabet) - 1;
  for ($i=0; $i<$len; $i++) $out .= $alphabet[random_int(0, $max)];
  return $out;
}

// ========= Inputs =========
$id         = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$nome       = trim((string)($_POST['nome'] ?? ''));
$codigo     = trim((string)($_POST['codigo'] ?? ''));
$sort_order = isset($_POST['sort_order']) ? (int)$_POST['sort_order'] : 0;
$ativo      = isset($_POST['ativo']) ? 1 : 0;

// parent_id: raiz => NULL (via NULLIF)
$parent_id = 0;
if (isset($_POST['parent_id']) && $_POST['parent_id'] !== '') {
  $parent_id = (int)$_POST['parent_id'];
  if ($parent_id < 0) $parent_id = 0;
}

if ($nome === '') {
  flash('Nome é obrigatório.');
  redirect_form($id);
}

// normaliza código e gera se vazio (evita UNIQUE duplicado por vazio)
$codigo = slug_codigo($codigo);
if ($codigo === '') {
  $base = slug_codigo($nome);
  if ($base !== '') $base = substr($base, 0, 18);
  $codigo = ($base ? ($base.'_') : '') . rand_code(18);
  $codigo = substr($codigo, 0, 40);
}

try {
  $conn->begin_transaction();

  if ($id <= 0) {
    // INSERT
    $sql = "INSERT INTO bpm_categorias (nome, codigo, parent_id, sort_order, ativo, created_at, updated_at)
            VALUES (?, ?, NULLIF(?,0), ?, ?, NOW(), NOW())";
    $st = $conn->prepare($sql);
    if (!$st) throw new Exception('prepare insert: '.$conn->error);

    $st->bind_param('ssiii', $nome, $codigo, $parent_id, $sort_order, $ativo);
    if (!$st->execute()) throw new Exception('execute insert: '.$st->error);
    $st->close();

    $id = (int)$conn->insert_id;
    if ($id <= 0) throw new Exception('Falha ao obter insert_id.');

  } else {
    // UPDATE
    $sql = "UPDATE bpm_categorias
            SET nome=?, codigo=?, parent_id=NULLIF(?,0), sort_order=?, ativo=?, updated_at=NOW()
            WHERE id=? LIMIT 1";
    $st = $conn->prepare($sql);
    if (!$st) throw new Exception('prepare update: '.$conn->error);

    $st->bind_param('ssiiii', $nome, $codigo, $parent_id, $sort_order, $ativo, $id);
    if (!$st->execute()) throw new Exception('execute update: '.$st->error);
    $st->close();
  }

  // ====== REBUILD CLOSURE DO NÓ (sempre) ======
  $st = $conn->prepare("DELETE FROM bpm_categorias_paths WHERE descendant_id=?");
  if (!$st) throw new Exception('prepare delete paths: '.$conn->error);
  $st->bind_param('i', $id);
  if (!$st->execute()) throw new Exception('execute delete paths: '.$st->error);
  $st->close();

  if ($parent_id > 0) {
    $sql = "INSERT INTO bpm_categorias_paths (ancestor_id, descendant_id, depth)
            SELECT ancestor_id, ?, depth+1
            FROM bpm_categorias_paths
            WHERE descendant_id = ?";
    $st = $conn->prepare($sql);
    if (!$st) throw new Exception('prepare inherit paths: '.$conn->error);
    $st->bind_param('ii', $id, $parent_id);
    if (!$st->execute()) throw new Exception('execute inherit paths: '.$st->error);
    $st->close();
  }

  $st = $conn->prepare("INSERT INTO bpm_categorias_paths (ancestor_id, descendant_id, depth) VALUES (?,?,0)");
  if (!$st) throw new Exception('prepare self path: '.$conn->error);
  $st->bind_param('ii', $id, $id);
  if (!$st->execute()) throw new Exception('execute self path: '.$st->error);
  $st->close();

  $conn->commit();

  flash('Categoria salva com sucesso.');
  redirect_list();

} catch (Throwable $e) {
  if ($conn instanceof mysqli) { @$conn->rollback(); }
  flash('Erro ao salvar: '.$e->getMessage());
  redirect_form($id);
}
