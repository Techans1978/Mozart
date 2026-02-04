<?php
// modules/bpm/bpmsai_wizard_steps/save.php
if (session_status()===PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../../../config.php';
require_once ROOT_PATH . '/system/config/autenticacao.php';
require_once ROOT_PATH . '/system/config/connect.php';
proteger_pagina();

if (!isset($conn) && isset($mysqli)) { $conn = $mysqli; }
if (!($conn instanceof mysqli)) { die('Conexão MySQLi $conn não encontrada.'); }
$conn->set_charset('utf8mb4');

$step = max(1, min(5, intval($_GET['step'] ?? 1)));

$state = $_SESSION['bpmsai_wizard'] ?? [
  'flow_id' => null,
  'flow_version_id' => null,
  'nome' => '',
  'codigo' => '',
  'categoria_id' => null,
  'descricao' => '',
  'original_text' => '',
  'actors_dict' => '',
  'steps' => [],
  'transitions' => [],
  'default_form_slug' => '',
  'formsByStep' => []
];

function go($n){ header('Location: /modules/bpm/bpmsai-wizard.php?step='.$n); exit; }
function jdec($v){ $x=json_decode($v,true); return is_array($x)?$x:[]; }

function calc_participants_cache($steps){
  // pega até 20 participantes (type:key) dos assignees
  $set = [];
  foreach((array)$steps as $s){
    $ass = $s['assignees'] ?? [];
    if(!is_array($ass)) $ass = [];
    foreach($ass as $p){
      if(!is_array($p)) continue;
      $t = (string)($p['type'] ?? '');
      $k = (string)($p['key'] ?? '');
      if($t!=='' && $k!=='') $set[$t.':'.$k]=1;
    }
  }
  return implode(',', array_slice(array_keys($set), 0, 20));
}

function validate_flow(&$state){
  $errs=[]; $warns=[];
  $steps = $state['steps'] ?? [];
  if(!is_array($steps) || !count($steps)) $errs[]='Sem etapas.';
  $ids=[];
  foreach((array)$steps as $s){
    $id = (string)($s['id'] ?? '');
    if($id==='') { $errs[]='Etapa sem id.'; continue; }
    if(isset($ids[$id])) $errs[]='ID repetido: '.$id;
    $ids[$id]=1;
    if(empty($s['actions']) || !is_array($s['actions'])) $warns[]='Etapa '.$id.' sem ações.';
  }

  $tr = $state['transitions'] ?? [];
  foreach((array)$steps as $s){
    $sid = (string)($s['id'] ?? '');
    $actions = (array)($s['actions'] ?? []);
    foreach($actions as $acode=>$ainfo){
      $to = $tr[$sid][$acode] ?? '';
      if($to==='') $warns[]='Etapa '.$sid.' ação '.$acode.' sem destino.';
      if($to && $to!=='__END__' && !isset($ids[$to])) $errs[]='Destino inválido: '.$sid.' '.$acode.' -> '.$to;
    }
  }

  $state['validation_errors']=$errs;
  $state['validation_warns']=$warns;
  return [$errs,$warns];
}

function bpmsai_steps_canonicalize($steps): array {
  if (!is_array($steps)) return [];

  // garante array numérico
  $keys = array_keys($steps);
  if ($keys !== range(0, count($steps)-1)) $steps = array_values($steps);

  $out = [];
  foreach ($steps as $s) {
    if (!is_array($s)) $s = [];

    // compat legado
    if (!isset($s['assignees']) && isset($s['assignment'])) $s['assignees'] = [$s['assignment']];
    if (!isset($s['assignees']) && isset($s['assignee']))   $s['assignees'] = is_array($s['assignee']) ? $s['assignee'] : [$s['assignee']];
    if (!isset($s['observers']) && isset($s['watchers']))   $s['observers'] = $s['watchers'];

    $s['id']   = (string)($s['id']   ?? '');
    $s['name'] = (string)($s['name'] ?? '');
    $s['type'] = (string)($s['type'] ?? 'human');

    $s['description'] = (string)($s['description'] ?? '');

    $s['blocks'] = array_merge([
      'use_description'  => true,
      'use_form'         => false,
      'use_bpmn_convert' => false,
    ], (array)($s['blocks'] ?? []));

    $s['form'] = array_merge([
      'mode'    => 'none',
      'payload' => ''
    ], (array)($s['form'] ?? []));

    $s['integration'] = array_merge([
      'connector_key'   => '',
      'connector_label' => ''
    ], (array)($s['integration'] ?? []));

    $s['static_text'] = (string)($s['static_text'] ?? '');

    $s['code'] = array_merge([
      'lang' => 'js',
      'body' => ''
    ], (array)($s['code'] ?? []));

    $s['assignees'] = is_array($s['assignees'] ?? null) ? $s['assignees'] : [];
    $s['observers'] = is_array($s['observers'] ?? null) ? $s['observers'] : [];

    // ações
    $s['actions'] = (array)($s['actions'] ?? []);
    foreach (['approve'=>'Aprovar','reject'=>'Reprovar','revise'=>'Pedir correção'] as $k=>$label) {
      if (!isset($s['actions'][$k]) || !is_array($s['actions'][$k])) $s['actions'][$k] = [];
      $s['actions'][$k]['label'] = (string)($s['actions'][$k]['label'] ?? $label);
    }

    // limpa placeholder
    foreach ($s['assignees'] as &$p) {
      if (is_array($p) && ($p['label'] ?? '') === '(definir)' && (empty($p['key']) || (int)$p['key'] === 0)) {
        $p['label'] = '';
      }
    }
    unset($p);

    $out[] = $s;
  }

  return $out;
}

// ================== STEP switch ==================
switch($step){

  case 1:
    $state['nome'] = trim((string)($_POST['nome'] ?? ''));
    $state['codigo'] = trim((string)($_POST['codigo'] ?? ''));
    $cat = intval($_POST['categoria_id'] ?? 0);
    $state['categoria_id'] = $cat>0 ? $cat : null;
    $state['descricao'] = trim((string)($_POST['descricao'] ?? ''));
    $_SESSION['bpmsai_wizard'] = $state;
    go(2);

  case 2:
    $state['original_text'] = trim((string)($_POST['original_text'] ?? ''));
    $state['actors_dict']   = trim((string)($_POST['actors_dict'] ?? ''));
    $_SESSION['bpmsai_wizard'] = $state;
    go(3);

  case 3:
    $steps_json = trim((string)($_POST['steps_json'] ?? '[]'));
    $arr = json_decode($steps_json, true);
    if (!is_array($arr)) $arr = [];
    $arr = bpmsai_steps_canonicalize($arr);

    $state['steps'] = $arr;
    $_SESSION['bpmsai_wizard'] = $state;
    go(4);

  case 4:
    $state['default_form_slug'] = trim((string)($_POST['default_form_slug'] ?? ''));
    $state['transitions'] = $_POST['to'] ?? [];
    $state['formsByStep'] = [];

    $form_slug = $_POST['form_slug'] ?? [];
    $editable  = $_POST['editable_json'] ?? [];
    $readonly  = $_POST['readonly_json'] ?? [];

    foreach((array)$form_slug as $sid=>$slug){
      $sid = (string)$sid;
      $state['formsByStep'][$sid] = [
        'form_slug' => trim((string)$slug),
        'editable'  => jdec($editable[$sid] ?? '[]'),
        'readonly'  => jdec($readonly[$sid] ?? '[]'),
      ];
    }

    validate_flow($state);
    $_SESSION['bpmsai_wizard'] = $state;
    go(5);

  case 5:
    $action = (string)($_POST['action'] ?? 'save_draft');
    validate_flow($state);
    $_SESSION['bpmsai_wizard'] = $state;

    $def = [
      'kind' => 'bpms.ai.flow',
      'schema_version' => '1.0',
      'flow' => [
        'code' => $state['codigo'],
        'name' => $state['nome'],
        'category_id' => $state['categoria_id'],
        'description' => $state['descricao']
      ],
      'language' => [
        'locale' => 'pt-BR',
        'original_text' => $state['original_text'],
        'actors_dictionary_text' => $state['actors_dict']
      ],
      'forms' => [
        'default_form' => [
          'type' => 'formSlug',
          'slug' => $state['default_form_slug'] ?: null,
          'version' => 'latest'
        ],
        'by_step' => $state['formsByStep']
      ],
      'steps' => $state['steps'],
      'transitions' => $state['transitions'],
      'runtime' => [
        'comment_required_on' => ['reject','revise']
      ]
    ];
    $json_def = json_encode($def, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);

    try {
      $conn->begin_transaction();

      $flowId = (int)($state['flow_id'] ?? 0);
      $participants = calc_participants_cache($state['steps']);
      $uid = (int)($_SESSION['user_id'] ?? 0);

      if($flowId<=0){
        $stmt = $conn->prepare("INSERT INTO bpmsai_flow (code,name,description,category_id,participants_cache,is_active,created_by) VALUES (?,?,?,?,?,1,?)");
        $stmt->bind_param("sssisi", $state['codigo'], $state['nome'], $state['descricao'], $state['categoria_id'], $participants, $uid);
        $stmt->execute();
        $flowId = (int)$stmt->insert_id;
        $stmt->close();
        $state['flow_id'] = $flowId;
      } else {
        $stmt = $conn->prepare("UPDATE bpmsai_flow SET code=?, name=?, description=?, category_id=?, participants_cache=?, updated_by=?, updated_at=NOW() WHERE id=? LIMIT 1");
        $stmt->bind_param("sssissi", $state['codigo'], $state['nome'], $state['descricao'], $state['categoria_id'], $participants, $uid, $flowId);
        $stmt->execute();
        $stmt->close();
      }

      $draftId = 0;
      $stmt = $conn->prepare("SELECT id FROM bpmsai_flow_version WHERE flow_id=? AND status='draft' ORDER BY id DESC LIMIT 1");
      $stmt->bind_param("i", $flowId);
      $stmt->execute();
      $row = $stmt->get_result()->fetch_assoc();
      $draftId = (int)($row['id'] ?? 0);
      $stmt->close();

      $orig = $state['original_text'] ?? '';

      if($draftId<=0){
        $rs = $conn->query("SELECT COALESCE(MAX(version_number),0) mx FROM bpmsai_flow_version WHERE flow_id=".(int)$flowId);
        $mx = (int)($rs?($rs->fetch_assoc()['mx'] ?? 0):0);
        $next = $mx + 1;

        $stmt = $conn->prepare("INSERT INTO bpmsai_flow_version (flow_id,version_number,status,json_def,original_text,created_by) VALUES (?,?, 'draft', ?, ?, ?)");
        $stmt->bind_param("iissi", $flowId, $next, $json_def, $orig, $uid);
        $stmt->execute();
        $draftId = (int)$stmt->insert_id;
        $stmt->close();
      } else {
        $stmt = $conn->prepare("UPDATE bpmsai_flow_version SET json_def=?, original_text=? WHERE id=? LIMIT 1");
        $stmt->bind_param("ssi", $json_def, $orig, $draftId);
        $stmt->execute();
        $stmt->close();
      }

      $state['flow_version_id'] = $draftId;

      if($action==='publish'){
        $errs = $state['validation_errors'] ?? [];
        if($errs){
          $conn->rollback();
          $_SESSION['bpmsai_wizard'] = $state;
          header('Location: /modules/bpm/bpmsai-wizard.php?step=5');
          exit;
        }

        $stmt = $conn->prepare("UPDATE bpmsai_flow_version SET status='published', published_by=?, published_at=NOW() WHERE id=? LIMIT 1");
        $stmt->bind_param("ii", $uid, $draftId);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("UPDATE bpmsai_flow SET active_version_id=? WHERE id=? LIMIT 1");
        $stmt->bind_param("ii", $draftId, $flowId);
        $stmt->execute();
        $stmt->close();
      }

      $conn->commit();
      $_SESSION['bpmsai_wizard'] = $state;
      header('Location: /modules/bpm/bpmsai-listar.php');
      exit;

    } catch (Throwable $e) {
      @$conn->rollback();
      $_SESSION['bpmsai_wizard'] = $state;
      $_SESSION['__flash'] = ['m' => 'Erro ao salvar/persistir: '.$e->getMessage()];
      header('Location: /modules/bpm/bpmsai-wizard.php?step=5');
      exit;
    }
}

