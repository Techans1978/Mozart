<?php
// public/modules/forms/wizard/5_preview.php — Teste de Dataset
ini_set('display_errors',1); ini_set('display_startup_errors',1); error_reporting(E_ALL);

require_once __DIR__ . '/../../../config.php';
require_once ROOT_PATH . '/system/config/autenticacao.php';
require_once ROOT_PATH . '/system/config/connect.php';

if (!isset($conn) && isset($mysqli)) { $conn = $mysqli; }
if (!($conn instanceof mysqli)) die('Conexão MySQLi $conn não encontrada.');
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
proteger_pagina();

$conn->set_charset('utf8mb4');
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$formId = (int)($_GET['form_id'] ?? 0);
$datasetId = (string)($_GET['dataset_id'] ?? '');
if ($formId<=0 || $datasetId==='') die('Parâmetros inválidos.');

$stmt = $conn->prepare("SELECT current_version, code FROM forms_form WHERE id=? LIMIT 1");
$stmt->bind_param("i",$formId);
$stmt->execute();
$form = $stmt->get_result()->fetch_assoc();
$stmt->close();
if(!$form) die('Formulário não encontrado.');

$ver = max(1,(int)$form['current_version']);

$stmt = $conn->prepare("SELECT schema_json, status FROM forms_form_version WHERE form_id=? AND version=? LIMIT 1");
$stmt->bind_param("ii",$formId,$ver);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();
if(!$row) die('Versão não encontrada.');

$schema = json_decode($row['schema_json'], true);
if (!is_array($schema)) die('Schema inválido.');

$datasets = $schema['globals']['datasets'] ?? [];
if (!is_array($datasets)) $datasets = [];

$ds = null;
foreach($datasets as $d){
  if ((string)($d['id'] ?? '') === $datasetId) { $ds = $d; break; }
}
if(!$ds) die('Dataset não encontrado.');

$mode = (string)($ds['mode'] ?? '');
$key = (string)($ds['key'] ?? 'value');
$label = (string)($ds['label'] ?? 'label');

$options = [];
if ($mode === 'static') {
  $opts = $ds['options'] ?? [];
  if (is_array($opts)) $options = $opts;
}

?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Dataset Preview</title>
  <link rel="stylesheet" href="<?php echo h(BASE_URL); ?>/assets/css/bootstrap.min.css">
  <style>.mono{font-family:ui-monospace,Menlo,Consolas,monospace}</style>
</head>
<body class="bg-light">
<div class="container py-4">

  <h4 class="mb-1">Preview Dataset</h4>
  <div class="text-muted mb-3">
    Form: <span class="mono"><?php echo h($form['code']); ?></span> •
    Dataset: <span class="mono"><?php echo h($datasetId); ?></span> •
    mode: <span class="mono"><?php echo h($mode); ?></span>
  </div>

  <div class="card shadow-sm mb-3">
    <div class="card-body">
      <h6 class="mb-2">Config</h6>
      <pre class="mono small mb-0"><?php echo h(json_encode($ds, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT)); ?></pre>
    </div>
  </div>

  <?php if ($mode === 'static'): ?>
    <div class="card shadow-sm">
      <div class="card-body">
        <h6 class="mb-2">Opções (<?php echo count($options); ?>)</h6>
        <div class="table-responsive">
          <table class="table table-sm table-hover align-middle">
            <thead>
              <tr><th><?php echo h($key); ?></th><th><?php echo h($label); ?></th></tr>
            </thead>
            <tbody>
              <?php foreach($options as $o): ?>
                <tr>
                  <td class="mono"><?php echo h($o[$key] ?? ($o['value'] ?? '')); ?></td>
                  <td><?php echo h($o[$label] ?? ($o['label'] ?? '')); ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  <?php else: ?>
    <div class="alert alert-info">
      Este preview executa apenas <b>mode=static</b> por enquanto. Para endpoint/sql, o Step 6 vai implementar execução com segurança (allowlist, cache, limites).
    </div>
  <?php endif; ?>

</div>
</body>
</html>
