<?php
// public/modules/forms/wizard/5.php — Wizard Step 5: Datasets (globais)
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

$curVer = max(1,(int)$form['current_version']);

$stmt = $conn->prepare("SELECT id, schema_json, status FROM forms_form_version WHERE form_id=? AND version=? LIMIT 1");
$stmt->bind_param("ii", $formId, $curVer);
$stmt->execute();
$ver = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$ver) die('Versão não encontrada.');
if (($ver['status'] ?? '') !== 'draft') die('Essa versão não está em draft.');

$schema = json_decode($ver['schema_json'], true);
if (!is_array($schema)) $schema = ['meta'=>[],'sections'=>[],'globals'=>[],'rules'=>[]];

if (!isset($schema['globals']) || !is_array($schema['globals'])) $schema['globals'] = [];
if (!isset($schema['globals']['datasets']) || !is_array($schema['globals']['datasets'])) $schema['globals']['datasets'] = [];

$datasets = $schema['globals']['datasets'];

$editId = (string)($_GET['edit'] ?? '');
$editDs = null;
if ($editId !== '') {
  foreach ($datasets as $d) {
    if ((string)($d['id'] ?? '') === $editId) { $editDs = $d; break; }
  }
}

$dd = $editDs ?: [];
$dd_id = (string)($dd['id'] ?? '');
$dd_name = (string)($dd['name'] ?? '');
$dd_mode = (string)($dd['mode'] ?? 'static');

$dd_key = (string)($dd['key'] ?? 'value');
$dd_label = (string)($dd['label'] ?? 'label');

$dd_endpoint = (string)($dd['endpoint'] ?? '');
$dd_method = (string)($dd['method'] ?? 'GET');

$dd_sql = (string)($dd['sql'] ?? '');
$dd_sql_params = isset($dd['params']) ? json_encode($dd['params'], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT) : '';

$dd_options = isset($dd['options']) ? json_encode($dd['options'], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT) : '';

$modes = [
  'static' => 'static (lista fixa)',
  'endpoint' => 'endpoint (URL)',
  'sql' => 'sql (consulta via backend)',
];

