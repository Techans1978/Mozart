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
    http_response_code(401);
    echo json_encode(['ok'=>false, 'error'=>'Usuário não autenticado'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// Aceita JSON ou POST normal
$raw = file_get_contents('php://input');
$in = json_decode($raw, true);
if (!is_array($in)) {
    $in = $_POST;
}

$name    = isset($in['name']) ? trim((string)$in['name']) : '';
$version = isset($in['version']) ? (int)$in['version'] : 1;

$businessKey = isset($in['business_key']) ? trim((string)$in['business_key']) : null;
if ($businessKey === '') $businessKey = null;

// Vars (aceita array ou JSON string)
$vars = [];
if (isset($in['vars'])) {
    if (is_array($in['vars'])) {
        $vars = $in['vars'];
    } elseif (is_string($in['vars']) && trim($in['vars']) !== '') {
        $tmp = json_decode($in['vars'], true);
        if (is_array($tmp)) $vars = $tmp;
    }
}

if ($name === '' || $version <= 0) {
    http_response_code(400);
    echo json_encode(['ok'=>false, 'error'=>'Parâmetros inválidos (name/version).'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

try {
    $result = bpm_engine_start_instance_by_name($conn, $name, $version, $uid, $businessKey, $vars);
    echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok'=>false, 'error'=>$e->getMessage()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
