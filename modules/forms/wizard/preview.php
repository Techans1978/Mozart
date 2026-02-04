<?php
// public/modules/forms/wizard/preview.php — Preview (grid 12 colunas) do schema draft
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../../config.php';
require_once ROOT_PATH . '/system/config/autenticacao.php';
require_once ROOT_PATH . '/system/config/connect.php';

if (!isset($conn) && isset($mysqli)) { $conn = $mysqli; }
if (!($conn instanceof mysqli)) die('Conexão MySQLi $conn não encontrada.');

if (session_status() !== PHP_SESSION_ACTIVE) session_start();
proteger_pagina();

$conn->set_charset('utf8mb4');
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$formId = (int)($_GET['form_id'] ?? 0);
$sectionId = (string)($_GET['section_id'] ?? '');
$mode = (string)($_GET['mode'] ?? 'section'); // section|all

if ($formId <= 0) die('form_id inválido.');

$stmt = $conn->prepare("SELECT id, code, title, status, current_version FROM forms_form WHERE id=? LIMIT 1");
$stmt->bind_param("i", $formId);
$stmt->execute();
$form = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$form) die('Formulário não encontrado.');

$ver = max(1, (int)$form['current_version']);

$stmt = $conn->prepare("SELECT id, schema_json, status FROM forms_form_version WHERE form_id=? AND version=? LIMIT 1");
$stmt->bind_param("ii", $formId, $ver);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$row) die('Versão não encontrada.');

$schema = json_decode($row['schema_json'], true);
if (!is_array($schema)) $schema = ['meta'=>[],'sections'=>[],'globals'=>[]];
if (!isset($schema['sections']) || !is_array($schema['sections'])) $schema['sections'] = [];

$sections = $schema['sections'];

function bs_col(int $col): string {
  if ($col < 1) $col = 1;
  if ($col > 12) $col = 12;
  // usa md para grid. Você pode melhorar com breakpoints depois.
  return 'col-12 col-md-'.$col;
}

function dataset_options($field): array {
  $ds = $field['dataset'] ?? null;
  if (!is_array($ds)) return [];
  $mode = (string)($ds['mode'] ?? '');
  if ($mode === 'static' && isset($ds['options']) && is_array($ds['options'])) {
    $out = [];
    foreach ($ds['options'] as $opt) {
      if (!is_array($opt)) continue;
      $out[] = [
        'value' => (string)($opt['value'] ?? ''),
        'label' => (string)($opt['label'] ?? ($opt['value'] ?? '')),
      ];
    }
    return $out;
  }
  return [];
}

function render_field(array $f): string {
  $id = (string)($f['id'] ?? '');
  $name = (string)($f['name'] ?? $id);
  $label = (string)($f['label'] ?? $name);
  $type = (string)($f['type'] ?? 'text');
  $required = !empty($f['required']);
  $requiredMark = array_key_exists('requiredMark',$f) ? (bool)$f['requiredMark'] : true;
  $placeholder = (string)($f['placeholder'] ?? '');
  $help = (string)($f['help'] ?? '');
  $multiple = !empty($f['multiple']);
  $format = (string)($f['format'] ?? '');
  $auto = (string)($f['autoFormatOnType'] ?? '');
  $preset = (string)($f['validationPreset'] ?? '');

  $labelHtml = '<label class="form-label">'.h($label);
  if ($required && $requiredMark) $labelHtml .= ' <span class="text-danger">*</span>';
  $labelHtml .= '</label>';

  // Campo oculto: não exibe no preview
  if ($type === 'hidden') return '';

  $attrReq = $required ? 'required' : '';
  $attrPH  = $placeholder !== '' ? ' placeholder="'.h($placeholder).'"' : '';

  $metaHint = [];
  if ($preset !== '') $metaHint[] = 'preset: '.h($preset);
  if ($format !== '') $metaHint[] = 'format: '.h($format);
  if ($auto !== '') $metaHint[] = 'auto: '.h($auto);

  $metaLine = '';
  if (count($metaHint)) {
    $metaLine = '<div class="small text-muted">'.implode(' • ', $metaHint).'</div>';
  }

  $html = '<div class="mb-3">'.$labelHtml;

  // Render por tipo
  if ($type === 'textarea') {
    $html .= '<textarea class="form-control" name="'.h($name).'" '.$attrReq.$attrPH.' rows="3"></textarea>';
  }
  elseif ($type === 'select') {
    $opts = dataset_options($f);
    $mul = $multiple ? ' multiple' : '';
    $html .= '<select class="form-select" name="'.h($name).($multiple?'[]':'').'"'.$mul.' '.$attrReq.'>';
    $html .= '<option value="">Selecione...</option>';
    foreach ($opts as $o) {
      $html .= '<option value="'.h($o['value']).'">'.h($o['label']).'</option>';
    }
    $html .= '</select>';
    if (!count($opts)) $html .= '<div class="small text-muted">dataset: (não-static ou vazio)</div>';
  }
  elseif ($type === 'radio') {
    $opts = dataset_options($f);
    if (!count($opts)) {
      $opts = [
        ['value'=>'1','label'=>'Opção 1'],
        ['value'=>'2','label'=>'Opção 2']
      ];
    }
    foreach ($opts as $i => $o) {
      $rid = $id.'_r_'.$i;
      $html .= '<div class="form-check">';
      $html .= '<input class="form-check-input" type="radio" name="'.h($name).'" id="'.h($rid).'" value="'.h($o['value']).'">';
      $html .= '<label class="form-check-label" for="'.h($rid).'">'.h($o['label']).'</label>';
      $html .= '</div>';
    }
  }
  elseif ($type === 'checkbox') {
    $cid = $id.'_c';
    $html .= '<div class="form-check">';
    $html .= '<input class="form-check-input" type="checkbox" name="'.h($name).'" id="'.h($cid).'" value="1">';
    $html .= '<label class="form-check-label" for="'.h($cid).'">Marcar</label>';
    $html .= '</div>';
  }
  elseif (in_array($type, ['button','submit','reset'], true)) {
    $txt = $label !== '' ? $label : 'Botão';
    $html .= '<button type="'.h($type).'" class="btn btn-outline-primary">'.h($txt).'</button>';
  }
  else {
    // Inputs comuns
    $safeType = $type;
    if (!preg_match('/^[a-zA-Z0-9\-]+$/', $safeType)) $safeType = 'text';
    $html .= '<input class="form-control" type="'.h($safeType).'" name="'.h($name).'" '.$attrReq.$attrPH.'>';
  }

  if ($help !== '') $html .= '<div class="form-text">'.h($help).'</div>';
  if ($metaLine !== '') $html .= $metaLine;

  $html .= '</div>';
  return $html;
}

