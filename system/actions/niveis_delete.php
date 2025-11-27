<?php
// system/actions/niveis_delete.php
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../config.php';
require_once ROOT_PATH . '/system/config/autenticacao.php';
require_once ROOT_PATH . '/system/config/connect.php';

proteger_pagina();

$mysqli = $mysqli ?? ($conn ?? null);
if (!$mysqli) { die('Sem conexão DB'); }

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header('Location: ' . BASE_URL . '/pages/niveis_list.php?erro=1');
    exit;
}

// Idealmente, checar se é nível crítico (ex.: superadmin) e bloquear a exclusão

$mysqli->begin_transaction();

try {

    // Apaga RBAC novo
    if ($delCaps = $mysqli->prepare("DELETE FROM acl_level_caps WHERE level_id = ?")) {
        $delCaps->bind_param('i', $id);
        $delCaps->execute();
        $delCaps->close();
    }

    // Apaga permissões legadas
    if ($delPerms = $mysqli->prepare("DELETE FROM acl_permissions WHERE level_id = ?")) {
        $delPerms->bind_param('i', $id);
        $delPerms->execute();
        $delPerms->close();
    }

    // Apaga o nível em si
    if ($delNivel = $mysqli->prepare("DELETE FROM acl_levels WHERE id = ?")) {
        $delNivel->bind_param('i', $id);
        $delNivel->execute();
        $delNivel->close();
    }

    $mysqli->commit();

    header('Location: ' . BASE_URL . '/pages/niveis_list.php?deleted=1');
    exit;

} catch (Exception $e) {
    $mysqli->rollback();
    echo "Erro ao excluir nível: " . htmlspecialchars($e->getMessage());
    exit;
}