?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Forms AI • Wizard • Step 5</title>
  <link rel="stylesheet" href="<?php echo h(BASE_URL); ?>/assets/css/bootstrap.min.css">
  <style>
    .mono{ font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono","Courier New", monospace; }
    .hint{ font-size:.9rem; color:#6c757d; }
    .cardish{ border:1px solid rgba(0,0,0,.08); border-radius:12px; padding:12px; background:#fff; }
  </style>
</head>
<body class="bg-light">
<div class="container py-4">

  <div class="d-flex align-items-center justify-content-between mb-2">
    <div>
      <h3 class="mb-0">Criador de Formulários por IA</h3>
      <div class="text-muted">
        Wizard • Step 5/10 — Datasets •
        <span class="mono"><?php echo h($form['code']); ?></span> • v<?php echo (int)$curVer; ?> •
        <span class="badge bg-warning"><?php echo h($ver['status']); ?></span>
      </div>
    </div>
    <div class="d-flex gap-2">
      <a class="btn btn-outline-secondary" href="<?php echo h(BASE_URL); ?>/public/modules/forms/wizard/4.php?form_id=<?php echo (int)$formId; ?>">← Step 4</a>
      <a class="btn btn-outline-secondary" href="<?php echo h(BASE_URL); ?>/public/modules/forms/forms_listar.php">Catálogo</a>
    </div>
  </div>

  <?php if ($flash): ?>
    <div class="alert alert-warning"><?php echo h($flash); ?></div>
  <?php endif; ?>

  <div class="row g-3">

    <!-- Lista -->
    <div class="col-lg-6">
      <div class="card shadow-sm">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Datasets do formulário</h5>
            <a class="btn btn-sm btn-outline-primary"
               href="<?php echo h(BASE_URL); ?>/public/modules/forms/wizard/5.php?form_id=<?php echo (int)$formId; ?>">
              Novo dataset
            </a>
          </div>
          <div class="hint mt-1">Dataset é fonte de opções para select/datalist/autocomplete.</div>

          <div class="mt-3 d-grid gap-2">
            <?php if (!count($datasets)): ?>
              <div class="text-muted">Nenhum dataset criado ainda.</div>
            <?php endif; ?>

            <?php foreach($datasets as $idx => $d): ?>
              <?php
                $did = (string)($d['id'] ?? '');
                $dname = (string)($d['name'] ?? '(sem nome)');
                $dmode = (string)($d['mode'] ?? '');
              ?>
              <div class="cardish">
                <div class="d-flex justify-content-between">
                  <div>
                    <div class="fw-semibold"><?php echo h($dname); ?></div>
                    <div class="hint">ID: <span class="mono"><?php echo h($did); ?></span> • mode: <span class="mono"><?php echo h($dmode); ?></span></div>
                  </div>

                  <div class="d-flex gap-1">
                    <form method="post" action="<?php echo h(BASE_URL); ?>/modules/forms/actions/forms_datasets_move.php" class="d-inline">
                      <input type="hidden" name="form_id" value="<?php echo (int)$formId; ?>">
                      <input type="hidden" name="dataset_id" value="<?php echo h($did); ?>">
                      <input type="hidden" name="dir" value="up">
                      <button class="btn btn-sm btn-outline-secondary" <?php echo ($idx===0?'disabled':''); ?>>↑</button>
                    </form>

                    <form method="post" action="<?php echo h(BASE_URL); ?>/modules/forms/actions/forms_datasets_move.php" class="d-inline">
                      <input type="hidden" name="form_id" value="<?php echo (int)$formId; ?>">
                      <input type="hidden" name="dataset_id" value="<?php echo h($did); ?>">
                      <input type="hidden" name="dir" value="down">
                      <button class="btn btn-sm btn-outline-secondary" <?php echo ($idx===count($datasets)-1?'disabled':''); ?>>↓</button>
                    </form>

                    <a class="btn btn-sm btn-outline-primary"
                       href="<?php echo h(BASE_URL); ?>/public/modules/forms/wizard/5.php?form_id=<?php echo (int)$formId; ?>&edit=<?php echo h($did); ?>">
                      Editar
                    </a>

                    <a class="btn btn-sm btn-outline-dark"
                       href="<?php echo h(BASE_URL); ?>/public/modules/forms/wizard/5_preview.php?form_id=<?php echo (int)$formId; ?>&dataset_id=<?php echo h($did); ?>"
                       target="_blank">
                      Testar
                    </a>

                    <form method="post" action="<?php echo h(BASE_URL); ?>/modules/forms/actions/forms_datasets_delete.php"
                          class="d-inline"
                          onsubmit="return confirm('Excluir dataset: <?php echo h(addslashes($dname)); ?> ?');">
                      <input type="hidden" name="form_id" value="<?php echo (int)$formId; ?>">
                      <input type="hidden" name="dataset_id" value="<?php echo h($did); ?>">
                      <button class="btn btn-sm btn-outline-danger">Excluir</button>
                    </form>
                  </div>
                </div>

                <details class="mt-2">
                  <summary class="hint">Ver JSON</summary>
                  <pre class="mono small mt-2 mb-0"><?php echo h(json_encode($d, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT)); ?></pre>
                </details>
              </div>
            <?php endforeach; ?>
          </div>

          <hr class="my-3">
          <div class="d-flex justify-content-between">
            <a class="btn btn-outline-secondary" href="<?php echo h(BASE_URL); ?>/public/modules/forms/wizard/4.php?form_id=<?php echo (int)$formId; ?>">← Step 4</a>
            <a class="btn btn-primary disabled" href="#">Step 6 (Runtime / Render) →</a>
          </div>
        </div>
      </div>
    </div>

    <!-- Editor -->
    <div class="col-lg-6">
      <div class="card shadow-sm">
        <div class="card-body">

          <h5 class="mb-1"><?php echo $editDs ? 'Editar dataset' : 'Adicionar dataset'; ?></h5>
          <div class="hint mb-3">Você pode usar static agora e migrar depois para endpoint/sql.</div>

          <form method="post" action="<?php echo h(BASE_URL); ?>/modules/forms/actions/forms_datasets_save.php">
            <input type="hidden" name="form_id" value="<?php echo (int)$formId; ?>">
            <input type="hidden" name="dataset_id" value="<?php echo h($dd_id); ?>">

            <div class="row g-3">
              <div class="col-md-8">
                <label class="form-label">Nome</label>
                <input class="form-control" name="name" maxlength="180" required value="<?php echo h($dd_name); ?>"
                       placeholder="Ex: Departamentos">
              </div>
              <div class="col-md-4">
                <label class="form-label">Mode</label>
                <select class="form-select" name="mode">
                  <?php foreach($modes as $k=>$lbl): ?>
                    <option value="<?php echo h($k); ?>" <?php echo ($dd_mode===$k?'selected':''); ?>><?php echo h($lbl); ?></option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="col-md-6">
                <label class="form-label">Chave (key)</label>
                <input class="form-control mono" name="key" value="<?php echo h($dd_key); ?>" placeholder="value">
              </div>
              <div class="col-md-6">
                <label class="form-label">Label</label>
                <input class="form-control mono" name="label" value="<?php echo h($dd_label); ?>" placeholder="label">
              </div>

              <div class="col-12">
                <label class="form-label">Options (JSON) — usado no mode=static</label>
                <textarea class="form-control mono" rows="6" name="options"
                  placeholder='[{"value":"TI","label":"TI"},{"value":"RH","label":"RH"}]'><?php echo h($dd_options); ?></textarea>
              </div>

              <div class="col-12">
                <label class="form-label">Endpoint URL — usado no mode=endpoint</label>
                <input class="form-control mono" name="endpoint" value="<?php echo h($dd_endpoint); ?>"
                       placeholder="https://... ou /modules/..../endpoint.php">
              </div>

              <div class="col-md-4">
                <label class="form-label">Método</label>
                <select class="form-select" name="method">
                  <?php foreach(['GET','POST'] as $m): ?>
                    <option value="<?php echo $m; ?>" <?php echo ($dd_method===$m?'selected':''); ?>><?php echo $m; ?></option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="col-12">
                <label class="form-label">SQL — usado no mode=sql (executa via backend)</label>
                <textarea class="form-control mono" rows="5" name="sql"
                  placeholder="SELECT id AS value, nome AS label FROM ... WHERE ... LIMIT 50"><?php echo h($dd_sql); ?></textarea>
                <div class="hint mt-1">⚠️ Runtime vai exigir allowlist/tabelas seguras. Step 6/7 a gente implementa com segurança.</div>
              </div>

              <div class="col-12">
                <label class="form-label">Params (JSON) — usado no mode=sql (opcional)</label>
                <textarea class="form-control mono" rows="4" name="params"
                  placeholder='{"q":"{{search}}","loja":"{{user.loja}}"}'><?php echo h($dd_sql_params); ?></textarea>
              </div>
            </div>

            <hr class="my-3">

            <div class="d-flex justify-content-between">
              <?php if ($editDs): ?>
                <a class="btn btn-outline-secondary"
                   href="<?php echo h(BASE_URL); ?>/public/modules/forms/wizard/5.php?form_id=<?php echo (int)$formId; ?>">
                  Cancelar
                </a>
              <?php else: ?>
                <span></span>
              <?php endif; ?>

              <button class="btn btn-primary"><?php echo $editDs ? 'Salvar dataset' : 'Adicionar dataset'; ?></button>
            </div>

          </form>
        </div>
      </div>

      <div class="mt-2 text-muted small">
        Próximo: Step 6 vai usar datasets no runtime e ligar datasetRef nos campos.
      </div>
    </div>
  </div>
</div>
</body>
</html>
