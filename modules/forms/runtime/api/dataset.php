<?php
// public/modules/forms/runtime/api/dataset.php
ini_set('display_errors',1); ini_set('display_startup_errors',1); error_reporting(E_ALL);

require_once __DIR__ . '/../../../../config.php';
require_once ROOT_PATH . '/system/config/connect.php';
require_once ROOT_PATH . '/system/config/autenticacao.php';

if (!isset($conn) && isset($mysqli)) $conn = $mysqli;
if (!($conn instanceof mysqli)) { http_response_code(500); die('Sem DB'); }

if (session_status() !== PHP_SESSION_ACTIVE) session_start();
proteger_pagina();
$conn->set_charset('utf8mb4');

function j($arr, int $code=200){
  http_response_code($code);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode($arr, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
  exit;
}

$formId = (int)($_GET['form_id'] ?? 0);
$code   = trim((string)($_GET['code'] ?? ''));
$datasetId = trim((string)($_GET['dataset_id'] ?? ''));
$mode   = trim((string)($_GET['mode'] ?? 'published')); // published|draft

$search = trim((string)($_GET['search'] ?? ''));
$value  = trim((string)($_GET['value'] ?? ''));

if ($datasetId==='') j(['ok'=>false,'error'=>'missing dataset_id'],400);

// permissões draft
$perms = $_SESSION['rbac_perms'] ?? [];
$allowDraft = is_array($perms) && (in_array('forms:design',$perms,true) || in_array('*',$perms,true));
if ($mode==='draft' && !$allowDraft) j(['ok'=>false,'error'=>'no permission for draft'],403);

// carrega form
if ($formId<=0 && $code!=='') {
  $stmt=$conn->prepare("SELECT id, code, current_version FROM forms_form WHERE code=? LIMIT 1");
  $stmt->bind_param("s",$code);
  $stmt->execute();
  $form=$stmt->get_result()->fetch_assoc();
  $stmt->close();
  if(!$form) j(['ok'=>false,'error'=>'form not found'],404);
  $formId=(int)$form['id'];
} else {
  $stmt=$conn->prepare("SELECT id, code, current_version FROM forms_form WHERE id=? LIMIT 1");
  $stmt->bind_param("i",$formId);
  $stmt->execute();
  $form=$stmt->get_result()->fetch_assoc();
  $stmt->close();
  if(!$form) j(['ok'=>false,'error'=>'form not found'],404);
}
$code = (string)$form['code'];
$curVer = max(1,(int)$form['current_version']);

// resolve versão
if ($mode==='draft') {
  $stmt=$conn->prepare("SELECT schema_json, status, version FROM forms_form_version WHERE form_id=? AND version=? LIMIT 1");
  $stmt->bind_param("ii",$formId,$curVer);
} else {
  $stmt=$conn->prepare("SELECT schema_json, status, version FROM forms_form_version WHERE form_id=? AND status='published' ORDER BY version DESC LIMIT 1");
  $stmt->bind_param("i",$formId);
}
$stmt->execute();
$ver=$stmt->get_result()->fetch_assoc();
$stmt->close();

if(!$ver) j(['ok'=>false,'error'=>'version not found'],404);
$schema = json_decode($ver['schema_json'], true);
if(!is_array($schema)) j(['ok'=>false,'error'=>'invalid schema'],500);

$datasets = $schema['globals']['datasets'] ?? [];
if(!is_array($datasets)) $datasets = [];

$ds=null;
foreach($datasets as $d){
  if((string)($d['id']??'')===$datasetId){ $ds=$d; break; }
}
if(!$ds) j(['ok'=>false,'error'=>'dataset not found'],404);

$modeDs = (string)($ds['mode'] ?? 'static');
$keyField = (string)($ds['key'] ?? 'value');
$labelField = (string)($ds['label'] ?? 'label');

$limit = 50;
$searchLike = '%'.$search.'%';

// ---- static ----
if ($modeDs === 'static') {
  $opts = $ds['options'] ?? [];
  if(!is_array($opts)) $opts=[];
  // filtro simples por search (label/value)
  if($search!==''){
    $opts = array_values(array_filter($opts, function($o) use ($keyField,$labelField,$search){
      $v = (string)($o[$keyField] ?? $o['value'] ?? '');
      $l = (string)($o[$labelField] ?? $o['label'] ?? '');
      return (stripos($v,$search)!==false) || (stripos($l,$search)!==false);
    }));
  }
  $opts = array_slice($opts,0,$limit);
  j(['ok'=>true,'mode'=>'static','key'=>$keyField,'label'=>$labelField,'items'=>$opts]);
}

// ---- endpoint (seguro) ----
if ($modeDs === 'endpoint') {
  $endpoint = trim((string)($ds['endpoint'] ?? ''));
  $method = strtoupper((string)($ds['method'] ?? 'GET'));
  if ($endpoint==='') j(['ok'=>false,'error'=>'missing endpoint'],400);

  // allowlist MUITO simples: só URLs internas do seu domínio (BASE_URL) ou paths começando com /
  $isInternal = (strpos($endpoint, '://') === false && str_starts_with($endpoint,'/'))
             || (strpos($endpoint, BASE_URL) === 0);

  if (!$isInternal) j(['ok'=>false,'error'=>'endpoint not allowed'],403);

  // monta URL
  $url = $endpoint;
  if (str_starts_with($url,'/')) $url = BASE_URL . $url;

  // repassa search/value
  $qs = http_build_query(['search'=>$search,'value'=>$value,'limit'=>$limit], '', '&');
  $final = $url . (str_contains($url,'?') ? '&' : '?') . $qs;

  // cURL
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $final);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_TIMEOUT, 8);
  if ($method === 'POST') {
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $qs);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
  }
  $resp = curl_exec($ch);
  $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
  $err  = curl_error($ch);
  curl_close($ch);

  if ($resp===false) j(['ok'=>false,'error'=>'endpoint error','detail'=>$err],500);
  if ($http<200 || $http>=300) j(['ok'=>false,'error'=>'endpoint http','status'=>$http],500);

  $data = json_decode($resp,true);
  if(!is_array($data)) j(['ok'=>false,'error'=>'endpoint invalid json'],500);

  // contrato esperado: {ok:true, items:[{value,label}]} OU array puro
  if (isset($data['items']) && is_array($data['items'])) {
    j(['ok'=>true,'mode'=>'endpoint','key'=>$keyField,'label'=>$labelField,'items'=>$data['items']]);
  }
  if (is_array($data)) {
    j(['ok'=>true,'mode'=>'endpoint','key'=>$keyField,'label'=>$labelField,'items'=>$data]);
  }
  j(['ok'=>false,'error'=>'endpoint invalid response'],500);
}

