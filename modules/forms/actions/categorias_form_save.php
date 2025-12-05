<?php
// modules/forms/actions/categorias_form_save.php
ini_set('display_errors',1); ini_set('display_startup_errors',1); error_reporting(E_ALL);

require_once __DIR__ . '/../../../config.php';
require_once ROOT_PATH . '/system/config/autenticacao.php';
require_once ROOT_PATH . '/system/config/connect.php';

if (!isset($conn) && isset($mysqli)) { $conn = $mysqli; }
if (!($conn instanceof mysqli)) { die('Erro: conexão MySQLi não encontrada.'); }
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }

$id            = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$nome          = trim($_POST['nome'] ?? '');
$slug          = trim($_POST['slug'] ?? '');
$descricao     = trim($_POST['descricao'] ?? '');
$contexto_tipo = trim($_POST['contexto_tipo'] ?? 'global');
$contexto_id   = isset($_POST['contexto_id']) && $_POST['contexto_id'] !== '' ? (int)$_POST['contexto_id'] : null;
$cor_hex       = trim($_POST['cor_hex'] ?? '');
$sort_order    = isset($_POST['sort_order']) ? (int)$_POST['sort_order'] : 0;
$ativo         = isset($_POST['ativo']) ? 1 : 0;

if ($nome === '') {
    $_SESSION['__flash'] = ['m' => 'Nome é obrigatório.'];
    header('Location: ../categorias_form_form.php'.($id>0 ? '?id='.$id : ''));
    exit;
}

// Gera slug se vier vazio
if ($slug === '') {
    $slug = strtolower($nome);
    $slug = preg_replace('/[^a-z0-9]+/i', '-', $slug);
    $slug = trim($slug, '-');
}

// Evita slug vazio
if ($slug === '') {
    $slug = 'cat-'.time();
}

if ($id > 0) {
    // UPDATE
    $sql = "UPDATE moz_form_category
            SET nome = ?, slug = ?, descricao = ?, contexto_tipo = ?, contexto_id = ?, 
                cor_hex = ?, ativo = ?, sort_order = ?, updated_at = NOW()
            WHERE id = ?";
    $st = $conn->prepare($sql);
    if (!$st) { die('prepare: '.$conn->error); }

    // contexto_id pode ser null
    if ($contexto_id === null) {
        $null = null;
        $st->bind_param('sssssiiii',
            $nome,
            $slug,
            $descricao,
            $contexto_tipo,
            $null,
            $cor_hex,
            $ativo,
            $sort_order,
            $id
        );
    } else {
        $st->bind_param('sssssisii',
            $nome,
            $slug,
            $descricao,
            $contexto_tipo,
            $contexto_id,
            $cor_hex,
            $ativo,
            $sort_order,
            $id
        );
    }

    $st->execute();
    $st->close();
    $_SESSION['__flash'] = ['m' => 'Categoria de formulário atualizada com sucesso.'];

} else {
    // INSERT
    $sql = "INSERT INTO moz_form_category
              (nome, slug, descricao, contexto_tipo, contexto_id, cor_hex, ativo, sort_order, created_at)
            VALUES
              (?,?,?,?,?,?,?,?,NOW())";
    $st = $conn->prepare($sql);
    if (!$st) { die('prepare: '.$conn->error); }

    if ($contexto_id === null) {
        $null = null;
        $st->bind_param('sssssii',
            $nome,
            $slug,
            $descricao,
            $contexto_tipo,
            $null,
            $cor_hex,
            $ativo,
            $sort_order
        );
    } else {
        $st->bind_param('sssssiis',
            $nome,
            $slug,
            $descricao,
            $contexto_tipo,
            $contexto_id,
            $cor_hex,
            $ativo,
            $sort_order
        );
    }

    $st->execute();
    $st->close();
    $_SESSION['__flash'] = ['m' => 'Categoria de formulário criada com sucesso.'];
}

header('Location: ../categorias_form_listar.php');
exit;
