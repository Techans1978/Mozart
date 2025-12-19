<?php
// bpm/api/instance_start.php
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__, 3).'/config.php';
require_once ROOT_PATH.'/system/config/autenticacao.php';
require_once ROOT_PATH.'/system/config/connect.php';
if (session_status()===PHP_SESSION_NONE) session_start();
proteger_pagina();

require_once __DIR__ . '/../_lib/bpm_engine.php';

$uid = (int)($_SESSION['user_id'] ?? 0);
if ($uid <= 0) {
    echo json_encode(['ok'=>false, 'error'=>'Usuário não autenticado']); exit;
}

// Aceita JSON ou POST normal
$raw = file_get_contents('php://input');
$in = json_decode($raw, true);
if (!is_array($in)) {
    $in = $_POST;
}

$name    = isset($in['name']) ? trim($in['name']) : '';
$version = isset($in['version']) ? (int)$in['version'] : 1;
$businessKey = isset($in['business_key']) ? trim($in['business_key']) : null;
$vars    = isset($in['vars']) && is_array($in['vars']) ? $in['vars'] : [];

if ($name === '' || $version <= 0) {
    echo json_encode(['ok'=>false, 'error'=>'Parâmetros inválidos (name/version).']); exit;
}

try {
    $result = bpm_engine_start_instance_by_name($conn, $name, $version, $uid, $businessKey, $vars);
    echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Exception $e) {
    echo json_encode(['ok'=>false, 'error'=>$e->getMessage()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
