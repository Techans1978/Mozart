<?php
// modules/bpm/designer-wizard.php
// Wrapper do Wizard: SEMPRE redireciona para o bpm_designer.php oficial
// Assim o Wizard nunca fica com "cópia" desatualizada do Designer.

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../../config.php';

// Mantém querystring original (from=wizard etc.)
$qs = $_SERVER['QUERY_STRING'] ?? '';
$sep = ($qs !== '') ? '&' : '';

// embed=1 (opcional) – no futuro, o bpm_designer.php pode usar isso pra esconder navbar/footer.
// Mesmo que hoje não use, não atrapalha.
$target = BASE_URL . '/modules/bpm/bpm_designer.php?embed=1' . $sep . $qs;

header('Location: ' . $target, true, 302);
exit;
