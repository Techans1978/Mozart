<?php
// modules/dmn/api/health.php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../../config.php';
require_once ROOT_PATH . '/system/config/connect.php';

$checks = [];

$tables = [
  'moz_dmn_decision',
  'moz_dmn_version',
  'moz_dmn_category',
  'moz_dmn_testcase',
  'moz_dmn_exec_log'
];

foreach ($tables as $t) {
  $r = $conn->query("SHOW TABLES LIKE '{$t}'");
  $checks[$t] = ($r && $r->num_rows > 0);
}

echo json_encode([
  'ok' => !in_array(false, $checks, true),
  'tables' => $checks
], JSON_UNESCAPED_UNICODE);