function render_section(array $sec): string {
  $title = (string)($sec['title'] ?? 'Seção');
  $desc  = (string)($sec['description'] ?? '');
  $gap = (int)($sec['layout']['gap'] ?? 12);
  if ($gap < 0) $gap = 0;
  if ($gap > 48) $gap = 48;

  $fields = $sec['fields'] ?? [];
  if (!is_array($fields)) $fields = [];

  $html = '<div class="card shadow-sm mb-3">';
  $html .= '<div class="card-body">';
  $html .= '<div class="d-flex justify-content-between align-items-start">';
  $html .= '<div>';
  $html .= '<h5 class="mb-1">'.h($title).'</h5>';
  if ($desc !== '') $html .= '<div class="text-muted">'.h($desc).'</div>';
  $html .= '</div>';
  $html .= '<span class="badge bg-light text-dark">gap '.$gap.'px</span>';
  $html .= '</div>';
  $html .= '<hr class="my-3">';

  // Grid
  $html .= '<div class="row" style="row-gap: '.$gap.'px;">';
  foreach ($fields as $f) {
    $col = (int)($f['col'] ?? 12);
    $cell = render_field(is_array($f)?$f:[]);
    if ($cell === '') continue;
    $html .= '<div class="'.h(bs_col($col)).'">'.$cell.'</div>';
  }
  $html .= '</div>';

  $html .= '</div></div>';
  return $html;
}

// seleciona seções para render
$renderSections = [];

if ($mode === 'all') {
  $renderSections = $sections;
} else {
  // section mode
  if ($sectionId !== '') {
    foreach ($sections as $s) {
      if ((string)($s['id'] ?? '') === $sectionId) { $renderSections = [$s]; break; }
    }
  }
  if (!count($renderSections) && count($sections)) $renderSections = [$sections[0]];
}
?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Preview • <?php echo h($form['code']); ?></title>
  <link rel="stylesheet" href="<?php echo h(BASE_URL); ?>/assets/css/bootstrap.min.css">
  <style>
    body{ background:#f6f7fb; }
    .topbar{ position:sticky; top:0; z-index:50; backdrop-filter: blur(6px); background:rgba(246,247,251,.9); border-bottom:1px solid rgba(0,0,0,.06); }
    .mono{ font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono","Courier New", monospace; }
  </style>
</head>
<body>

<div class="topbar py-2">
  <div class="container d-flex justify-content-between align-items-center">
    <div>
      <div class="fw-semibold">Preview do Formulário</div>
      <div class="text-muted small">
        <span class="mono"><?php echo h($form['code']); ?></span> • v<?php echo (int)$ver; ?> • <?php echo h($row['status']); ?>
        • modo: <?php echo h($mode); ?>
      </div>
    </div>
    <div class="d-flex gap-2">
      <a class="btn btn-sm btn-outline-secondary"
         href="<?php echo h(BASE_URL); ?>/public/modules/forms/wizard/3.php?form_id=<?php echo (int)$formId; ?>&section_id=<?php echo h($sectionId); ?>">
        Voltar ao Step 3
      </a>
    </div>
  </div>
</div>

<div class="container py-3">
  <?php
    foreach ($renderSections as $s) {
      echo render_section(is_array($s)?$s:[]);
    }
  ?>
  <div class="text-muted small">
    Preview apenas visual (sem executar rules/hooks). Dataset mostra options somente quando <b>mode=static</b>.
  </div>
</div>

</body>
</html>
