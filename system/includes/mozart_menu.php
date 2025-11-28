<?php
// NUNCA deixe espaços acima deste <?php

// Garante que ROOT_PATH esteja definido
if (!defined('ROOT_PATH')) {
    require_once __DIR__ . '/../config.php';
}

/**
 * Esse arquivo é só um "proxy" para o loader de manifests.
 * O mozart_get_menu() ORIGINAL já está definido em:
 *   system/includes/manifest/manifest_loader.php
 */

require_once ROOT_PATH . '/system/includes/manifest/manifest_loader.php';

// NÃO declarar mozart_get_menu() aqui!
// Vamos apenas reutilizar a implementação existente no loader.
