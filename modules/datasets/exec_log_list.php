<?php
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../../config.php';
require_once ROOT_PATH . '/system/config/connect.php';
require_once ROOT_PATH . '/system/config/autenticacao.php';

if (session_status()===PHP_SESSION_NONE) session_start();
proteger_pagina();

$dataset_key = trim($_GET['dataset_key'] ?? '');
$status = trim($_GET['status'] ?? '');
$caller = trim($_GET['caller'] ?? '');
$limit = (int)($_GET['limit'] ?? 100);
if ($limit<=0) $limit = 100;
if ($limit>500) $limit = 500;

$where = [];
$params = [];
$types = '';

if ($dataset_key!==''){ $where[]="dataset_key=?"; $params[]=$dataset_key; $types.='s'; }
if ($status!==''){ $where[]="status=?"; $params[]=$status; $types.='s'; }
if ($caller!==''){ $where[]="caller LIKE ?"; $params[]='%'.$caller.'%'; $types.='s'; }

$sql = "SELECT id, dataset_key, version_id, exec_ms, is_cached, status, error_msg, params_json, result_rows_count, caller, created_at
        FROM moz_ds_exec_log";
if ($where) $sql .= " WHERE ".implode(" AND ",$where);
$sql .= " ORDER BY created_at DESC LIMIT ".$limit;

$st = $conn->prepare($sql);
if ($params) $st->bind_param($types, ...$params);
$st->execute();
$res = $st->get_result();

$items=[];
while($r=$res->fetch_assoc()) $items[]=$r;
$st->close();

echo json_encode(['ok'=>true,'items'=>$items], JSON_UNESCAPED_UNICODE);
