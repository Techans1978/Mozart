<?php
// modules/bpm/api/bpmsai_form_fields.php
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

$key = trim((string)($_GET['key'] ?? ''));
if ($key==='') out(false, ['error'=>'missing key'], 400);

// pega a última versão desse form (por key)
$stmt = $conn->prepare("
  SELECT id, `key`, title, schema_json
  FROM bpm_form
  WHERE `key`=?
  ORDER BY id DESC
  LIMIT 1
");
$stmt->bind_param("s", $key);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if(!$row) out(false, ['error'=>'form not found'], 404);

$raw = (string)($row['schema_json'] ?? '');
$raw = trim($raw);
if ($raw==='') out(true, ['fields'=>[], 'note'=>'schema vazio']);

$schema = json_decode($raw, true);
if (!is_array($schema)) out(true, ['fields'=>[], 'note'=>'schema nao-json']);

$fields = [];
$seen = [];

$walk = function($node) use (&$walk, &$fields, &$seen){
  if (!is_array($node)) return;

  if (!empty($node['key'])) {
    $k = (string)$node['key'];
    if (!isset($seen[$k])) {
      $seen[$k]=1;
      $fields[] = [
        'key'   => $k,
        'label' => (string)($node['label'] ?? $k),
        'type'  => (string)($node['type'] ?? 'field'),
      ];
    }
  }

  foreach (['components','children','items','fields'] as $arrKey) {
    if (!empty($node[$arrKey]) && is_array($node[$arrKey])) {
      foreach($node[$arrKey] as $child) $walk($child);
    }
  }
};

if (!empty($schema['components']) && is_array($schema['components'])) {
  foreach($schema['components'] as $c) $walk($c);
} else {
  $walk($schema);
}

out(true, [
  'fields'=>$fields,
  'form'=>['id'=>(int)$row['id'],'title'=>$row['title'],'key'=>$row['key']]
]);
