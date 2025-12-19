<?php
// modules/datasets/health.php
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config.php';
require_once ROOT_PATH . '/system/config/connect.php';

$start = microtime(true);

$out = [
  'ok' => true,
  'module' => 'datasets',
  'version' => '1.0.0',
  'checks' => [],
  'time_ms' => 0
];

/* ========= Check 1: DB conexão ========= */
try {
  if (!$conn || !$conn->ping()) {
    throw new Exception('DB não respondeu ao ping');
  }
  $out['checks']['db'] = 'ok';
} catch (Throwable $e) {
  $out['ok'] = false;
  $out['checks']['db'] = 'error';
  $out['error'] = 'Falha DB: '.$e->getMessage();
}

/* ========= Check 2: Tabelas essenciais ========= */
$tables = [
  'moz_ds_dataset',
  'moz_ds_version',
  'moz_ds_exec_log',
  'moz_ds_testcase'
];

$missing = [];
foreach ($tables as $t) {
  try {
    $r = $conn->query("SHOW TABLES LIKE '{$t}'");
    if (!$r || $r->num_rows === 0) {
      $missing[] = $t;
    }
  } catch (Throwable $e) {
    $missing[] = $t;
  }
}

if ($missing) {
  $out['ok'] = false;
  $out['checks']['tables'] = 'missing';
  $out['missing_tables'] = $missing;
} else {
  $out['checks']['tables'] = 'ok';
}

/* ========= Check 3: API run.php acessível ========= */
$runPath = BASE_URL . '/modules/datasets/api/run.php';

$out['checks']['run_endpoint'] = [
  'url' => $runPath,
  'status' => 'skipped'
];

// Não executa run de verdade (evita efeitos colaterais)
// Apenas valida que o arquivo existe
$runFile = __DIR__ . '/api/run.php';
if (!file_exists($runFile)) {
  $out['ok'] = false;
  $out['checks']['run_endpoint']['status'] = 'missing';
} else {
  $out['checks']['run_endpoint']['status'] = 'ok';
}

/* ========= Final ========= */
$out['time_ms'] = (int)((microtime(true) - $start) * 1000);

echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
