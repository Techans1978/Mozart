<?php
// NUNCA deixe espaços acima deste <?php

/**
 * Renderiza a árvore completa de menus no sidebar.
 */
function renderSidebarMenu(array $items): void
{
    foreach ($items as $item) {
        renderSidebarItem($item);
    }
}

/**
 * Detecta se o item deve ser marcado como ativo.
 */
function mozart_menu_is_active(string $url): bool
{
    if ($url === '#' || trim($url) === '') {
        return false;
    }

    $current = $_SERVER['REQUEST_URI'] ?? '';
    $urlPath = parse_url($url, PHP_URL_PATH);

    return $urlPath && str_contains($current, $urlPath);
}

/**
 * Verifica se algum filho (ou neto) está ativo
 */
function mozart_menu_children_active(array $children): bool
{
    foreach ($children as $child) {
        $childUrl  = $child['url'] ?? '#';
        $grandsons = $child['children'] ?? [];

        if (mozart_menu_is_active($childUrl)) {
            return true;
        }

        if (!empty($grandsons) && mozart_menu_children_active($grandsons)) {
            return true;
        }
    }
    return false;
}

/**
 * Renderiza um item individual, podendo ter submenus.
 */
function renderSidebarItem(array $item, int $depth = 1): void
{
    // Divider
    if (!empty($item['divider'])) {
        echo '<li class="nav-divider"></li>';
        return;
    }

    $label    = $item['label']    ?? 'Item';
    $icon     = $item['icon']     ?? '';
    $url      = $item['url']      ?? '#';
    $children = $item['children'] ?? [];
    $hasChild = !empty($children);

    // Ativo: próprio link ou algum filho ativo
    $selfActive   = mozart_menu_is_active($url);
    $childActive  = $hasChild ? mozart_menu_children_active($children) : false;
    $isActive     = $selfActive || $childActive;

    $liClasses = [];
    if ($isActive) {
        $liClasses[] = 'active';
    }
    if ($hasChild) {
        $liClasses[] = 'has-children';
    }

    $liClassAttr = $liClasses ? ' class="'.implode(' ', $liClasses).'"' : '';

    // UL de children conforme nível
    $ulClass = match ($depth) {
        1       => 'nav nav-second-level',
        2       => 'nav nav-third-level',
        default => 'nav nav-level-' . $depth,
    };

    echo '<li' . $liClassAttr . '>';

    // Link do item:
    // - se tem filhos e não tem URL definida, usamos "#"
    // - se tem URL, usamos a URL normalmente
    $href = ($hasChild && ($url === '#' || trim($url) === ''))
        ? '#'
        : $url;

    echo '<a href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '">';

    // Ícone (apenas no primeiro nível, para não poluir submenus)
    if ($icon && $depth === 1) {
        echo '<i class="' . htmlspecialchars($icon, ENT_QUOTES, 'UTF-8') . '"></i> ';
    }

    echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8');

    if ($hasChild) {
        echo ' <span class="fa arrow"></span>';
    }

    echo '</a>';

    // Renderiza filhos
    if ($hasChild) {
        echo '<ul class="' . $ulClass . '">';
        foreach ($children as $child) {
            renderSidebarItem($child, $depth + 1);
        }
        echo '</ul>';
    }

    echo '</li>';
}
