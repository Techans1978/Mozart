<?php
// public/modules/forms/wizard/3.php — Wizard Step 3: Campos por seção
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../../config.php';
require_once ROOT_PATH . '/system/config/autenticacao.php';
require_once ROOT_PATH . '/system/config/connect.php';

if (!isset($conn) && isset($mysqli)) { $conn = $mysqli; }
if (!($conn instanceof mysqli)) { die('Conexão MySQLi $conn não encontrada.'); }

if (session_status() !== PHP_SESSION_ACTIVE) session_start();
proteger_pagina();

$conn->set_charset('utf8mb4');
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$flash = $_SESSION['__flash']['m'] ?? '';
unset($_SESSION['__flash']);

$wiz = $_SESSION['forms_wizard'] ?? [];
$formId = (int)($_GET['form_id'] ?? ($wiz['form_id'] ?? 0));
if ($formId <= 0) die('Wizard sem contexto. Volte ao Step 1.');

$stmt = $conn->prepare("SELECT id, code, title, status, current_version FROM forms_form WHERE id=? LIMIT 1");
$stmt->bind_param("i", $formId);
$stmt->execute();
$form = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$form) die('Formulário não encontrado.');

$curVer = max(1, (int)$form['current_version']);

$stmt = $conn->prepare("SELECT id, schema_json, status FROM forms_form_version WHERE form_id=? AND version=? LIMIT 1");
$stmt->bind_param("ii", $formId, $curVer);
$stmt->execute();
$ver = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$ver) die('Versão não encontrada.');
if (($ver['status'] ?? '') !== 'draft') die('Essa versão não está em draft.');

$schema = json_decode($ver['schema_json'], true);
if (!is_array($schema)) $schema = ['meta'=>[],'sections'=>[],'globals'=>[]];
if (!isset($schema['sections']) || !is_array($schema['sections'])) $schema['sections'] = [];

$sections = $schema['sections'];
if (!count($sections)) {
  echo "Nenhuma seção encontrada. Volte ao Step 2 e crie pelo menos uma seção.";
  exit;
}

$sectionId = (string)($_GET['section_id'] ?? ($sections[0]['id'] ?? ''));
$section = null;
$sectionIndex = -1;

for ($i=0; $i<count($sections); $i++) {
  if ((string)($sections[$i]['id'] ?? '') === $sectionId) {
    $section = $sections[$i];
    $sectionIndex = $i;
    break;
  }
}
if (!$section) { $section = $sections[0]; $sectionIndex = 0; $sectionId = (string)($section['id'] ?? ''); }

if (!isset($section['fields']) || !is_array($section['fields'])) $section['fields'] = [];
$fields = $section['fields'];

$editId = (string)($_GET['edit'] ?? '');
$editField = null;
if ($editId !== '') {
  foreach ($fields as $f) {
    if ((string)($f['id'] ?? '') === $editId) { $editField = $f; break; }
  }
}

// Defaults para editor
$df = $editField ?: [];
$df_id = (string)($df['id'] ?? '');
$df_name = (string)($df['name'] ?? '');
$df_label = (string)($df['label'] ?? '');
$df_type = (string)($df['type'] ?? 'text');
$df_col = (int)($df['col'] ?? 6);
$df_required = !empty($df['required']);
$df_requiredMark = array_key_exists('requiredMark',$df) ? (bool)$df['requiredMark'] : true;

$df_placeholder = (string)($df['placeholder'] ?? '');
$df_help = (string)($df['help'] ?? '');
$df_default = (string)($df['defaultValue'] ?? '');

$df_format = (string)($df['format'] ?? '');              // ex: "R$ #,##0.00"
$df_auto = (string)($df['autoFormatOnType'] ?? '');      // ex: "money_br"
$df_multiple = !empty($df['multiple']);

$validators = $df['validators'] ?? [];
if (!is_array($validators)) $validators = [];

function find_validator(array $validators, string $type) {
  foreach ($validators as $v) {
    if (is_array($v) && (string)($v['type'] ?? '') === $type) return $v;
  }
  return null;
}

