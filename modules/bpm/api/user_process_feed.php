<?php
// modules/bpm/api/user_process_feed.php
// Lista "meus processos" no estilo feed: tarefas abertas que o usuário pode atuar.

header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__, 3).'/config.php';
require_once ROOT_PATH.'/system/config/autenticacao.php';
require_once ROOT_PATH.'/system/config/connect.php';
if (session_status()===PHP_SESSION_NONE) session_start();
proteger_pagina();

$uid = (int)($_SESSION['user_id'] ?? 0);
if ($uid<=0) {
  http_response_code(401);
  echo json_encode(['ok'=>false,'error'=>'Usuário não autenticado.'], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
  exit;
}

// Filtros (todos opcionais)
$q         = trim((string)($_GET['q'] ?? ''));                 // nome do BPM
$cat       = trim((string)($_GET['cat'] ?? ''));               // categoria (string)
$part      = trim((string)($_GET['participant'] ?? ''));       // participante (texto)
$dtIni     = trim((string)($_GET['dt_ini'] ?? ''));            // YYYY-MM-DD
$dtFim     = trim((string)($_GET['dt_fim'] ?? ''));            // YYYY-MM-DD
$status    = trim((string)($_GET['status'] ?? 'open'));        // open|completed|all
$result    = trim((string)($_GET['result'] ?? ''));            // approved|rejected| (futuro)
$limit     = max(1, min(200, (int)($_GET['limit'] ?? 50)));
$offset    = max(0, (int)($_GET['offset'] ?? 0));

// helper: existe tabela?
$exists = function($t) use ($conn){
  $t = $conn->real_escape_string($t);
  $rs = $conn->query("SHOW TABLES LIKE '$t'");
  return $rs && $rs->num_rows > 0;
};

if (!$exists('bpm_task') || !$exists('bpm_instance')) {
  echo json_encode(['ok'=>true,'items'=>[], 'next_offset'=>null], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
  exit;
}

// Monta SQL base
$where = [];
$where[] = "(t.assignee_user_id IS NULL OR t.assignee_user_id = $uid)";

// open/completed
if ($status==='completed') {
  $where[] = "t.status='completed'";
} elseif ($status==='all') {
  // sem filtro
} else {
  $where[] = "t.status IN ('ready','claimed','in_progress','error')";
}

// datas (no started_at da instância)
if ($dtIni!=='' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dtIni)) {
  $where[] = "i.started_at >= '".$conn->real_escape_string($dtIni." 00:00:00")."'";
}
if ($dtFim!=='' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dtFim)) {
  $where[] = "i.started_at <= '".$conn->real_escape_string($dtFim." 23:59:59")."'";
}

// joins de processo/versão (novo) ou legado
$useNew = $exists('bpm_process_version') && $exists('bpm_process');
$useElm = $useNew && $exists('bpm_process_version_element');

$select = [
  "t.id AS task_id",
  "t.instance_id",
  "t.node_id",
  "t.type",
  "t.assignee_user_id",
  "t.candidate_group",
  "t.form_slug",
  "t.form_version",
  "t.status AS task_status",
  "t.created_at AS task_created_at",
  "i.started_at",
  "i.status AS instance_status",
];

if ($useNew) {
  $select[] = "p.name AS process_name";
  $select[] = "p.category AS process_category";
  $select[] = "pv.semver AS process_version";
  if ($useElm) $select[] = "e.name AS step_name";
} else {
  $select[] = "NULL AS process_name";
  $select[] = "NULL AS process_category";
  $select[] = "NULL AS process_version";
  $select[] = "NULL AS step_name";
}

// filtros texto
if ($q!=='') {
  $qq = $conn->real_escape_string('%'.$q.'%');
  $where[] = $useNew ? "(p.name LIKE '$qq' OR p.code LIKE '$qq')" : "(t.node_id LIKE '$qq')";
}
if ($cat!=='') {
  $cc = $conn->real_escape_string('%'.$cat.'%');
  if ($useNew) $where[] = "(p.category LIKE '$cc')";
}
if ($part!=='') {
  $pp = $conn->real_escape_string('%'.$part.'%');
  $where[] = "(t.candidate_group LIKE '$pp')"; // assignee nome é resolvido no front (p/ não depender de tabela)
}

// Observação: approved/rejected/alteração ficam como "futuro".
// Mantemos parâmetro result aceito para não quebrar UI.
if ($result!=='') {
  // placeholder: sem filtro por enquanto
}

$sql = "SELECT\n  ".implode(",\n  ", $select)."\n";
$sql .= "FROM bpm_task t\nJOIN bpm_instance i ON i.id=t.instance_id\n";

if ($useNew) {
  $sql .= "JOIN bpm_process_version pv ON pv.id=i.version_id\nJOIN bpm_process p ON p.id=pv.process_id\n";
  if ($useElm) {
    $sql .= "LEFT JOIN bpm_process_version_element e\n  ON e.process_version_id=pv.id AND e.element_id=t.node_id\n";
  }
}

$sql .= "WHERE ".implode(' AND ', $where)."\n";
$sql .= "ORDER BY i.started_at DESC, t.created_at DESC\n";
$sql .= "LIMIT $limit OFFSET $offset";

$items = [];
if ($rs = $conn->query($sql)) {
  while ($r = $rs->fetch_assoc()) {
    $items[] = $r;
  }
}

$next = count($items) === $limit ? ($offset + $limit) : null;

echo json_encode([
  'ok' => true,
  'items' => $items,
  'next_offset' => $next
], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
