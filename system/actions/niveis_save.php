<?php
// system/actions/niveis_save.php
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../config.php';
require_once ROOT_PATH . '/system/config/autenticacao.php';
require_once ROOT_PATH . '/system/config/connect.php';

proteger_pagina();

$mysqli = $mysqli ?? ($conn ?? null);
if (!$mysqli) { die('Sem conexão DB'); }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/pages/niveis_list.php');
    exit;
}

// ----------- Dados básicos do nível -----------
$id         = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$nome       = trim($_POST['nome'] ?? '');
$descricao  = trim($_POST['descricao'] ?? '');
$backend    = !empty($_POST['acesso_backend']) ? 1 : 0;
$frontend   = !empty($_POST['acesso_frontend']) ? 1 : 0;
$ativo      = !empty($_POST['ativo']) ? 1 : 0;

if ($nome === '') {
    // Poderia voltar com erro amigável, mas por enquanto só trava
    die('Nome é obrigatório.');
}

$mysqli->begin_transaction();

try {

    // ----------- INSERT/UPDATE em acl_levels -----------
    if ($id > 0) {
        $sql = "UPDATE acl_levels 
                SET nome = ?, descricao = ?, acesso_backend = ?, acesso_frontend = ?, ativo = ?
                WHERE id = ?";
        $st = $mysqli->prepare($sql);
        $st->bind_param('ssiiii', $nome, $descricao, $backend, $frontend, $ativo, $id);
        $st->execute();
        $st->close();
    } else {
        $sql = "INSERT INTO acl_levels (nome, descricao, acesso_backend, acesso_frontend, ativo)
                VALUES (?, ?, ?, ?, ?)";
        $st = $mysqli->prepare($sql);
        $st->bind_param('ssiii', $nome, $descricao, $backend, $frontend, $ativo);
        $st->execute();
        $id = (int)$st->insert_id;
        $st->close();
    }

    // Se por algum motivo não tiver id, aborta
    if ($id <= 0) {
        throw new Exception('Falha ao salvar nível (ID inválido).');
    }

    // ----------- LEGADO: salvar permissões por módulo (acl_permissions) -----------
    // Estrutura atual do form: mods[modulo][campo]
    $mods = $_POST['mods'] ?? [];

    // Apaga permissões antigas desse nível
    $del = $mysqli->prepare("DELETE FROM acl_permissions WHERE level_id = ?");
    $del->bind_param('i', $id);
    $del->execute();
    $del->close();

    if (!empty($mods) && is_array($mods)) {
        $sqlPerm = "INSERT INTO acl_permissions 
            (level_id, modulo, pode_ver, pode_criar, pode_editar, pode_excluir, pode_aprovar)
            VALUES (?, ?, ?, ?, ?, ?, ?)";
        $insPerm = $mysqli->prepare($sqlPerm);

        foreach ($mods as $modKey => $data) {
            // nome do módulo pode vir pelo campo 'modulo'
            $modulo = trim($data['modulo'] ?? $modKey);
            if ($modulo === '') {
                continue;
            }

            $pode_ver     = !empty($data['pode_ver'])     ? 1 : 0;
            $pode_criar   = !empty($data['pode_criar'])   ? 1 : 0;
            $pode_editar  = !empty($data['pode_editar'])  ? 1 : 0;
            $pode_excluir = !empty($data['pode_excluir']) ? 1 : 0;
            $pode_aprovar = !empty($data['pode_aprovar']) ? 1 : 0;

            // Se não tiver nenhuma permissão marcada, pode pular
            if (!$pode_ver && !$pode_criar && !$pode_editar && !$pode_excluir && !$pode_aprovar) {
                continue;
            }

            $insPerm->bind_param(
                'isiiiii',
                $id,
                $modulo,
                $pode_ver,
                $pode_criar,
                $pode_editar,
                $pode_excluir,
                $pode_aprovar
            );
            $insPerm->execute();
        }

        $insPerm->close();
    }

    // ----------- NOVO RBAC: salvar capabilities (acl_level_caps) -----------
    // O form manda caps[] com os slugs vindos dos manifests
    $caps = $_POST['caps'] ?? [];

    // Apaga capabilities antigas desse nível
    if ($delCap = $mysqli->prepare("DELETE FROM acl_level_caps WHERE level_id = ?")) {
        $delCap->bind_param('i', $id);
        $delCap->execute();
        $delCap->close();
    }

    // Insere capabilities novas
    if (!empty($caps) && is_array($caps)) {
        $sqlCap = "INSERT INTO acl_level_caps (level_id, cap_slug) VALUES (?, ?)";
        $insCap = $mysqli->prepare($sqlCap);

        foreach ($caps as $capSlug) {
            $capSlug = trim($capSlug);
            if ($capSlug === '') { continue; }

            $insCap->bind_param('is', $id, $capSlug);
            $insCap->execute();
        }

        $insCap->close();
    }

    $mysqli->commit();

    header('Location: ' . BASE_URL . '/pages/niveis_list.php?ok=1');
    exit;

} catch (Exception $e) {
    $mysqli->rollback();
    // Em produção, logar o erro e mostrar mensagem amigável
    echo "Erro ao salvar nível: " . htmlspecialchars($e->getMessage());
    exit;
}
