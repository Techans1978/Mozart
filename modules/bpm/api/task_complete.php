<?php
// public/modules/bpm/api/task_complete.php
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__, 3).'/config.php';
require_once ROOT_PATH.'/system/config/autenticacao.php';
require_once ROOT_PATH.'/system/config/connect.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
proteger_pagina();

require_once __DIR__ . '/../_lib/bpm_engine.php';

$uid = (int)($_SESSION['user_id'] ?? 0);
if ($uid <= 0) {
    echo json_encode([
        'ok'    => false,
        'error' => 'Usuário não autenticado.'
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// Aceita JSON ou POST normal
$raw = file_get_contents('php://input');
$in  = json_decode($raw, true);
if (!is_array($in)) {
    $in = $_POST;
}

// ID da tarefa
$taskId = isset($in['id']) ? (int)$in['id'] : 0;
if ($taskId <= 0) {
    echo json_encode([
        'ok'    => false,
        'error' => 'Parâmetro "id" da tarefa é obrigatório.'
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// Payload (dados do formulário)
// Contrato preferido: { id: 123, payload: { campo1: "x", campo2: 10 } }
$payload = [];
if (isset($in['payload']) && is_array($in['payload'])) {
    $payload = $in['payload'];
} else {
    // Fallback: tudo que não for 'id' entra como payload
    foreach ($in as $k => $v) {
        if ($k === 'id') continue;
        $payload[$k] = $v;
    }
}

try {
    $result = bpm_engine_advance_from_task($conn, $taskId, $uid, $payload);

    if (empty($result['ok'])) {
        $msg = $result['error'] ?? 'Falha ao avançar tarefa.';
        echo json_encode([
            'ok'    => false,
            'error' => $msg
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Exception $e) {
    echo json_encode([
        'ok'    => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
