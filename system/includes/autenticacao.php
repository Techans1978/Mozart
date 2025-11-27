<?php
// NUNCA deixe espaços/linhas acima deste <?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Aqui assumo que ROOT_PATH e BASE_URL vêm do config.php
// Se não vierem, garante que o config foi incluído ANTES deste arquivo.

// 1) Loader dos manifests
require_once ROOT_PATH . '/system/includes/manifest/manifest_loader.php';
mozart_manifest_bootstrap();

/**
 * 2) Função de proteção de login (como você já usava)
 */
function proteger_pagina() {
    if (empty($_SESSION['user_id'])) {
        header('Location: ' . BASE_URL . '/pages/login.php');
        exit;
    }
}

/**
 * 3) Função que verifica se o usuário tem as capabilities necessárias.
 *    POR ENQUANTO pode ficar sempre true para não travar nada.
 *    Depois ligamos nas tabelas de nível/perfil.
 */
function usuario_tem_capabilities(array $requiredCaps): bool {
    // TODO: implementar de verdade usando RBAC no banco.
    // Exemplo futuro: checar $_SESSION['user_caps'] ou buscar no banco.
    return true;
}

/**
 * 4) Tela de acesso negado (simples, depois estilizamos)
 */
function mozart_acesso_negado() {
    http_response_code(403);
    echo '<h1>Acesso negado</h1>';
    echo '<p>Você não tem permissão para acessar esta funcionalidade.</p>';
    exit;
}

// 5) Primeiro: garante que o usuário está logado
proteger_pagina();

// 6) Depois: aplica RBAC por rota (exceto páginas públicas)
$currentPath = $_SERVER['SCRIPT_NAME'] ?? ''; 
// Ex.: /modules/helpdesk/pages/tickets_listar.php

// Páginas que NÃO devem passar pelo RBAC (só login/esqueci senha, etc.)
$rbac_skip = [
    '/pages/login.php',
    '/pages/esqueci_senha.php',
    // coloque aqui qualquer outra página pública
];

if (!in_array($currentPath, $rbac_skip, true)) {

    $routeMeta = mozart_get_route_meta($currentPath);

    if ($routeMeta && !empty($routeMeta['requires'])) {
        $requiredCaps = (array) $routeMeta['requires'];

        if (!usuario_tem_capabilities($requiredCaps)) {
            mozart_acesso_negado();
        }
    }
}
