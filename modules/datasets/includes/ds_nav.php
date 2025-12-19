<?php
$current = basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
function ds_nav_active($f,$c){ return $f===$c ? 'active' : ''; }
?>
<style>
  .ds-nav{display:flex;gap:8px;align-items:center;flex-wrap:wrap;padding:10px 12px;border-bottom:1px solid #e5e7eb;background:#fff;position:sticky;top:0;z-index:20}
  .ds-nav .brand{font-weight:900}
  .ds-nav .spacer{flex:1}
  .ds-nav a{text-decoration:none;border:1px solid #d1d5db;background:#fff;color:#111;padding:8px 12px;border-radius:10px;font-weight:800}
  .ds-nav a:hover{background:#f3f4f6}
  .ds-nav a.primary{background:#111827;border-color:#111827;color:#fff}
  .ds-nav a.active{box-shadow:0 0 0 2px rgba(17,24,39,.12) inset}
</style>
<div class="ds-nav">
  <div class="brand">Mozart — Datasets</div>
  <a class="<?=ds_nav_active('ds_list.php',$current)?>" href="<?=BASE_URL?>/modules/datasets/ds_list.php">Listar</a>
  <a class="<?=ds_nav_active('ds_editor.php',$current)?> primary" href="<?=BASE_URL?>/modules/datasets/ds_editor.php">+ Novo</a>
  <a class="<?=ds_nav_active('ds_runner.php',$current)?>" href="<?=BASE_URL?>/modules/datasets/ds_runner.php">Runner</a>
  <div class="spacer"></div>
  <a href="<?=BASE_URL?>/modules/datasets/index.php">Início</a>
</div>
