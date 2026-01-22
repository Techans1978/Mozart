<?php
// modules/bpm/api/task_get.php
// Retorna detalhes de uma tarefa + dados básicos do processo/instância.

header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__, 3).'/config.php';
require_once ROOT_PATH.'/system/config/autenticacao.php';
require_once ROOT_PATH.'/system/config/connect.php';
if (session_status()===PHP_SESSION_NONE) session_start();
proteger_pagina();

$uid = (int)($_SESSION['user_id'] ?? 0);
$id  = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id<=0) {
  http_response_code(400);
  echo json_encode(['ok'=>false,'error'=>'Parâmetro id é obrigatório.'], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
  exit;
}

// helper: existe tabela?
$exists = function($t) use ($conn){
  $t = $conn->real_escape_string($t);
  $rs = $conn->query("SHOW TABLES LIKE '$t'");
  return $rs && $rs->num_rows>0;
};

if (!$exists('bpm_task') || !$exists('bpm_instance')) {
  http_response_code(404);
  echo json_encode(['ok'=>false,'error'=>'Tabelas BPM não encontradas.'], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
  exit;
}

$useNew = $exists('bpm_process_version') && $exists('bpm_process');
$useElm = $useNew && $exists('bpm_process_version_element');

$sel = [
  't.id AS task_id','t.instance_id','t.node_id','t.type','t.assignee_user_id','t.candidate_group',
  't.form_slug','t.form_version','t.status AS task_status','t.created_at AS task_created_at',
  'i.version_id','i.status AS instance_status','i.started_at','i.business_key','i.starter_user_id'
];
if ($useNew) {
  $sel[]='p.code AS process_code';
  $sel[]='p.name AS process_name';
  $sel[]='p.category AS process_category';
  $sel[]='pv.semver AS process_version';
  if ($useElm) $sel[]='e.name AS step_name';
}

$sql = "SELECT ".implode(',',$sel)."\n";
$sql .= "FROM bpm_task t\nJOIN bpm_instance i ON i.id=t.instance_id\n";
if ($useNew) {
  $sql .= "JOIN bpm_process_version pv ON pv.id=i.version_id\nJOIN bpm_process p ON p.id=pv.process_id\n";
  if ($useElm) $sql .= "LEFT JOIN bpm_process_version_element e ON e.process_version_id=pv.id AND e.element_id=t.node_id\n";
}
$sql .= "WHERE t.id=? LIMIT 1";

$stmt = $conn->prepare($sql);
if (!$stmt) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>'Erro ao preparar SQL: '.$conn->error], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
  exit;
}
$stmt->bind_param('i',$id);
$stmt->execute();
$task = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$task) {
  http_response_code(404);
  echo json_encode(['ok'=>false,'error'=>'Tarefa não encontrada.'], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
  exit;
}

// Autorização mínima: se a tarefa não é do usuário e não está "disponível", nega.
// (candidate_group será tratado mais tarde; por agora, só garante assignee)
if (!empty($task['assignee_user_id']) && (int)$task['assignee_user_id'] !== $uid) {
  http_response_code(403);
  echo json_encode(['ok'=>false,'error'=>'Sem permissão para acessar esta tarefa.'], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
  exit;
}

echo json_encode(['ok'=>true,'task'=>$task], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
