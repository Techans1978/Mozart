<?php

if (!defined('ROOT_PATH')) {
    // Ajusta se o seu ROOT_PATH estiver em outro lugar
    define('ROOT_PATH', dirname(__DIR__, 2));
}

if (!function_exists('mozart_debug_log')) {
    /**
     * Log simples para debug de manifests (pode depois apontar pra uma tabela de logs).
     */
    function mozart_debug_log(string $msg): void {
        // Você pode trocar isso por error_log() ou por gravação em tabela.
        // error_log('[MOZART MANIFEST] ' . $msg);
    }
}

/**
 * Estruturas globais carregadas pelo manifest_loader.
 * Você pode usar essas variáveis em qualquer lugar depois de chamar mozart_manifest_bootstrap().
 */
$GLOBALS['MOZART_MODULES']      = []; // slug => manifest array
$GLOBALS['MOZART_MENUS_BACK']   = []; // menu consolidado back
$GLOBALS['MOZART_MENUS_FRONT']  = []; // menu consolidado front
$GLOBALS['MOZART_ROUTES']       = []; // path => ['requires' => [...], 'module_slug' => ...]
$GLOBALS['MOZART_CAPABILITIES'] = []; // capability => ['label' => ..., 'module_slug' => ...]


/**
 * Carrega um único arquivo de manifest e faz validações básicas.
 */
function mozart_manifest_load_file(string $file, ?string $moduleSlugFromPath = null): ?array
{
    $data = include $file;

    if (!is_array($data)) {
        mozart_debug_log("Manifest inválido (não retornou array): {$file}");
        return null;
    }

    if (empty($data['slug'])) {
        // Se não tiver slug explícito, tenta deduzir do caminho
        if ($moduleSlugFromPath) {
            $data['slug'] = $moduleSlugFromPath;
        } else {
            mozart_debug_log("Manifest sem slug e sem slug dedutível: {$file}");
            return null;
        }
    }

    // Garante algumas chaves básicas
    $data['name']        = $data['name']        ?? $data['slug'];
    $data['area']        = $data['area']        ?? ['back' => true, 'front' => false];
    $data['version']     = $data['version']     ?? '1.0.0';
    $data['description'] = $data['description'] ?? '';
    $data['capabilities']= $data['capabilities']?? [];
    $data['menu']        = $data['menu']        ?? ['back' => [], 'front' => []];
    $data['routes']      = $data['routes']      ?? [];
    $data['role_defaults']= $data['role_defaults'] ?? [];

    return $data;
}

/**
 * Scaneia os manifests do core (system/includes/manifest/module_*.php).
 */
function mozart_manifest_scan_core(): array
{
    $coreDir = ROOT_PATH . '/system/includes/manifest';
    $pattern = $coreDir . '/module_*.php';

    $manifests = [];

    foreach (glob($pattern) as $file) {
        $basename = basename($file, '.php'); // ex: module_system
        $slugFromPath = preg_replace('/^module_/', '', $basename); // system, intranet, etc.

        $manifest = mozart_manifest_load_file($file, $slugFromPath);
        if ($manifest) {
            $manifests[] = $manifest;
        }
    }

    return $manifests;
}


function mozart_manifest_scan_modules(): array
{
    $modulesDir = ROOT_PATH . '/modules';
    $manifests  = [];

    if (!is_dir($modulesDir)) {
        return [];
    }

    $dirs = glob($modulesDir . '/*', GLOB_ONLYDIR);
    foreach ($dirs as $dir) {
        $moduleSlug = basename($dir); // helpdesk, gestao_ativos, wpp_chat, etc.
        $pattern    = $dir . '/module_*.php';

        foreach (glob($pattern) as $file) {
            $basename      = basename($file, '.php');  // ex: module_helpdesk
            $slugFromPath  = preg_replace('/^module_/', '', $basename) ?: $moduleSlug;

            $manifest = mozart_manifest_load_file($file, $slugFromPath);
            if ($manifest) {
                $manifests[] = $manifest;
            }
        }
    }

    return $manifests;
}

/**
 * Faz o merge de todos os manifests em estruturas globais:
 *  - MOZART_MODULES
 *  - MOZART_MENUS_BACK
 *  - MOZART_MENUS_FRONT
 *  - MOZART_ROUTES
 *  - MOZART_CAPABILITIES
 */
function mozart_manifest_bootstrap(): void
{
    static $alreadyLoaded = false;
    if ($alreadyLoaded) {
        return;
    }
    $alreadyLoaded = true;

    $coreManifests    = mozart_manifest_scan_core();
    $moduleManifests  = mozart_manifest_scan_modules();

    $all = array_merge($coreManifests, $moduleManifests);

    foreach ($all as $manifest) {
        $slug = $manifest['slug'];

        // Evita duplicidade de slug
        if (isset($GLOBALS['MOZART_MODULES'][$slug])) {
            mozart_debug_log("Slug duplicado de módulo: {$slug}");
            continue;
        }

        $GLOBALS['MOZART_MODULES'][$slug] = $manifest;

        // Menus
        if (!empty($manifest['menu']['back'])) {
            $GLOBALS['MOZART_MENUS_BACK'][$slug] = $manifest['menu']['back'];
        }
        if (!empty($manifest['menu']['front'])) {
            $GLOBALS['MOZART_MENUS_FRONT'][$slug] = $manifest['menu']['front'];
        }

        // Rotas
        if (!empty($manifest['routes']) && is_array($manifest['routes'])) {
            foreach ($manifest['routes'] as $route) {
                if (empty($route['path'])) {
                    continue;
                }
                $path = $route['path'];
                $requires = $route['requires'] ?? [];

                $GLOBALS['MOZART_ROUTES'][$path] = [
                    'requires'    => (array)$requires,
                    'module_slug' => $slug,
                ];
            }
        }

        // Capabilities
        if (!empty($manifest['capabilities']) && is_array($manifest['capabilities'])) {
            foreach ($manifest['capabilities'] as $cap => $meta) {
                if (is_string($meta)) {
                    // Se vier só string, trata como label
                    $meta = ['label' => $meta];
                }
                $meta['module_slug'] = $slug;
                $GLOBALS['MOZART_CAPABILITIES'][$cap] = $meta;
            }
        }
    }
}

/**
 * Helper para obter o menu consolidado (back ou front).
 *
 * @param string $area 'back' ou 'front'
 * @return array
 */
function mozart_get_menu(string $area = 'back'): array
{
    mozart_manifest_bootstrap();

    if ($area === 'front') {
        return $GLOBALS['MOZART_MENUS_FRONT'] ?? [];
    }

    return $GLOBALS['MOZART_MENUS_BACK'] ?? [];
}

/**
 * Helper simples para obter as capabilities consolidadas.
 *
 * @return array capability => meta
 */
function mozart_get_capabilities(): array
{
    mozart_manifest_bootstrap();
    return $GLOBALS['MOZART_CAPABILITIES'] ?? [];
}

/**
 * Retorna metadados da rota, incluindo as capabilities exigidas.
 *
 * @param string $path caminho relativo, ex: '/modules/helpdesk/pages/tickets_listar.php'
 * @return array|null
 */
function mozart_get_route_meta(string $path): ?array
{
    mozart_manifest_bootstrap();
    return $GLOBALS['MOZART_ROUTES'][$path] ?? null;
}
