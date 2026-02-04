<?php
// public/modules/forms/dashboard.php
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

$days = 14;

// séries por dia/status
$stmt = $conn->prepare("
  SELECT DATE(created_at) d, status, COUNT(*) c
  FROM forms_form_submission
  WHERE created_at >= (CURDATE() - INTERVAL ? DAY)
  GROUP BY DATE(created_at), status
  ORDER BY d ASC
");
$stmt->bind_param("i",$days);
$stmt->execute();
$series = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// top forms volume
$stm2 = $conn->prepare("
  SELECT form_code, COUNT(*) c
  FROM forms_form_submission
  WHERE created_at >= (CURDATE() - INTERVAL ? DAY)
  GROUP BY form_code
  ORDER BY c DESC
  LIMIT 10
");
$stm2->bind_param("i",$days);
$stm2->execute();
$top = $stm2->get_result()->fetch_all(MYSQLI_ASSOC);
$stm2->close();

// top errors
$stm3 = $conn->prepare("
  SELECT form_code, COUNT(*) c
  FROM forms_form_submission
  WHERE created_at >= (CURDATE() - INTERVAL ? DAY)
    AND status='error'
  GROUP BY form_code
  ORDER BY c DESC
  LIMIT 10
");
$stm3->bind_param("i",$days);
$stm3->execute();
$topErr = $stm3->get_result()->fetch_all(MYSQLI_ASSOC);
$stm3->close();

// resumo total
$stm4 = $conn->prepare("
  SELECT status, COUNT(*) c
  FROM forms_form_submission
  WHERE created_at >= (CURDATE() - INTERVAL ? DAY)
  GROUP BY status
");
$stm4->bind_param("i",$days);
$stm4->execute();
$sum = $stm4->get_result()->fetch_all(MYSQLI_ASSOC);
$stm4->close();

$sumMap = ['new'=>0,'processed'=>0,'error'=>0];
foreach($sum as $r){ $sumMap[$r['status']] = (int)$r['c']; }
?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Forms • Dashboard</title>
  <link rel="stylesheet" href="<?php echo h(BASE_URL); ?>/assets/css/bootstrap.min.css">
  <style>.mono{font-family:ui-monospace,Menlo,Consolas,monospace}</style>
</head>
<body class="bg-light">
<div class="container py-4">

  <div class="d-flex justify-content-between align-items-start mb-3">
    <div>
      <h4 class="mb-0">Dashboard • Forms</h4>
      <div class="text-muted">Saúde das submissões (últimos <?php echo (int)$days; ?> dias)</div>
    </div>
    <div class="d-flex gap-2">
      <a class="btn btn-outline-secondary" href="<?php echo h(BASE_URL); ?>/public/modules/forms/forms_listar.php">Voltar</a>
      <a class="btn btn-outline-dark" href="<?php echo h(BASE_URL); ?>/public/modules/forms/submissions_listar.php">Submissões</a>
      <a class="btn btn-outline-primary" href="<?php echo h(BASE_URL); ?>/public/modules/forms/audit_listar.php">Auditoria</a>
    </div>
  </div>

  <div class="row g-3 mb-3">
    <div class="col-md-4">
      <div class="card shadow-sm"><div class="card-body">
        <div class="text-muted">NEW</div>
        <div class="display-6"><?php echo (int)$sumMap['new']; ?></div>
      </div></div>
    </div>
    <div class="col-md-4">
      <div class="card shadow-sm"><div class="card-body">
        <div class="text-muted">PROCESSED</div>
        <div class="display-6"><?php echo (int)$sumMap['processed']; ?></div>
      </div></div>
    </div>
    <div class="col-md-4">
      <div class="card shadow-sm"><div class="card-body">
        <div class="text-muted">ERROR</div>
        <div class="display-6 text-danger"><?php echo (int)$sumMap['error']; ?></div>
      </div></div>
    </div>
  </div>

  <div class="card shadow-sm mb-3">
    <div class="card-body">
      <h6 class="mb-2">Série por dia/status</h6>
      <div class="table-responsive">
        <table class="table table-sm table-hover align-middle">
          <thead><tr><th>Dia</th><th>Status</th><th>Qtd</th></tr></thead>
          <tbody>
            <?php foreach($series as $r): ?>
              <tr>
                <td class="mono"><?php echo h($r['d']); ?></td>
                <td class="mono"><?php echo h($r['status']); ?></td>
                <td class="mono"><?php echo (int)$r['c']; ?></td>
              </tr>
            <?php endforeach; ?>
            <?php if(!count($series)): ?>
              <tr><td colspan="3" class="text-muted">Sem dados no período.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
      <div class="text-muted small">Depois a gente troca por gráfico (Chart.js) se quiser.</div>
    </div>
  </div>

  <div class="row g-3">
    <div class="col-lg-6">
      <div class="card shadow-sm"><div class="card-body">
        <h6 class="mb-2">Top 10 Forms (volume)</h6>
        <table class="table table-sm">
          <thead><tr><th>Form</th><th class="text-end">Qtd</th></tr></thead>
          <tbody>
            <?php foreach($top as $r): ?>
              <tr>
                <td class="mono"><?php echo h($r['form_code']); ?></td>
                <td class="mono text-end"><?php echo (int)$r['c']; ?></td>
              </tr>
            <?php endforeach; ?>
            <?php if(!count($top)): ?><tr><td colspan="2" class="text-muted">Sem dados.</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div></div>
    </div>
    <div class="col-lg-6">
      <div class="card shadow-sm"><div class="card-body">
        <h6 class="mb-2">Top 10 Forms (erros)</h6>
        <table class="table table-sm">
          <thead><tr><th>Form</th><th class="text-end">Erros</th></tr></thead>
          <tbody>
            <?php foreach($topErr as $r): ?>
              <tr>
                <td class="mono"><?php echo h($r['form_code']); ?></td>
                <td class="mono text-end text-danger"><?php echo (int)$r['c']; ?></td>
              </tr>
            <?php endforeach; ?>
            <?php if(!count($topErr)): ?><tr><td colspan="2" class="text-muted">Sem erros 🎉</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div></div>
    </div>
  </div>

</div>
</body>
</html>