$val_preset = (string)($df['validationPreset'] ?? ''); // nosso atalho opcional

$val_min = '';
$val_max = '';
$val_minlen = '';
$val_maxlen = '';
$val_pattern = '';

$vmin = find_validator($validators,'min'); if ($vmin) $val_min = (string)($vmin['value'] ?? '');
$vmax = find_validator($validators,'max'); if ($vmax) $val_max = (string)($vmax['value'] ?? '');
$vminl = find_validator($validators,'minLength'); if ($vminl) $val_minlen = (string)($vminl['value'] ?? '');
$vmaxl = find_validator($validators,'maxLength'); if ($vmaxl) $val_maxlen = (string)($vmaxl['value'] ?? '');
$vpat = find_validator($validators,'pattern'); if ($vpat) $val_pattern = (string)($vpat['value'] ?? '');

$adv_dataset = $df['dataset'] ?? null;
$adv_rules   = $df['rules'] ?? null;
$adv_js      = $df['js'] ?? null;

$adv_dataset_json = $adv_dataset ? json_encode($adv_dataset, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT) : '';
$adv_rules_json   = $adv_rules ? json_encode($adv_rules, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT) : '';
$adv_js_json      = $adv_js ? json_encode($adv_js, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT) : '';

$types = [
  'text'=>'text', 'password'=>'password', 'email'=>'email', 'number'=>'number', 'tel'=>'tel',
  'url'=>'url', 'search'=>'search',
  'date'=>'date', 'time'=>'time', 'datetime-local'=>'datetime-local', 'month'=>'month', 'week'=>'week',
  'color'=>'color', 'range'=>'range',
  'checkbox'=>'checkbox', 'radio'=>'radio', 'file'=>'file', 'hidden'=>'hidden',
  'textarea'=>'textarea', 'select'=>'select', 'datalist'=>'datalist',
  'button'=>'button', 'submit'=>'submit', 'reset'=>'reset'
];

$presets = [
  ''=>'— nenhum —',
  'cpf'=>'CPF',
  'cnpj'=>'CNPJ',
  'cpf_cnpj'=>'CPF/CNPJ',
  'money_br'=>'Moeda BR',
  'time_hhmm'=>'Hora (HH:MM)',
  'phone_br'=>'Telefone BR',
  'cep'=>'CEP',
  'integer'=>'Inteiro',
  'decimal'=>'Decimal',
  'email'=>'E-mail',
  'url'=>'URL',
];

