<?php
// modules/forms/actions/submissions_export.php
ini_set('display_errors',1); ini_set('display_startup_errors',1); error_reporting(E_ALL);

require_once __DIR__ . '/../../../config.php';
require_once ROOT_PATH . '/system/config/autenticacao.php';
require_once ROOT_PATH . '/system/config/connect.php';

if (!isset($conn) && isset($mysqli)) $conn = $mysqli;
if (!($conn instanceof mysqli)) die('Conexão MySQLi $conn não encontrada.');

if (session_status() !== PHP_SESSION_ACTIVE) session_start();
proteger_pagina();
$conn->set_charset('utf8mb4');

$id = (int)($_GET['id'] ?? 0);

// se id, exporta 1
if ($id > 0) {
  $stmt = $conn->prepare("SELECT id, form_code, version, status, created_by, created_at, payload_json, meta_json
                          FROM forms_form_submission WHERE id=? LIMIT 1");
  $stmt->bind_param("i",$id);
  $stmt->execute();
  $row = $stmt->get_result()->fetch_assoc();
  $stmt->close();

  if (!$row) { http_response_code(404); die('Não encontrado'); }

  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename="submission_'.$id.'.csv"');

  $out = fopen('php://output','w');
  fputcsv($out, array_keys($row));
  fputcsv($out, array_values($row));
  fclose($out);
  exit;
}

// senão, exporta filtrado (mesma lógica do listar)
$form_code = trim((string)($_GET['form_code'] ?? ''));
$status    = trim((string)($_GET['status'] ?? ''));
$date_from = trim((string)($_GET['from'] ?? ''));
$date_to   = trim((string)($_GET['to'] ?? ''));
$created_by= trim((string)($_GET['created_by'] ?? ''));

$where=[]; $types=''; $params=[];
if ($form_code!==''){ $where[]="form_code=?"; $types.='s'; $params[]=$form_code; }
if ($status!==''){ $where[]="status=?"; $types.='s'; $params[]=$status; }
if ($created_by!=='' && ctype_digit($created_by)){ $where[]="created_by=?"; $types.='i'; $params[]=(int)$created_by; }
if ($date_from!==''){ $where[]="created_at>=?"; $types.='s'; $params[]=$date_from.' 00:00:00'; }
if ($date_to!==''){ $where[]="created_at<=?"; $types.='s'; $params[]=$date_to.' 23:59:59'; }

$whereSql = count($where) ? ('WHERE '.implode(' AND ',$where)) : '';

$sql = "SELECT id, form_code, version, status, created_by, created_at, payload_json
        FROM forms_form_submission
        $whereSql
        ORDER BY id DESC
        LIMIT 5000"; // trava pra não explodir

$stmt = $conn->prepare($sql);
if ($types) $stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="submissions_export.csv"');

$out = fopen('php://output','w');
$headerDone=false;

while($row=$res->fetch_assoc()){
  if(!$headerDone){
    fputcsv($out, array_keys($row));
    $headerDone=true;
  }
  fputcsv($out, array_values($row));
}
fclose($out);
$stmt->close();
exit;