// ---- sql (seguro) ----
if ($modeDs === 'sql') {
  $sql = trim((string)($ds['sql'] ?? ''));
  if ($sql==='') j(['ok'=>false,'error'=>'missing sql'],400);

  $sqlLower = strtolower($sql);

  // regras de segurança mínimas (sem firula por agora)
  if (!str_starts_with(ltrim($sqlLower), 'select')) j(['ok'=>false,'error'=>'only SELECT allowed'],403);
  if (str_contains($sqlLower, ';')) j(['ok'=>false,'error'=>'semicolon not allowed'],403);
  foreach (['insert','update','delete','drop','alter','create','truncate'] as $bad){
    if (preg_match('/\b'.preg_quote($bad,'/').'\b/i', $sqlLower)) j(['ok'=>false,'error'=>'sql contains forbidden keyword'],403);
  }
  if (!preg_match('/\blimit\b/i', $sql)) {
    // força limite se não tiver
    $sql .= " LIMIT ".$limit;
  }

  // tokens suportados: {{search}} (LIKE), {{value}}
  // substitui por placeholders "?" e faz bind
  $bind = [];
  $types = '';

  if (str_contains($sql, '{{search}}')) {
    $sql = str_replace('{{search}}', '?', $sql);
    $bind[] = $searchLike;
    $types .= 's';
  }
  if (str_contains($sql, '{{value}}')) {
    $sql = str_replace('{{value}}', '?', $sql);
    $bind[] = $value;
    $types .= 's';
  }

  $stmt = $conn->prepare($sql);
  if(!$stmt) j(['ok'=>false,'error'=>'sql prepare failed','detail'=>$conn->error],500);

  if (count($bind)>0) {
    $stmt->bind_param($types, ...$bind);
  }

  $stmt->execute();
  $res = $stmt->get_result();
  $items = [];
  $i=0;
  while($row=$res->fetch_assoc()){
    $items[] = $row;
    $i++;
    if ($i >= $limit) break;
  }
  $stmt->close();

  j(['ok'=>true,'mode'=>'sql','key'=>$keyField,'label'=>$labelField,'items'=>$items]);
}

j(['ok'=>false,'error'=>'unknown dataset mode'],400);
