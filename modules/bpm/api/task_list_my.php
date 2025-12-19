<?php
header('Content-Type: application/json; charset=utf-8');
require_once dirname(__DIR__, 3).'/config.php';
require_once ROOT_PATH.'/system/config/autenticacao.php';
require_once ROOT_PATH.'/system/config/connect.php';
if (session_status()===PHP_SESSION_NONE) session_start();
proteger_pagina();

$uid = (int)($_SESSION['user_id'] ?? 0);
$items = [];

// helper para checar se a tabela existe
$exists = function($t) use ($conn){
  $t = $conn->real_escape_string($t);
  $rs = $conn->query("SHOW TABLES LIKE '$t'");
  return $rs && $rs->num_rows > 0;
};

// Se não tiver usuário logado ou tabela de tarefas, retorna vazio
if ($uid <= 0 || !$exists('bpm_task') || !$exists('bpm_instance')) {
  echo json_encode(['ok' => true, 'items' => []], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
  exit;
}

// Status que queremos mostrar na caixa de tarefas
$statuses = "'ready','claimed','in_progress','error'";

// 1) Caminho "novo": bpm_process_version + bpm_process
if ($exists('bpm_process_version') && $exists('bpm_process')) {

  $sql = "
    SELECT
      t.id,
      t.instance_id,
      t.node_id,
      t.type,
      t.assignee_user_id,
      t.status,
      t.due_at,
      t.priority,
      p.name AS process_name
    FROM bpm_task t
    JOIN bpm_instance i       ON i.id = t.instance_id
    JOIN bpm_process_version pv ON pv.id = i.version_id
    JOIN bpm_process p        ON p.id = pv.process_id
    WHERE t.status IN ($statuses)
      AND (t.assignee_user_id IS NULL OR t.assignee_user_id = $uid)
    ORDER BY
      t.due_at IS NULL ASC,
      t.due_at ASC,
      t.created_at ASC
    LIMIT 200
  ";

  if ($rs = $conn->query($sql)) {
    while ($r = $rs->fetch_assoc()) {
      // compat com o front: campo "name" (ainda vazio, usa node_id como fallback)
      $r['name'] = $r['name'] ?? null;
      $items[] = $r;
    }
  }

// 2) Caminho "legado": bpm_processes (designer antigo)
} elseif ($exists('bpm_processes')) {

  $sql = "
    SELECT
      t.id,
      t.instance_id,
      t.node_id,
      t.type,
      t.assignee_user_id,
      t.status,
      t.due_at,
      t.priority,
      bp.name AS process_name,
      bp.version
    FROM bpm_task t
    JOIN bpm_instance i ON i.id = t.instance_id
    JOIN bpm_processes bp ON bp.id = i.version_id
    WHERE t.status IN ($statuses)
      AND (t.assignee_user_id IS NULL OR t.assignee_user_id = $uid)
    ORDER BY
      t.due_at IS NULL ASC,
      t.due_at ASC,
      t.created_at ASC
    LIMIT 200
  ";

  if ($rs = $conn->query($sql)) {
    while ($r = $rs->fetch_assoc()) {
      $r['name'] = $r['name'] ?? null;
      $items[] = $r;
    }
  }

// 3) Fallback: só bpm_task + bpm_instance (sem nome de processo)
} else {

  $sql = "
    SELECT
      t.id,
      t.instance_id,
      t.node_id,
      t.type,
      t.assignee_user_id,
      t.status,
      t.due_at,
      t.priority,
      NULL AS process_name
    FROM bpm_task t
    JOIN bpm_instance i ON i.id = t.instance_id
    WHERE t.status IN ($statuses)
      AND (t.assignee_user_id IS NULL OR t.assignee_user_id = $uid)
    ORDER BY
      t.due_at IS NULL ASC,
      t.due_at ASC,
      t.created_at ASC
    LIMIT 200
  ";

  if ($rs = $conn->query($sql)) {
    while ($r = $rs->fetch_assoc()) {
      $r['name'] = $r['name'] ?? null;
      $items[] = $r;
    }
  }
}

echo json_encode(
  ['ok' => true, 'items' => $items],
  JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
);
