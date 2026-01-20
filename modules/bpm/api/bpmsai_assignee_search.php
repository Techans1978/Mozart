<?php
// modules/bpm/api/bpmsai_assignee_search.php
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

$type = strtolower(trim((string)($_GET['type'] ?? '')));
$q    = trim((string)($_GET['q'] ?? ''));

if (!in_array($type, ['user','group','perfil'], true)) {
  out(false, ['error'=>'type inválido'], 400);
}

$like = '%'.$q.'%';
$items = [];

if ($type === 'user') {
  $stmt = $conn->prepare("
    SELECT id, nome_completo AS label, username AS extra, email
    FROM usuarios
    WHERE ativo=1
      AND (nome_completo LIKE ? OR username LIKE ? OR email LIKE ?)
    ORDER BY nome_completo
    LIMIT 20
  ");
  $stmt->bind_param("sss", $like, $like, $like);
  $stmt->execute();
  $rs = $stmt->get_result();
  while($r=$rs->fetch_assoc()){
    $items[] = [
      'type'=>'user',
      'key'=>(int)$r['id'],
      'label'=>$r['label'],
      'extra'=>$r['extra'],
      'email'=>$r['email'],
    ];
  }
  $stmt->close();
}

if ($type === 'group') {
  $stmt = $conn->prepare("
    SELECT id, nome AS label, codigo AS extra, parent_id
    FROM grupos
    WHERE ativo=1
      AND (nome LIKE ? OR codigo LIKE ?)
    ORDER BY nome
    LIMIT 20
  ");
  $stmt->bind_param("ss", $like, $like);
  $stmt->execute();
  $rs = $stmt->get_result();
  while($r=$rs->fetch_assoc()){
    $items[] = [
      'type'=>'group',
      'key'=>(int)$r['id'],
      'label'=>$r['label'],
      'extra'=>$r['extra'],
      'parent_id'=>$r['parent_id'] !== null ? (int)$r['parent_id'] : null,
    ];
  }
  $stmt->close();
}

if ($type === 'perfil') {
  $stmt = $conn->prepare("
    SELECT id, nome AS label, codigo AS extra, parent_id
    FROM perfis
    WHERE ativo=1
      AND (nome LIKE ? OR codigo LIKE ?)
    ORDER BY nome
    LIMIT 20
  ");
  $stmt->bind_param("ss", $like, $like);
  $stmt->execute();
  $rs = $stmt->get_result();
  while($r=$rs->fetch_assoc()){
    $items[] = [
      'type'=>'perfil',
      'key'=>(int)$r['id'],
      'label'=>$r['label'],
      'extra'=>$r['extra'],
      'parent_id'=>$r['parent_id'] !== null ? (int)$r['parent_id'] : null,
    ];
  }
  $stmt->close();
}

out(true, ['items'=>$items]);
