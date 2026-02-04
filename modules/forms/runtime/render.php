<?php
// public/modules/forms/runtime/render.php — Runtime Renderer do Forms AI (grid + datasets + rules + presets)
ini_set('display_errors',1); ini_set('display_startup_errors',1); error_reporting(E_ALL);

require_once __DIR__ . '/../../../config.php';
require_once ROOT_PATH . '/system/config/connect.php';
require_once ROOT_PATH . '/system/config/autenticacao.php';

if (!isset($conn) && isset($mysqli)) { $conn = $mysqli; }
if (!($conn instanceof mysqli)) die('Conexão MySQLi $conn não encontrada.');

if (session_status() !== PHP_SESSION_ACTIVE) session_start();
// Em runtime você pode querer permitir front. Por enquanto protegendo:
proteger_pagina();

$conn->set_charset('utf8mb4');

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function json_out($arr, $code=200){
  http_response_code($code);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode($arr, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
  exit;
}

// Inputs
$formId = (int)($_GET['form_id'] ?? 0);
$code   = trim((string)($_GET['code'] ?? ''));

// modo: published | draft (draft só se tiver forms:design)
$mode = trim((string)($_GET['mode'] ?? 'published')); // published/draft
$allowDraft = false;

// Se seu RBAC tiver helper, use ele. Se não tiver, deixa draft bloqueado.
// Aqui: se existir $_SESSION['rbac_perms'] com perms, ok:
$perms = $_SESSION['rbac_perms'] ?? [];
if (is_array($perms) && (in_array('forms:design',$perms,true) || in_array('*',$perms,true))) $allowDraft = true;

// Carrega form
if ($formId <= 0 && $code !== '') {
  $stmt = $conn->prepare("SELECT id, code, title, status, current_version FROM forms_form WHERE code=? LIMIT 1");
  $stmt->bind_param("s", $code);
  $stmt->execute();
  $form = $stmt->get_result()->fetch_assoc();
  $stmt->close();
  if (!$form) die('Formulário não encontrado.');
  $formId = (int)$form['id'];
} else {
  $stmt = $conn->prepare("SELECT id, code, title, status, current_version FROM forms_form WHERE id=? LIMIT 1");
  $stmt->bind_param("i", $formId);
  $stmt->execute();
  $form = $stmt->get_result()->fetch_assoc();
  $stmt->close();
  if (!$form) die('Formulário não encontrado.');
}

if (($form['status'] ?? '') !== 'active') {
  die('Formulário bloqueado/arquivado.');
}


$curVer = max(1, (int)$form['current_version']);

// resolve versão
if ($mode === 'draft') {
  if (!$allowDraft) die('Sem permissão para abrir draft.');
  $stmt = $conn->prepare("SELECT id, version, schema_json, status FROM forms_form_version WHERE form_id=? AND version=? LIMIT 1");
  $stmt->bind_param("ii", $formId, $curVer);
  $stmt->execute();
  $ver = $stmt->get_result()->fetch_assoc();
  $stmt->close();
  if (!$ver) die('Versão draft não encontrada.');
} else {
  // published: pega a última publicada; se não tiver, cai na current_version se published.
  $stmt = $conn->prepare("SELECT id, version, schema_json, status
                          FROM forms_form_version
                          WHERE form_id=? AND status='published'
                          ORDER BY version DESC
                          LIMIT 1");
  $stmt->bind_param("i", $formId);
  $stmt->execute();
  $ver = $stmt->get_result()->fetch_assoc();
  $stmt->close();

  if (!$ver) {
    // fallback: se current_version estiver publicada
    $stmt = $conn->prepare("SELECT id, version, schema_json, status FROM forms_form_version WHERE form_id=? AND version=? LIMIT 1");
    $stmt->bind_param("ii", $formId, $curVer);
    $stmt->execute();
    $ver = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$ver) die('Versão não encontrada.');
    if (($ver['status'] ?? '') !== 'published') die('Não existe versão publicada ainda.');
  }
}

$schema = json_decode($ver['schema_json'], true);
if (!is_array($schema)) die('Schema inválido.');

$sections = $schema['sections'] ?? [];
if (!is_array($sections)) $sections = [];

$globals = $schema['globals'] ?? [];
if (!is_array($globals)) $globals = [];

$datasets = $globals['datasets'] ?? [];
if (!is_array($datasets)) $datasets = [];

$rules = $schema['rules'] ?? [];
if (!is_array($rules)) $rules = [];

// index datasets por id
$dsIndex = [];
foreach ($datasets as $d){
  $did = (string)($d['id'] ?? '');
  if ($did !== '') $dsIndex[$did] = $d;
}

?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?php echo h($form['title'] ?: $form['code']); ?> • Runtime</title>
  <link rel="stylesheet" href="<?php echo h(BASE_URL); ?>/assets/css/bootstrap.min.css">
  <style>
    .mono{ font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono","Courier New", monospace; }
    .hint{ font-size:.9rem; color:#6c757d; }
    .field-wrap[data-hidden="1"]{ display:none !important; }
    .reqmark::after{ content:" *"; color:#dc3545; font-weight:700; }
    .form-error{ font-size:.85rem; color:#dc3545; margin-top:4px; }
    .sec-card{ border:1px solid rgba(0,0,0,.08); border-radius:14px; background:#fff; padding:14px; }
  </style>
</head>
<body class="bg-light">
<div class="container py-4">

  <div class="d-flex justify-content-between align-items-start mb-3">
    <div>
      <h4 class="mb-0"><?php echo h($form['title'] ?: $form['code']); ?></h4>
      <div class="text-muted">
        Runtime • <span class="mono"><?php echo h($form['code']); ?></span> • v<?php echo (int)$ver['version']; ?> •
        <span class="badge bg-<?php echo (($ver['status']??'')==='published'?'success':'warning'); ?>"><?php echo h($ver['status']); ?></span>
      </div>
    </div>
    <div class="d-flex gap-2">
      <button class="btn btn-outline-secondary" type="button" id="btnDump">Dump JSON</button>
      <button class="btn btn-primary" type="submit" form="runtimeForm">Enviar</button>
    </div>
  </div>

  <form id="runtimeForm" class="needs-validation" novalidate>
    <input type="hidden" name="__form_code" value="<?php echo h($form['code']); ?>">
    <input type="hidden" name="__form_version" value="<?php echo (int)$ver['version']; ?>">

    <?php foreach ($sections as $sec): ?>
      <?php
        $secId = (string)($sec['id'] ?? '');
        $secTitle = (string)($sec['title'] ?? $secId);
        $fields = $sec['fields'] ?? [];
        if (!is_array($fields)) $fields = [];
      ?>
      <div class="sec-card shadow-sm mb-3" data-section-id="<?php echo h($secId); ?>">
        <?php if ($secTitle): ?>
          <div class="d-flex justify-content-between align-items-center mb-2">
            <div class="fw-semibold"><?php echo h($secTitle); ?></div>
            <div class="hint">Seção: <span class="mono"><?php echo h($secId); ?></span></div>
          </div>
        <?php endif; ?>

        <div class="row g-3">
          <?php foreach ($fields as $f): ?>
            <?php
              $fid = (string)($f['id'] ?? '');
              $name = (string)($f['name'] ?? '');
              $label = (string)($f['label'] ?? $name);
              $type = (string)($f['type'] ?? 'text');
              $col  = (int)($f['col'] ?? 12);
              if ($col<1) $col=1; if ($col>12) $col=12;

              $required = !empty($f['required']);
              $requiredMark = array_key_exists('requiredMark',$f) ? (bool)$f['requiredMark'] : true;

              $ph = (string)($f['placeholder'] ?? '');
              $help = (string)($f['help'] ?? '');
              $def = (string)($f['defaultValue'] ?? '');

              $preset = (string)($f['validationPreset'] ?? '');
              $validators = $f['validators'] ?? [];
              if (!is_array($validators)) $validators = [];

              // dataset: pode vir inline (dataset) ou por datasetRef (vamos suportar os dois)
              $dataset = $f['dataset'] ?? null;
              $datasetRef = (string)($f['datasetRef'] ?? '');

              $multiple = !empty($f['multiple']);

              // hidden input sem wrapper? vamos manter wrapper e esconder via css
            ?>
            <div class="col-md-<?php echo (int)$col; ?> field-wrap"
                 data-field-name="<?php echo h($name); ?>"
                 data-field-id="<?php echo h($fid); ?>"
                 data-field-type="<?php echo h($type); ?>"
                 data-preset="<?php echo h($preset); ?>"
                 data-required="<?php echo $required ? '1':'0'; ?>"
                 data-requiredmark="<?php echo $requiredMark ? '1':'0'; ?>"
                 data-datasetref="<?php echo h($datasetRef); ?>"
                 data-validators="<?php echo h(json_encode($validators, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)); ?>"
                 <?php if ($type==='hidden'): ?>data-hidden="1"<?php endif; ?>
            >
              <div class="mb-0">

                <?php if ($type!=='hidden'): ?>
                  <label class="form-label <?php echo ($required && $requiredMark)?'reqmark':''; ?>" for="<?php echo h($fid); ?>">
                    <?php echo h($label); ?>
                  </label>
                <?php endif; ?>

                <?php if ($type === 'textarea'): ?>
                  <textarea
                    class="form-control"
                    id="<?php echo h($fid); ?>"
                    name="<?php echo h($name); ?>"
                    placeholder="<?php echo h($ph); ?>"
                    <?php echo $required ? 'required':''; ?>
                    rows="3"><?php echo h($def); ?></textarea>

                <?php elseif ($type === 'select'): ?>
                  <select
                    class="form-select"
                    id="<?php echo h($fid); ?>"
                    name="<?php echo h($name); ?><?php echo $multiple?'[]':''; ?>"
                    <?php echo $multiple ? 'multiple':''; ?>
                    <?php echo $required ? 'required':''; ?>
                    data-inline-dataset="<?php echo h(is_array($dataset)?json_encode($dataset, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES):''); ?>"
                  >
                    <?php if (!$multiple): ?>
                      <option value="">— selecione —</option>
                    <?php endif; ?>
                  </select>

                <?php elseif ($type === 'datalist'): ?>
                  <?php $dlid = 'dl_'.$fid; ?>
                  <input
                    class="form-control"
                    list="<?php echo h($dlid); ?>"
                    id="<?php echo h($fid); ?>"
                    name="<?php echo h($name); ?>"
                    placeholder="<?php echo h($ph); ?>"
                    value="<?php echo h($def); ?>"
                    <?php echo $required ? 'required':''; ?>
                    data-inline-dataset="<?php echo h(is_array($dataset)?json_encode($dataset, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES):''); ?>"
                  >
                  <datalist id="<?php echo h($dlid); ?>"></datalist>

                <?php else: ?>
                  <input
                    class="form-control"
                    type="<?php echo h($type); ?>"
                    id="<?php echo h($fid); ?>"
                    name="<?php echo h($name); ?>"
                    placeholder="<?php echo h($ph); ?>"
                    value="<?php echo h($def); ?>"
                    <?php echo $required ? 'required':''; ?>
                  >
                <?php endif; ?>

                <?php if ($help): ?>
                  <div class="hint mt-1"><?php echo h($help); ?></div>
                <?php endif; ?>

                <div class="form-error d-none"></div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endforeach; ?>

  </form>

  <div class="card shadow-sm mt-3">
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-center">
        <div class="fw-semibold">Payload gerado</div>
        <div class="hint">isso aqui vai alimentar BPM/Helpdesk/qualquer módulo</div>
      </div>
      <pre class="mono small mb-0 mt-2" id="payloadOut">{}</pre>
    </div>
  </div>

</div>

<script>
/**
 * Runtime JS (sem libs externas)
 * - datasets (static)
 * - presets BR (máscaras simples)
 * - rules engine (when/then/else)
 * - validação mínima + payload
 */
(function(){
  const schema = {
    datasets: <?php echo json_encode($dsIndex, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); ?>,
    rules: <?php echo json_encode($rules, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); ?>,
  };

  async function loadDatasetItems(dsId, search=''){
  const url = api.dataset + "&dataset_id=" + encodeURIComponent(dsId) + "&search=" + encodeURIComponent(search);
  const r = await fetch(url, {credentials:'same-origin'});
  const j = await r.json();
  if (!j || !j.ok) throw new Error(j?.error || 'dataset failed');
  return j;
}

async function fillDatasetSelectOrDatalist(wrap){
  const datasetRef = wrap.dataset.datasetref || '';
  let inline = null;

  const select = wrap.querySelector('select');
  const input = wrap.querySelector('input[list]');
  const datalist = input ? document.querySelector('#'+CSS.escape(input.getAttribute('list'))) : null;

  if (select && select.dataset.inlineDataset) {
    try { inline = JSON.parse(select.dataset.inlineDataset || ''); } catch(e){}
  }
  if (input && input.dataset.inlineDataset) {
    try { inline = JSON.parse(input.dataset.inlineDataset || ''); } catch(e){}
  }

  // prioridade: datasetRef (global)
  let ds = null;
  if (datasetRef && schema.datasets[datasetRef]) ds = schema.datasets[datasetRef];
  if (!ds && inline && typeof inline === 'object') ds = inline;

  if (!ds) return;

  const keyField = ds.key || 'value';
  const labelField = ds.label || 'label';

  // resolve itens:
  let items = [];
  if ((ds.mode || 'static') === 'static') {
    items = Array.isArray(ds.options) ? ds.options : [];
  } else {
    // endpoint/sql: chama API
    const resp = await loadDatasetItems(datasetRef, '');
    items = Array.isArray(resp.items) ? resp.items : [];
  }

  if (select) {
    const keepFirst = !select.multiple && select.options.length && select.options[0].value === '';
    const first = keepFirst ? select.options[0].outerHTML : '';
    select.innerHTML = keepFirst ? first : '';
    items.forEach(o => {
      const v = (o[keyField] ?? o.value ?? '');
      const l = (o[labelField] ?? o.label ?? String(v));
      const opt = document.createElement('option');
      opt.value = String(v);
      opt.textContent = String(l);
      select.appendChild(opt);
    });
    return;
  }

  if (datalist && input) {
    datalist.innerHTML = '';
    items.forEach(o => {
      const v = (o[keyField] ?? o.value ?? '');
      const opt = document.createElement('option');
      opt.value = String(v);
      datalist.appendChild(opt);
    });
  }
}


  const form = document.getElementById('runtimeForm');
  const payloadOut = document.getElementById('payloadOut');
  const btnDump = document.getElementById('btnDump');

  function qs(sel, root=document){ return root.querySelector(sel); }
  function qsa(sel, root=document){ return Array.from(root.querySelectorAll(sel)); }

  // -------- utils values --------
  function getFieldElByName(name){
    return qs(`[name="${CSS.escape(name)}"]`) || qs(`[name="${CSS.escape(name)}[]"]`);
  }
  function getWrapByName(name){
    return qs(`.field-wrap[data-field-name="${CSS.escape(name)}"]`);
  }
  function getValue(name){
    const el = getFieldElByName(name);
    if(!el) return null;
    if (el.tagName === 'SELECT' && el.multiple) {
      return Array.from(el.selectedOptions).map(o => o.value);
    }
    if (el.type === 'checkbox') return el.checked ? '1' : '0';
    return el.value;
  }
  function setValue(name, val){
    const el = getFieldElByName(name);
    if(!el) return;
    if (el.tagName === 'SELECT' && el.multiple && Array.isArray(val)) {
      Array.from(el.options).forEach(o => o.selected = val.includes(o.value));
      el.dispatchEvent(new Event('change', {bubbles:true}));
      return;
    }
    if (el.type === 'checkbox') {
      el.checked = (val===true || val==='1' || val===1);
      el.dispatchEvent(new Event('change', {bubbles:true}));
      return;
    }
    el.value = (val==null ? '' : String(val));
    el.dispatchEvent(new Event('input', {bubbles:true}));
    el.dispatchEvent(new Event('change', {bubbles:true}));
  }

  function showTarget(targetType, target, show){
    if (targetType === 'section') {
      const sec = qs(`[data-section-id="${CSS.escape(target)}"]`);
      if (!sec) return;
      sec.style.display = show ? '' : 'none';
      return;
    }
    // field
    const wrap = getWrapByName(target) || qs(`.field-wrap[data-field-id="${CSS.escape(target)}"]`);
    if (!wrap) return;
    wrap.dataset.hidden = show ? '0' : '1';
    wrap.style.display = show ? '' : 'none';
  }
  function setRequiredTarget(target, isReq){
    const el = getFieldElByName(target);
    if (!el) return;
    if (isReq) el.setAttribute('required','required');
    else el.removeAttribute('required');

    const wrap = getWrapByName(target);
    if (wrap) {
      wrap.dataset.required = isReq ? '1' : '0';
      const lbl = wrap.querySelector('.form-label');
      const mark = wrap.dataset.requiredmark === '1';
      if (lbl) {
        if (isReq && mark) lbl.classList.add('reqmark');
        else lbl.classList.remove('reqmark');
      }
    }
  }
  function setEnabledTarget(target, isEnabled){
    const el = getFieldElByName(target);
    if (!el) return;
    el.disabled = !isEnabled;
  }
  function setMaxTarget(target, maxVal){
    const el = getFieldElByName(target);
    if (!el) return;
    if (maxVal === '' || maxVal == null) el.removeAttribute('max');
    else el.setAttribute('max', String(maxVal));
  }

  // -------- datasets (static) --------
  function fillDatasetSelectOrDatalist(wrap){
    const type = wrap.dataset.fieldType;
    const datasetRef = wrap.dataset.datasetref || '';
    let inline = null;

    const select = wrap.querySelector('select');
    const input = wrap.querySelector('input[list]');
    const datalist = input ? qs('#'+CSS.escape(input.getAttribute('list'))) : null;

    // inline dataset
    if (select && select.dataset.inlineDataset) {
      try { inline = JSON.parse(select.dataset.inlineDataset || ''); } catch(e){}
    }
    if (input && input.dataset.inlineDataset) {
      try { inline = JSON.parse(input.dataset.inlineDataset || ''); } catch(e){}
    }

    let ds = null;
    if (datasetRef && schema.datasets[datasetRef]) ds = schema.datasets[datasetRef];
    if (!ds && inline && typeof inline === 'object') ds = inline;

    if (!ds || (ds.mode && ds.mode !== 'static')) return; // aqui runtime executa apenas static

    const keyField = ds.key || 'value';
    const labelField = ds.label || 'label';
    const options = Array.isArray(ds.options) ? ds.options : (Array.isArray(ds.options?.options) ? ds.options.options : []);

    if (select) {
      // mantém primeira opção "— selecione —" se existir
      const keepFirst = !select.multiple && select.options.length && select.options[0].value === '';
      const first = keepFirst ? select.options[0].outerHTML : '';
      select.innerHTML = keepFirst ? first : '';
      options.forEach(o => {
        const v = (o[keyField] ?? o.value ?? '');
        const l = (o[labelField] ?? o.label ?? String(v));
        const opt = document.createElement('option');
        opt.value = String(v);
        opt.textContent = String(l);
        select.appendChild(opt);
      });
      return;
    }

    if (datalist && input) {
      datalist.innerHTML = '';
      options.forEach(o => {
        const v = (o[keyField] ?? o.value ?? '');
        const opt = document.createElement('option');
        opt.value = String(v);
        datalist.appendChild(opt);
      });
    }
  }

  // -------- presets / masks (simples) --------
  function digits(s){ return String(s||'').replace(/\D+/g,''); }
  function maskCPF(v){
    v = digits(v).slice(0,11);
    v = v.replace(/^(\d{3})(\d)/,'$1.$2');
    v = v.replace(/^(\d{3})\.(\d{3})(\d)/,'$1.$2.$3');
    v = v.replace(/^(\d{3})\.(\d{3})\.(\d{3})(\d)/,'$1.$2.$3-$4');
    return v;
  }
  function maskCNPJ(v){
    v = digits(v).slice(0,14);
    v = v.replace(/^(\d{2})(\d)/,'$1.$2');
    v = v.replace(/^(\d{2})\.(\d{3})(\d)/,'$1.$2.$3');
    v = v.replace(/^(\d{2})\.(\d{3})\.(\d{3})(\d)/,'$1.$2.$3/$4');
    v = v.replace(/^(\d{2})\.(\d{3})\.(\d{3})\/(\d{4})(\d)/,'$1.$2.$3/$4-$5');
    return v;
  }
  function maskCEP(v){
    v = digits(v).slice(0,8);
    v = v.replace(/^(\d{5})(\d)/,'$1-$2');
    return v;
  }
  function maskPhoneBR(v){
    v = digits(v).slice(0,11);
    if (v.length <= 10) {
      v = v.replace(/^(\d{2})(\d)/,'($1) $2');
      v = v.replace(/(\d{4})(\d)/,'$1-$2');
    } else {
      v = v.replace(/^(\d{2})(\d)/,'($1) $2');
      v = v.replace(/(\d{5})(\d)/,'$1-$2');
    }
    return v;
  }
  function maskTimeHHMM(v){
    v = digits(v).slice(0,4);
    if (v.length >= 3) v = v.replace(/^(\d{2})(\d{1,2})$/,'$1:$2');
    return v;
  }
  function maskMoneyBR(v){
    v = digits(v);
    if (v === '') return '';
    // centavos fixos
    const n = parseInt(v,10);
    const cents = (n % 100).toString().padStart(2,'0');
    let int = Math.floor(n/100).toString();
    int = int.replace(/\B(?=(\d{3})+(?!\d))/g,'.');
    return 'R$ ' + int + ',' + cents;
  }

  function applyPreset(el, preset){
    if (!preset) return;
    const onInput = () => {
      const old = el.value;
      let neu = old;

      if (preset === 'cpf') neu = maskCPF(old);
      else if (preset === 'cnpj') neu = maskCNPJ(old);
      else if (preset === 'cpf_cnpj') {
        const d = digits(old);
        neu = (d.length <= 11) ? maskCPF(old) : maskCNPJ(old);
      }
      else if (preset === 'cep') neu = maskCEP(old);
      else if (preset === 'phone_br') neu = maskPhoneBR(old);
      else if (preset === 'time_hhmm') neu = maskTimeHHMM(old);
      else if (preset === 'money_br') neu = maskMoneyBR(old);
      else if (preset === 'integer') neu = digits(old);
      else if (preset === 'decimal') {
        // deixa só dígitos e 1 vírgula
        let s = String(old).replace(/[^\d,]/g,'');
        const parts = s.split(',');
        neu = parts.shift() + (parts.length ? ','+parts.join('').slice(0,6) : '');
      }

      if (neu !== old) el.value = neu;
    };
    el.addEventListener('input', onInput);
  }

  // -------- validators (min/max/minLength/maxLength/pattern) --------
  function parseValidators(wrap){
    try { return JSON.parse(wrap.dataset.validators || '[]') || []; } catch(e){ return []; }
  }

  function validateField(wrap){
    const name = wrap.dataset.fieldName;
    const el = getFieldElByName(name);
    if(!el) return true;

    // se hidden (por regra), ignora
    if (wrap.dataset.hidden === '1' || wrap.style.display === 'none') {
      clearError(wrap);
      return true;
    }

    const val = getValue(name);
    const vstr = Array.isArray(val) ? val.join(',') : String(val ?? '');

    // required
    const req = el.hasAttribute('required');
    if (req) {
      const empty = (Array.isArray(val) ? val.length===0 : (vstr.trim()===''));
      if (empty) return setError(wrap, 'Campo obrigatório.');
    }

    // validators
    const validators = parseValidators(wrap);
    for (const v of validators) {
      if (!v || typeof v !== 'object') continue;

      const t = v.type || '';
      const vv = v.value;

      if (t === 'minLength' && vstr.length < (parseInt(vv,10)||0)) return setError(wrap, `Mínimo de ${vv} caracteres.`);
      if (t === 'maxLength' && vstr.length > (parseInt(vv,10)||0)) return setError(wrap, `Máximo de ${vv} caracteres.`);
      if (t === 'pattern' && vv) {
        try {
          const re = new RegExp(String(vv));
          if (vstr && !re.test(vstr)) return setError(wrap, 'Formato inválido.');
        } catch(e){}
      }
      if (t === 'min' && vv !== '' && vv != null) {
        const n = parseFloat(String(vstr).replace('.','').replace(',','.').replace(/[^\d.-]/g,''));
        const mn = parseFloat(vv);
        if (!isNaN(n) && !isNaN(mn) && n < mn) return setError(wrap, `Valor mínimo: ${vv}.`);
      }
      if (t === 'max' && vv !== '' && vv != null) {
        const n = parseFloat(String(vstr).replace('.','').replace(',','.').replace(/[^\d.-]/g,''));
        const mx = parseFloat(vv);
        if (!isNaN(n) && !isNaN(mx) && n > mx) return setError(wrap, `Valor máximo: ${vv}.`);
      }
    }

    clearError(wrap);
    return true;
  }

  function setError(wrap, msg){
    const box = wrap.querySelector('.form-error');
    if (box) {
      box.textContent = msg;
      box.classList.remove('d-none');
    }
    return false;
  }
  function clearError(wrap){
    const box = wrap.querySelector('.form-error');
    if (box) {
      box.textContent = '';
      box.classList.add('d-none');
    }
  }

  // -------- rules engine --------
  function evalCond(cond){
    const field = cond.field;
    const op = cond.op;
    const expected = cond.value;

    const v = getValue(field);
    const vs = Array.isArray(v) ? v : String(v ?? '');

    const isEmpty = (Array.isArray(v) ? v.length===0 : (String(v ?? '').trim()===''));

    if (op === 'empty') return isEmpty;
    if (op === 'notEmpty') return !isEmpty;

    if (op === 'equals') return String(vs) === String(expected ?? '');
    if (op === 'notEquals') return String(vs) !== String(expected ?? '');

    // num ops
    const n = parseFloat(String(vs).replace('.','').replace(',','.').replace(/[^\d.-]/g,''));
    const e = parseFloat(String(expected).replace('.','').replace(',','.').replace(/[^\d.-]/g,''));
    if (['gt','gte','lt','lte'].includes(op)) {
      if (isNaN(n) || isNaN(e)) return false;
      if (op==='gt') return n>e;
      if (op==='gte') return n>=e;
      if (op==='lt') return n<e;
      if (op==='lte') return n<=e;
    }

    if (op === 'in') {
      const arr = Array.isArray(expected) ? expected : String(expected ?? '').split(',').map(s=>s.trim()).filter(Boolean);
      if (Array.isArray(v)) return v.some(x => arr.includes(String(x)));
      return arr.includes(String(vs));
    }

    if (op === 'regex') {
      try {
        const re = new RegExp(String(expected ?? ''));
        return re.test(String(vs));
      } catch(e){ return false; }
    }

    return false;
  }

  function applyActions(actions){
    if (!Array.isArray(actions)) return;
    actions.forEach(a => {
      const action = a.action;
      const targetType = a.targetType || 'field';
      const target = a.target;

      if (!action || !target) return;

      if (action === 'show') return showTarget(targetType, target, true);
      if (action === 'hide') return showTarget(targetType, target, false);

      if (targetType !== 'field') return;

      if (action === 'setRequired') return setRequiredTarget(target, !!a.value);
      if (action === 'setEnabled') return setEnabledTarget(target, !!a.value);
      if (action === 'setValue') return setValue(target, a.value);
      if (action === 'clearValue') return setValue(target, '');
      if (action === 'setMax') return setMaxTarget(target, a.value);
      if (action === 'maxFromField') {
        const src = String(a.value || '');
        const srcVal = getValue(src);
        return setMaxTarget(target, srcVal);
      }
    });
  }

  function runRules(){
    (schema.rules || []).forEach(r => {
      const when = Array.isArray(r.when) ? r.when : [];
      const ok = when.every(evalCond);

      if (ok) applyActions(r.then);
      else if (Array.isArray(r.else)) applyActions(r.else);
    });
  }

  // -------- payload --------
  function buildPayload(){
    const payload = {};
    const wraps = qsa('.field-wrap');
    wraps.forEach(w => {
      const name = w.dataset.fieldName;
      const el = getFieldElByName(name);
      if (!el) return;

      // se campo hidden por regra, ainda pode salvar ou não. Aqui: NÃO salva
      if (w.dataset.hidden === '1' || w.style.display === 'none') return;

      payload[name] = getValue(name);
    });
    return payload;
  }

  function refreshPayload(){
    const p = buildPayload();
    payloadOut.textContent = JSON.stringify(p, null, 2);
  }

  // -------- init --------
  // 1) datasets
  (async function init(){
  const wraps = qsa('.field-wrap');

  // datasets
  for (const w of wraps) {
    try { await fillDatasetSelectOrDatalist(w); } catch(e){ /* ignora */ }
  }

  // presets
  wraps.forEach(w => {
    const name = w.dataset.fieldName;
    const preset = w.dataset.preset || '';
    const el = getFieldElByName(name);
    if (el) applyPreset(el, preset);
  });

  runRules();
  refreshPayload();
})();


  // 2) presets
  qsa('.field-wrap').forEach(w => {
    const name = w.dataset.fieldName;
    const preset = w.dataset.preset || '';
    const el = getFieldElByName(name);
    if (!el) return;
    applyPreset(el, preset);
  });

  // 3) listeners
  form.addEventListener('input', function(e){
    // valida campo do evento
    const wrap = e.target.closest('.field-wrap');
    if (wrap) validateField(wrap);
    runRules();
    refreshPayload();
  });
  form.addEventListener('change', function(){
    runRules();
    refreshPayload();
  });

  // 4) submit
  form.addEventListener('submit', function(e){
    e.preventDefault();

    runRules();

    let ok = true;
    qsa('.field-wrap').forEach(w => {
      if (!validateField(w)) ok = false;
    });

    refreshPayload();

    if (!ok) {
      alert('Existem campos inválidos. Corrija e tente novamente.');
      return;
    }

    // aqui entra a integração real (BPM/helpdesk). Por enquanto só mostra payload.
    const payload = buildPayload();

const body = {
  form_code: form.querySelector('[name="__form_code"]').value,
  version: parseInt(form.querySelector('[name="__form_version"]').value, 10),
  payload,
  meta: { source: "runtime" }
};

const r = await fetch(api.submit, {
  method: 'POST',
  headers: {'Content-Type':'application/json'},
  body: JSON.stringify(body),
  credentials:'same-origin'
});
const j = await r.json();
if (!j || !j.ok) {
  alert('Falha ao salvar: ' + (j?.error || 'erro'));
  return;
}
alert('Salvo! Submission ID: ' + j.id);

  });

  // dump
  btnDump.addEventListener('click', function(){
    const dump = {
      datasets: schema.datasets,
      rules: schema.rules,
      payload: buildPayload()
    };
    payloadOut.textContent = JSON.stringify(dump, null, 2);
  });

  // 5) primeira execução
  runRules();
  refreshPayload();

})();
</script>

</body>
</html>