?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Forms AI • Wizard • Step 3</title>
  <link rel="stylesheet" href="<?php echo h(BASE_URL); ?>/assets/css/bootstrap.min.css">
  <style>
    .mono{ font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono","Courier New", monospace; }
    .req::after{ content:" *"; color:#dc3545; font-weight:700; }
    .hint{ font-size:.9rem; color:#6c757d; }
    .cardish{ border:1px solid rgba(0,0,0,.08); border-radius:12px; padding:12px; background:#fff; }
    textarea.mono{ font-size:.88rem; }
  </style>
</head>
<body class="bg-light">
<div class="container py-4">

  <div class="d-flex align-items-center justify-content-between mb-2">
    <div>
      <h3 class="mb-0">Criador de Formulários por IA</h3>
      <div class="text-muted">
        Wizard • Step 3/10 — Campos •
        <span class="mono"><?php echo h($form['code']); ?></span> • v<?php echo (int)$curVer; ?> •
        <span class="badge bg-warning"><?php echo h($ver['status']); ?></span>
      </div>
    </div>
    <div class="d-flex gap-2">
      <a class="btn btn-outline-secondary" href="<?php echo h(BASE_URL); ?>/public/modules/forms/wizard/2.php?form_id=<?php echo (int)$formId; ?>">← Step 2</a>

      <button class="btn btn-outline-primary"
              type="button"
              data-bs-toggle="modal"
              data-bs-target="#previewModal"
              data-preview-mode="section">
        Preview Seção
      </button>

      <a class="btn btn-outline-primary"
        target="_blank"
        href="<?php echo h(BASE_URL); ?>/public/modules/forms/wizard/preview.php?form_id=<?php echo (int)$formId; ?>&mode=all">
        Preview Form (Nova Aba)
      </a>

      <a class="btn btn-outline-secondary" href="<?php echo h(BASE_URL); ?>/public/modules/forms/forms_listar.php">Catálogo</a>
    </div>
  </div>

  <?php if ($flash): ?>
    <div class="alert alert-warning"><?php echo h($flash); ?></div>
  <?php endif; ?>

  <div class="row g-3">

    <!-- Esquerda: Seções + lista de campos -->
    <div class="col-lg-6">
      <div class="card shadow-sm">
        <div class="card-body">

          <div class="d-flex justify-content-between align-items-center mb-2">
            <h5 class="mb-0">Seção</h5>
            <div class="text-muted small">Escolha onde editar</div>
          </div>

          <form method="get" class="row g-2 align-items-end mb-3">
            <input type="hidden" name="form_id" value="<?php echo (int)$formId; ?>">
            <div class="col-9">
              <label class="form-label">Seções</label>
              <select class="form-select" name="section_id" onchange="this.form.submit()">
                <?php foreach ($sections as $s): ?>
                  <?php $sid = (string)($s['id'] ?? ''); ?>
                  <option value="<?php echo h($sid); ?>" <?php echo ($sid===$sectionId?'selected':''); ?>>
                    <?php echo h((string)($s['title'] ?? $sid)); ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <div class="hint mt-1">Campos são salvos dentro da seção selecionada.</div>
            </div>
            <div class="col-3">
              <a class="btn btn-outline-secondary w-100"
                 href="<?php echo h(BASE_URL); ?>/public/modules/forms/wizard/3.php?form_id=<?php echo (int)$formId; ?>&section_id=<?php echo h($sectionId); ?>">
                Novo campo
              </a>
            </div>
          </form>

          <div class="d-flex justify-content-between align-items-center">
            <h6 class="mb-0">Campos da seção</h6>
            <span class="text-muted small"><?php echo count($fields); ?> campo(s)</span>
          </div>

          <div class="mt-2 d-grid gap-2">
            <?php if (!count($fields)): ?>
              <div class="text-muted">Nenhum campo nesta seção ainda.</div>
            <?php endif; ?>

            <?php foreach($fields as $idx => $f): ?>
              <?php
                $fid = (string)($f['id'] ?? '');
                $flabel = (string)($f['label'] ?? '(sem label)');
                $fname = (string)($f['name'] ?? '');
                $ftype = (string)($f['type'] ?? '');
                $fcol = (int)($f['col'] ?? 12);
                $freq = !empty($f['required']);
              ?>
              <div class="cardish">
                <div class="d-flex justify-content-between">
                  <div>
                    <div class="fw-semibold">
                      <?php echo h($flabel); ?>
                      <?php if ($freq): ?><span class="text-danger">*</span><?php endif; ?>
                    </div>
                    <div class="hint">
                      ID: <span class="mono"><?php echo h($fid); ?></span> •
                      name: <span class="mono"><?php echo h($fname); ?></span> •
                      type: <span class="mono"><?php echo h($ftype); ?></span> •
                      col: <span class="mono"><?php echo (int)$fcol; ?></span>
                    </div>
                  </div>

                  <div class="d-flex gap-1">
                    <form method="post" action="<?php echo h(BASE_URL); ?>/modules/forms/actions/forms_fields_move.php" class="d-inline">
                      <input type="hidden" name="form_id" value="<?php echo (int)$formId; ?>">
                      <input type="hidden" name="section_id" value="<?php echo h($sectionId); ?>">
                      <input type="hidden" name="field_id" value="<?php echo h($fid); ?>">
                      <input type="hidden" name="dir" value="up">
                      <button class="btn btn-sm btn-outline-secondary" <?php echo ($idx===0?'disabled':''); ?>>↑</button>
                    </form>

                    <form method="post" action="<?php echo h(BASE_URL); ?>/modules/forms/actions/forms_fields_move.php" class="d-inline">
                      <input type="hidden" name="form_id" value="<?php echo (int)$formId; ?>">
                      <input type="hidden" name="section_id" value="<?php echo h($sectionId); ?>">
                      <input type="hidden" name="field_id" value="<?php echo h($fid); ?>">
                      <input type="hidden" name="dir" value="down">
                      <button class="btn btn-sm btn-outline-secondary" <?php echo ($idx===count($fields)-1?'disabled':''); ?>>↓</button>
                    </form>

                    <a class="btn btn-sm btn-outline-primary"
                       href="<?php echo h(BASE_URL); ?>/public/modules/forms/wizard/3.php?form_id=<?php echo (int)$formId; ?>&section_id=<?php echo h($sectionId); ?>&edit=<?php echo h($fid); ?>">
                      Editar
                    </a>

                    <form method="post" action="<?php echo h(BASE_URL); ?>/modules/forms/actions/forms_fields_delete.php"
                          class="d-inline"
                          onsubmit="return confirm('Excluir o campo: <?php echo h(addslashes($flabel)); ?> ?');">
                      <input type="hidden" name="form_id" value="<?php echo (int)$formId; ?>">
                      <input type="hidden" name="section_id" value="<?php echo h($sectionId); ?>">
                      <input type="hidden" name="field_id" value="<?php echo h($fid); ?>">
                      <button class="btn btn-sm btn-outline-danger">Excluir</button>
                    </form>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>

          <hr class="my-3">
          <div class="d-flex justify-content-between">
            <a class="btn btn-outline-secondary" href="<?php echo h(BASE_URL); ?>/public/modules/forms/wizard/2.php?form_id=<?php echo (int)$formId; ?>">← Step 2</a>
            <a class="btn btn-primary disabled" href="#">Step 4 (Validações avançadas / Regras visuais) →</a>
          </div>

        </div>
      </div>
    </div>

    <!-- Direita: Editor de campo -->
    <div class="col-lg-6">
      <div class="card shadow-sm">
        <div class="card-body">

          <div class="d-flex justify-content-between align-items-center mb-1">
            <h5 class="mb-0"><?php echo $editField ? 'Editar campo' : 'Adicionar campo'; ?></h5>
            <div class="text-muted small">Seção: <?php echo h((string)($section['title'] ?? $sectionId)); ?></div>
          </div>

          <form method="post" action="<?php echo h(BASE_URL); ?>/modules/forms/actions/forms_fields_save.php">
            <input type="hidden" name="form_id" value="<?php echo (int)$formId; ?>">
            <input type="hidden" name="section_id" value="<?php echo h($sectionId); ?>">
            <input type="hidden" name="field_id" value="<?php echo h($df_id); ?>">

            <div class="row g-3">

              <div class="col-md-6">
                <label class="form-label req">Label</label>
                <input class="form-control" name="label" maxlength="160" required
                       value="<?php echo h($df_label); ?>"
                       placeholder="Ex: CPF do solicitante">
              </div>

              <div class="col-md-6">
                <label class="form-label req">Name (chave)</label>
                <input class="form-control mono" name="name" maxlength="80" required
                       value="<?php echo h($df_name); ?>"
                       placeholder="ex: cpf_solicitante">
                <div class="hint mt-1">Usado no payload final. Sem espaços. (a action normaliza)</div>
              </div>

              <div class="col-md-4">
                <label class="form-label req">Tipo</label>
                <select class="form-select" name="type" required>
                  <?php foreach($types as $k=>$lbl): ?>
                    <option value="<?php echo h($k); ?>" <?php echo ($df_type===$k?'selected':''); ?>>
                      <?php echo h($lbl); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="col-md-4">
                <label class="form-label req">Colunas (1..12)</label>
                <input type="number" class="form-control" name="col" min="1" max="12" required
                       value="<?php echo (int)$df_col; ?>">
                <div class="hint mt-1">Ex: 4-4-4 / 6-6 / 12</div>
              </div>

              <div class="col-md-4 d-flex align-items-end">
                <div class="w-100">
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="required" value="1" id="required"
                           <?php echo $df_required ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="required">Obrigatório</label>
                  </div>
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="requiredMark" value="1" id="requiredMark"
                           <?php echo $df_requiredMark ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="requiredMark">Exibir marcador *</label>
                  </div>
                </div>
              </div>

              <div class="col-md-6">
                <label class="form-label">Placeholder</label>
                <input class="form-control" name="placeholder" maxlength="160"
                       value="<?php echo h($df_placeholder); ?>">
              </div>

              <div class="col-md-6">
                <label class="form-label">Valor padrão</label>
                <input class="form-control" name="defaultValue" maxlength="255"
                       value="<?php echo h($df_default); ?>">
              </div>

              <div class="col-12">
                <label class="form-label">Ajuda (help)</label>
                <input class="form-control" name="help" maxlength="255"
                       value="<?php echo h($df_help); ?>">
              </div>

              <hr class="my-2">

              <div class="col-md-6">
                <label class="form-label">Preset BR (atalho)</label>
                <select class="form-select" name="validationPreset">
                  <?php foreach($presets as $k=>$lbl): ?>
                    <option value="<?php echo h($k); ?>" <?php echo ($val_preset===$k?'selected':''); ?>>
                      <?php echo h($lbl); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
                <div class="hint mt-1">Isso pode gerar format + validators automaticamente.</div>
              </div>

              <div class="col-md-6">
                <label class="form-label">Formato/Máscara (opcional)</label>
                <input class="form-control mono" name="format" maxlength="80"
                       value="<?php echo h($df_format); ?>"
                       placeholder='Ex: R$ #,##0.00  |  000.000.000-00'>
              </div>

              <div class="col-md-6">
                <label class="form-label">Auto-format ao digitar</label>
                <input class="form-control mono" name="autoFormatOnType" maxlength="40"
                       value="<?php echo h($df_auto); ?>"
                       placeholder="Ex: money_br">
              </div>

              <div class="col-md-6 d-flex align-items-end">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="multiple" value="1" id="multiple"
                         <?php echo $df_multiple ? 'checked' : ''; ?>>
                  <label class="form-check-label" for="multiple">Multiple (select)</label>
                </div>
              </div>

              <hr class="my-2">

              <div class="col-md-3">
                <label class="form-label">min</label>
                <input class="form-control mono" name="val_min" value="<?php echo h($val_min); ?>" placeholder="0">
              </div>
              <div class="col-md-3">
                <label class="form-label">max</label>
                <input class="form-control mono" name="val_max" value="<?php echo h($val_max); ?>" placeholder="100">
              </div>
              <div class="col-md-3">
                <label class="form-label">minLength</label>
                <input class="form-control mono" name="val_minlen" value="<?php echo h($val_minlen); ?>" placeholder="3">
              </div>
              <div class="col-md-3">
                <label class="form-label">maxLength</label>
                <input class="form-control mono" name="val_maxlen" value="<?php echo h($val_maxlen); ?>" placeholder="60">
              </div>

              <div class="col-12">
                <label class="form-label">pattern (Regex)</label>
                <input class="form-control mono" name="val_pattern" value="<?php echo h($val_pattern); ?>"
                       placeholder="Ex: ^[0-9]{11}$">
              </div>

              <hr class="my-2">

              <div class="col-12">
                <label class="form-label">Dataset (JSON avançado)</label>
                <textarea class="form-control mono" rows="5" name="adv_dataset"
                          placeholder='Ex:
{
  "mode":"static",
  "options":[{"value":"1","label":"Sim"},{"value":"0","label":"Não"}]
}
'><?php echo h($adv_dataset_json); ?></textarea>
                <div class="hint mt-1">Usado para select/datalist/autocomplete. Pode ser static/endpoint/sql.</div>
              </div>

              <div class="col-12">
                <label class="form-label">Regras (JSON avançado)</label>
                <textarea class="form-control mono" rows="5" name="adv_rules"
                          placeholder='Ex:
[
  {"when":{"notEmpty":"cpf"},"then":[{"action":"show","target":"sec_aprovacao"}]}
]
'><?php echo h($adv_rules_json); ?></textarea>
              </div>

              <div class="col-12">
                <label class="form-label">JS Hooks (JSON avançado)</label>
                <textarea class="form-control mono" rows="5" name="adv_js"
                          placeholder='Ex:
{
  "onChange":{"ref":"calcTotal"},
  "onBlur":{"ref":"normalizeMoney"}
}
'><?php echo h($adv_js_json); ?></textarea>
              </div>

            </div>

            <hr class="my-3">

            <div class="d-flex justify-content-between">
              <?php if ($editField): ?>
                <a class="btn btn-outline-secondary"
                   href="<?php echo h(BASE_URL); ?>/public/modules/forms/wizard/3.php?form_id=<?php echo (int)$formId; ?>&section_id=<?php echo h($sectionId); ?>">
                  Cancelar
                </a>
              <?php else: ?>
                <span></span>
              <?php endif; ?>

              <button class="btn btn-primary">
                <?php echo $editField ? 'Salvar campo' : 'Adicionar campo'; ?>
              </button>
            </div>

          </form>

        </div>
      </div>

      <div class="mt-2 text-muted small">
        Dica: a gente deixou dataset/rules/js via JSON agora pra não travar o avanço.
        Depois eu monto editor visual desses 3 blocos.
      </div>
    </div>

  </div>
</div>

<script>
  // Normalização client-side simples do "name"
  (function(){
    const el = document.querySelector('input[name="name"]');
    if(!el) return;
    el.addEventListener('input', () => {
      let v = el.value || '';
      v = v.replace(/\s+/g,'_');
      v = v.replace(/[^a-zA-Z0-9_]/g,'_');
      v = v.replace(/_+/g,'_');
      el.value = v.toLowerCase();
    });
  })();
</script>

<!-- Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <div>
          <div class="fw-semibold">Preview</div>
          <div class="text-muted small">Grid 12 colunas • seção atual</div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>
      <div class="modal-body p-0" style="height: 75vh;">
        <iframe id="previewFrame" src="about:blank" style="border:0; width:100%; height:100%;"></iframe>
      </div>
      <div class="modal-footer">
        <a class="btn btn-outline-primary" target="_blank" id="previewOpenNewTab" href="#">Abrir em nova aba</a>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
      </div>
    </div>
  </div>
</div>

<script src="<?php echo h(BASE_URL); ?>/assets/js/bootstrap.bundle.min.js"></script>
<script>
(function(){
  const modalEl   = document.getElementById('previewModal');
  const frame     = document.getElementById('previewFrame');
  const openNewTab= document.getElementById('previewOpenNewTab');
  if(!modalEl || !frame || !openNewTab) return;

  // Base para montar URL do preview
  const basePreview = "<?php echo h(BASE_URL); ?>/public/modules/forms/wizard/preview.php";
  const formId      = "<?php echo (int)$formId; ?>";

  // sectionId atual (o mesmo que você está editando)
  function currentSectionId(){
    return "<?php echo h($sectionId); ?>";
  }

  modalEl.addEventListener('show.bs.modal', function (event) {
    // botão que abriu o modal
    const btn = event.relatedTarget;
    let mode = "section"; // default

    if (btn && btn.dataset && btn.dataset.previewMode) {
      mode = btn.dataset.previewMode;
    }

    const sec = currentSectionId();

    // URL final
    let url = basePreview + "?form_id=" + encodeURIComponent(formId);

    if (mode === "all") {
      url += "&mode=all";
    } else {
      url += "&mode=section&section_id=" + encodeURIComponent(sec);
    }

    // Seta iframe e link nova aba
    frame.src = url;
    openNewTab.href = url;

    // Texto do modal (opcional)
    const subtitle = modalEl.querySelector('.modal-header .text-muted');
    if (subtitle) {
      subtitle.textContent = (mode === "all")
        ? "Grid 12 colunas • formulário completo"
        : "Grid 12 colunas • seção atual";
    }
  });

  // Limpa iframe ao fechar (evita ficar tocando / segurando memória)
  modalEl.addEventListener('hidden.bs.modal', function(){
    frame.src = "about:blank";
  });
})();
</script>

</body>
</html>
