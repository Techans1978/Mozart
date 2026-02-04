<?php
// public/modules/forms/submissions_ver.php
ini_set('display_errors',1); ini_set('display_startup_errors',1); error_reporting(E_ALL);

require_once __DIR__ . '/../../config.php';
require_once ROOT_PATH . '/system/config/autenticacao.php';
require_once ROOT_PATH . '/system/config/connect.php';

if (!isset($conn) && isset($mysqli)) $conn = $mysqli;
if (!($conn instanceof mysqli)) die('Conexão MySQLi $conn não encontrada.');

if (session_status() !== PHP_SESSION_ACTIVE) session_start();
proteger_pagina();
$conn->set_charset('utf8mb4');

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$id = (int)($_GET['id'] ?? 0);
if ($id<=0) die('ID inválido.');

$stmt = $conn->prepare("SELECT * FROM forms_form_submission WHERE id=? LIMIT 1");
$stmt->bind_param("i",$id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();
if(!$row) die('Submissão não encontrada.');

$payload = json_decode($row['payload_json'] ?? '{}', true);
$meta    = json_decode($row['meta_json'] ?? '{}', true);

$payloadPretty = json_encode($payload, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT);
$metaPretty    = json_encode($meta,    JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT);
?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Submissão #<?php echo (int)$id; ?></title>
  <link rel="stylesheet" href="<?php echo h(BASE_URL); ?>/assets/css/bootstrap.min.css">
  <style>.mono{font-family:ui-monospace,Menlo,Consolas,monospace}</style>
</head>
<body class="bg-light">
<div class="container py-4">

  <div class="d-flex justify-content-between align-items-start mb-3">
    <div>
      <h4 class="mb-0">Submissão #<?php echo (int)$id; ?></h4>
      <div class="text-muted">
        Form: <span class="mono"><?php echo h($row['form_code']); ?></span> • v<?php echo (int)$row['version']; ?> •
        Status: <span class="badge bg-<?php echo ($row['status']==='processed'?'success':($row['status']==='error'?'danger':'secondary')); ?>"><?php echo h($row['status']); ?></span>
        <form method="post" action="<?php echo h(BASE_URL); ?>/modules/forms/actions/submissions_reprocess.php"
            onsubmit="return confirm('Reprocessar submissão #<?php echo (int)$id; ?>?');" class="mt-2">
        <input type="hidden" name="id" value="<?php echo (int)$id; ?>">
        <button class="btn btn-outline-warning w-100">Reprocessar</button>
      </form>
      </div>
    </div>
    <div class="d-flex gap-2">
      <a class="btn btn-outline-secondary" href="<?php echo h(BASE_URL); ?>/public/modules/forms/submissions_listar.php">Voltar</a>
      <a class="btn btn-outline-dark" href="<?php echo h(BASE_URL); ?>/modules/forms/actions/submissions_export.php?id=<?php echo (int)$id; ?>">Export (1)</a>
    </div>
  </div>

  <div class="row g-3">
    <div class="col-lg-4">
      <div class="card shadow-sm">
        <div class="card-body">
          <div><b>Form ID:</b> <?php echo (int)$row['form_id']; ?></div>
          <div><b>Criado por:</b> <span class="mono"><?php echo h($row['created_by'] ?? ''); ?></span></div>
          <div><b>Criado em:</b> <?php echo h($row['created_at']); ?></div>
          <hr>
          <form method="post" action="<?php echo h(BASE_URL); ?>/modules/forms/actions/submissions_status.php" class="d-flex gap-2">
            <input type="hidden" name="id" value="<?php echo (int)$id; ?>">
            <input type="hidden" name="back" value="">
            <select name="status" class="form-select">
              <?php foreach(['new','processed','error'] as $s): ?>
                <option value="<?php echo h($s); ?>" <?php echo ($row['status']===$s?'selected':''); ?>><?php echo h($s); ?></option>
              <?php endforeach; ?>
            </select>
            <button class="btn btn-primary">Salvar</button>
          </form>
          <hr>
          <form method="post" action="<?php echo h(BASE_URL); ?>/modules/forms/actions/submissions_delete.php"
                onsubmit="return confirm('Excluir submissão #<?php echo (int)$id; ?>?');">
            <input type="hidden" name="id" value="<?php echo (int)$id; ?>">
            <input type="hidden" name="back" value="">
            <button class="btn btn-outline-danger w-100">Excluir</button>
          </form>
        </div>
      </div>
    </div>

    <div class="col-lg-8">
      <div class="card shadow-sm mb-3">
        <div class="card-body">
          <h6 class="mb-2">Payload</h6>
          <pre class="mono small mb-0"><?php echo h($payloadPretty ?: "{}"); ?></pre>
        </div>
      </div>

      <div class="card shadow-sm">
        <div class="card-body">
          <h6 class="mb-2">Meta</h6>
          <pre class="mono small mb-0"><?php echo h($metaPretty ?: "{}"); ?></pre>
        </div>
      </div>
    </div>
  </div>

</div>
</body>
</html>
