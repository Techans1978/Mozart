<?php
// modules/bpm/bpmsai-wizard.php
ini_set('display_errors',1); ini_set('display_startup_errors',1); error_reporting(E_ALL);

require_once __DIR__.'/../../config.php';
require_once ROOT_PATH.'/system/config/autenticacao.php';
require_once ROOT_PATH.'/system/config/connect.php';
if (session_status()===PHP_SESSION_NONE) session_start();
proteger_pagina();

include_once ROOT_PATH.'system/includes/head.php';
include_once ROOT_PATH.'modules/bpm/includes/content_header.php';
include_once ROOT_PATH.'modules/bpm/includes/content_style.php';
include_once ROOT_PATH.'system/includes/navbar.php';

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$step    = max(1, min(5, (int)($_GET['step'] ?? 1)));
$flow_id = (int)($_GET['flow_id'] ?? 0);

// ===== categorias BPM (reusa do BPM) =====
$categorias = [];
$rs = $conn->query("SELECT id, nome FROM bpm_categorias WHERE ativo=1 ORDER BY sort_order ASC, nome ASC");
if ($rs) $categorias = $rs->fetch_all(MYSQLI_ASSOC);

// ===== estado sessão =====
$state = $_SESSION['bpmsai_wizard'] ?? null;

// se mudou flow_id, recarrega
if ($flow_id && (!$state || (int)($state['flow_id'] ?? 0) !== $flow_id)) {
  $state = null;
}

if (!$state) {
  // estado padrão no formato usado pelo save.php + steps 3/4/5
  $state = [
    'flow_id' => $flow_id ?: null,
    'flow_version_id' => null,

    'nome' => '',
    'codigo' => '',
    'categoria_id' => null,
    'descricao' => '',

    'original_text' => '',
    'actors_dict'   => "analista=analista\ngerente=gerente_area\nrh=rh",

    // steps no formato OFICIAL (assignment{type,key}, actions{code:{label}})
    'steps' => [],
    'transitions' => [],

    'default_form_slug' => '',   // aqui é “key” do bpm_form (mantive nome por compatibilidade)
    'formsByStep' => [],

    'validation_errors' => [],
    'validation_warns'  => [],
    'last_ai_test'      => ['status'=>'never','messages'=>[]],
    'last_human_test'   => ['status'=>'never','messages'=>[]],
  ];

  // se flow_id informado, tenta carregar do banco:
  if ($flow_id) {
    // flow
    $stmt = $conn->prepare("SELECT id, code, name, description, category_id, active_version_id FROM bpmsai_flow WHERE id=? LIMIT 1");
    $stmt->bind_param('i', $flow_id);
    $stmt->execute();
    $flow = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($flow) {
      $state['flow_id']      = (int)$flow['id'];
      $state['codigo']       = (string)$flow['code'];
      $state['nome']         = (string)$flow['name'];
      $state['descricao']    = (string)($flow['description'] ?? '');
      $state['categoria_id'] = $flow['category_id'] !== null ? (int)$flow['category_id'] : null;

      // pega draft mais recente, senão active published
      $ver = null;
      $stmt = $conn->prepare("SELECT id, version_number, status, json_def, original_text
                              FROM bpmsai_flow_version
                              WHERE flow_id=? AND status='draft'
                              ORDER BY version_number DESC, id DESC LIMIT 1");
      $stmt->bind_param('i', $flow_id);
      $stmt->execute();
      $ver = $stmt->get_result()->fetch_assoc();
      $stmt->close();

      if (!$ver && !empty($flow['active_version_id'])) {
        $avid = (int)$flow['active_version_id'];
        $stmt = $conn->prepare("SELECT id, version_number, status, json_def, original_text
                                FROM bpmsai_flow_version WHERE id=? LIMIT 1");
        $stmt->bind_param('i', $avid);
        $stmt->execute();
        $ver = $stmt->get_result()->fetch_assoc();
        $stmt->close();
      }

      if ($ver) {
        $state['flow_version_id'] = (int)$ver['id'];
        $state['original_text']   = (string)($ver['original_text'] ?? '');

        $json = json_decode((string)($ver['json_def'] ?? ''), true);
        if (is_array($json)) {
          // extrai do JSON oficial
          if (!empty($json['language']['original_text'])) {
            $state['original_text'] = (string)$json['language']['original_text'];
          }
          if (!empty($json['language']['actors_dictionary_text'])) {
            $state['actors_dict'] = (string)$json['language']['actors_dictionary_text'];
          }
if (!empty($json['steps']) && is_array($json['steps'])) {
  $tmpSteps = $json['steps'];

  // Normaliza para array numérico (evita virar objeto no JS)
  $keys = array_keys($tmpSteps);
  $isNumericSeq = ($keys === range(0, count($tmpSteps)-1));
  if (!$isNumericSeq) $tmpSteps = array_values($tmpSteps);

  $state['steps'] = $tmpSteps;
}

          if (!empty($json['transitions']) && is_array($json['transitions'])) {
            $state['transitions'] = $json['transitions'];
          }
          if (!empty($json['forms']['default_form']['slug'])) {
            $state['default_form_slug'] = (string)$json['forms']['default_form']['slug'];
          }
          if (!empty($json['forms']['by_step']) && is_array($json['forms']['by_step'])) {
            $state['formsByStep'] = $json['forms']['by_step'];
          }
        }
      }
    }
  }

  $_SESSION['bpmsai_wizard'] = $state;
}

$labels = [
  1=>'Metadados',
  2=>'Texto / IA',
  3=>'Etapas',
  4=>'Destinos / Form',
  5=>'Testes / Publicar'
];

$flash = $_SESSION['__flash'] ?? null;
unset($_SESSION['__flash']);
?>

<div id="page-wrapper">
  <div class="container-fluid">

    <div class="row">
      <div class="col-lg-12">
        <h1 class="page-header">Wizard BPMs AI</h1>
        <p class="text-muted" style="margin-top:-8px">
          Fluxo simples por etapas (idas e voltas) com rascunho, testes e publicação.
        </p>
      </div>
    </div>

    <?php if($flash): ?>
      <div class="alert alert-info"><?= h($flash['m'] ?? '') ?></div>
    <?php endif; ?>

    <div class="row">
      <div class="col-lg-12">
        <div class="panel panel-default">
          <div class="panel-body">
            <div class="btn-group" role="group" aria-label="Passos" style="flex-wrap:wrap">
              <?php for($i=1;$i<=5;$i++): ?>
                <?php $active = ($i===$step) ? 'btn-primary' : 'btn-default'; ?>
                <a class="btn <?= $active ?> btn-sm"
                   href="<?= BASE_URL.'/modules/bpm/bpmsai-wizard.php?'.http_build_query(['step'=>$i]+($state['flow_id']?['flow_id'=>$state['flow_id']]:[])) ?>">
                  <?= $i ?>. <?= h($labels[$i]) ?>
                </a>
              <?php endfor; ?>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-lg-12">
        <div class="panel panel-default">
          <div class="panel-body">
            <?php
              $CATEGORIAS = $categorias;
              include __DIR__.'/bpmsai_wizard_steps/'.$step.'.php';
            ?>
          </div>
        </div>
      </div>
    </div>

  </div>
</div>

<?php include_once ROOT_PATH . '/system/includes/footer.php'; ?>
