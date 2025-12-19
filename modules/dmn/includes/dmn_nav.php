<?php
// modules/dmn/includes/dmn_nav.php
// Uso: include_once __DIR__.'/includes/dmn_nav.php';

if (!defined('BASE_URL')) {
  // se por algum motivo BASE_URL não existir, evita warning
  define('BASE_URL', '');
}

$current = basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

function dmn_nav_active(string $file, string $current): string {
  return $file === $current ? 'active' : '';
}
?>
<style>
  .dmn-nav{
    display:flex; gap:8px; align-items:center; flex-wrap:wrap;
    padding:10px 12px; border-bottom:1px solid #e5e7eb; background:#fff;
    position:sticky; top:0; z-index:20;
  }
  .dmn-nav .brand{ font-weight:900; }
  .dmn-nav .spacer{ flex:1; }
  .dmn-nav a{
    text-decoration:none;
    border:1px solid #d1d5db; background:#fff; color:#111;
    padding:8px 12px; border-radius:10px; font-weight:800;
  }
  .dmn-nav a:hover{ background:#f3f4f6; }
  .dmn-nav a.primary{ background:#111827; border-color:#111827; color:#fff; }
  .dmn-nav a.active{ box-shadow:0 0 0 2px rgba(17,24,39,.12) inset; }
</style>

<div class="dmn-nav">
  <div class="brand">Mozart — DMN</div>

  <a class="<?= dmn_nav_active('dmn_list.php',$current) ?>" href="<?= BASE_URL ?>/modules/dmn/dmn_list.php">Biblioteca</a>
  <a class="<?= dmn_nav_active('dmn_editor.php',$current) ?> primary" href="<?= BASE_URL ?>/modules/dmn/dmn_editor.php">+ Nova decisão</a>
  <a class="<?= dmn_nav_active('dmn_categories.php',$current) ?>" href="<?= BASE_URL ?>/modules/dmn/dmn_categories.php">Categorias</a>
  <a class="<?= dmn_nav_active('dmn_runner.php',$current) ?>" href="<?= BASE_URL ?>/modules/dmn/dmn_runner.php">Runner</a>

  <div class="spacer"></div>

  <a href="<?= BASE_URL ?>/modules/dmn/index.php">Início DMN</a>
</div>
