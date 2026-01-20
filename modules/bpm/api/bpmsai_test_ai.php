<?php
// modules/bpm/api/bpmsai_test_ai.php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../../config.php';
require_once ROOT_PATH . '/system/config/autenticacao.php';
require_once ROOT_PATH . '/system/config/connect.php';
if (session_status()===PHP_SESSION_NONE) session_start();
proteger_pagina();

function out($ok, $data=[], $http=200){
  http_response_code($http);
  echo json_encode(['ok'=>$ok] + $data, JSON_UNESCAPED_UNICODE);
  exit;
}

$st = $_SESSION['bpmsai_wizard'] ?? null;
if(!$st) out(false, ['error'=>'no wizard state'], 400);

$messages = [];
$status = 'ok';

$steps = $st['steps'] ?? [];
$tr    = $st['transitions'] ?? [];

if (!is_array($steps) || !count($steps)) {
  $status='fail'; $messages[]='Sem etapas definidas.';
}

$ids=[];
foreach((array)$steps as $s){
  $sid = (string)($s['id'] ?? '');
  if($sid===''){ $status='fail'; $messages[]='Etapa sem id.'; continue; }
  if(isset($ids[$sid])){ $status='fail'; $messages[]='ID repetido: '.$sid; }
  $ids[$sid]=1;

  $as = $s['assignment'] ?? null;
  if(!is_array($as) || empty($as['type']) || empty($as['key'])){
    $status='fail'; $messages[]='Etapa '.$sid.' sem assignment {type,key}.';
  }
}

foreach((array)$steps as $s){
  $sid = (string)($s['id'] ?? '');
  $actions = (array)($s['actions'] ?? []);
  foreach($actions as $acode=>$ainfo){
    $to = $tr[$sid][$acode] ?? '';
    if($to===''){
      $status = ($status==='fail') ? 'fail' : 'warn';
      $messages[]="Ação sem destino: {$sid}.{$acode}";
      continue;
    }
    if($to!=='__END__' && !isset($ids[$to])){
      $status='fail';
      $messages[]="Destino inválido: {$sid}.{$acode} -> {$to}";
    }
  }
}

if($status==='ok' && !$messages) $messages[]='Validação IA (MVP) passou.';

$_SESSION['bpmsai_wizard']['last_ai_test'] = [
  'status' => $status,
  'messages' => $messages,
  'at' => date('c')
];

out(true, ['status'=>$status, 'messages'=>$messages]);
