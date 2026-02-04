<?php
// public/modules/forms/wizard/2.php — Wizard Step 2: Seções
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

$schema = json_decode($ver['schema_json'], true);
if (!is_array($schema)) $schema = ['meta'=>[],'sections'=>[],'globals'=>[]];

if (!isset($schema['sections']) || !is_array($schema['sections'])) $schema['sections'] = [];

$sections = $schema['sections'];

// edição (se existir ?edit=sec_id)
$editId = (string)($_GET['edit'] ?? '');
$editSec = null;
if ($editId !== '') {
  foreach ($sections as $s) {
    if (($s['id'] ?? '') === $editId) { $editSec = $s; break; }
  }
}

$defaultTitle = $editSec['title'] ?? '';
$defaultDesc  = $editSec['description'] ?? '';
$defaultGap   = (int)($editSec['layout']['gap'] ?? 12);
?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Forms AI • Wizard • Step 2</title>
  <link rel="stylesheet" href="<?php echo h(BASE_URL); ?>/assets/css/bootstrap.min.css">
  <style>
    .mono{ font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; }
    .req::after{ content:" *"; color:#dc3545; font-weight:700; }
    .hint{ font-size:.9rem; color:#6c757d; }
    .sec-card{ border:1px solid rgba(0,0,0,.08); border-radius:12px; padding:12px; background:#fff; }
  </style>
</head>
<body class="bg-light">
<div class="container py-4">

  <div class="d-flex align-items-center justify-content-between mb-2">
    <div>
      <h3 class="mb-0">Criador de Formulários por IA</h3>
      <div class="text-muted">
        Wizard • Step 2/10 — Seções •
        <span class="mono"><?php echo h($form['code']); ?></span> • v<?php echo (int)$curVer; ?> •
        <span class="badge bg-<?php echo ($form['status']==='published'?'success':($form['status']==='archived'?'secondary':'warning')); ?>">
          <?php echo h($form['status']); ?>
        </span>
      </div>
    </div>
    <div class="d-flex gap-2">
      <a class="btn btn-outline-secondary" href="<?php echo h(BASE_URL); ?>/public/modules/forms/forms_listar.php">Catálogo</a>
      <a class="btn btn-outline-secondary" href="<?php echo h(BASE_URL); ?>/public/modules/forms/wizard/1.php">Novo por IA</a>
    </div>
  </div>

  <?php if ($flash): ?>
    <div class="alert alert-warning"><?php echo h($flash); ?></div>
  <?php endif; ?>

  <div class="row g-3">
    <!-- Coluna esquerda: lista de seções -->
    <div class="col-lg-7">
      <div class="card shadow-sm">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <h5 class="mb-0">Seções</h5>
            <div class="text-muted small"><?php echo count($sections); ?> seção(ões)</div>
          </div>

          <?php if (!count($sections)): ?>
            <div class="text-muted">Nenhuma seção criada ainda. Adicione uma seção ao lado.</div>
          <?php else: ?>
            <div class="d-grid gap-2">
              <?php foreach($sections as $idx => $s): ?>
                <?php
                  $sid = (string)($s['id'] ?? '');
                  $stitle = (string)($s['title'] ?? '(sem título)');
                  $sdesc = (string)($s['description'] ?? '');
                  $fieldsCount = is_array($s['fields'] ?? null) ? count($s['fields']) : 0;
                ?>
                <div class="sec-card">
                  <div class="d-flex justify-content-between">
                    <div>
                      <div class="fw-semibold"><?php echo h($stitle); ?></div>
                      <div class="hint">
                        ID: <span class="mono"><?php echo h($sid); ?></span> •
                        Campos: <?php echo (int)$fieldsCount; ?>
                        <?php if ($sdesc !== ''): ?> • <?php echo h($sdesc); ?><?php endif; ?>
                      </div>
                    </div>
                    <div class="d-flex gap-1">
                      <!-- Move up -->
                      <form method="post" action="<?php echo h(BASE_URL); ?>/modules/forms/actions/forms_sections_move.php" class="d-inline">
                        <input type="hidden" name="form_id" value="<?php echo (int)$formId; ?>">
                        <input type="hidden" name="section_id" value="<?php echo h($sid); ?>">
                        <input type="hidden" name="dir" value="up">
                        <button class="btn btn-sm btn-outline-secondary" <?php echo ($idx===0?'disabled':''); ?> title="Subir">↑</button>
                      </form>
                      <!-- Move down -->
                      <form method="post" action="<?php echo h(BASE_URL); ?>/modules/forms/actions/forms_sections_move.php" class="d-inline">
                        <input type="hidden" name="form_id" value="<?php echo (int)$formId; ?>">
                        <input type="hidden" name="section_id" value="<?php echo h($sid); ?>">
                        <input type="hidden" name="dir" value="down">
                        <button class="btn btn-sm btn-outline-secondary" <?php echo ($idx===count($sections)-1?'disabled':''); ?> title="Descer">↓</button>
                      </form>

                      <a class="btn btn-sm btn-outline-primary"
                         href="<?php echo h(BASE_URL); ?>/public/modules/forms/wizard/2.php?form_id=<?php echo (int)$formId; ?>&edit=<?php echo h($sid); ?>">
                        Editar
                      </a>

                      <form method="post"
                            action="<?php echo h(BASE_URL); ?>/modules/forms/actions/forms_sections_delete.php"
                            class="d-inline"
                            onsubmit="return confirm('Excluir a seção: <?php echo h(addslashes($stitle)); ?> ?');">
                        <input type="hidden" name="form_id" value="<?php echo (int)$formId; ?>">
                        <input type="hidden" name="section_id" value="<?php echo h($sid); ?>">
                        <button class="btn btn-sm btn-outline-danger">Excluir</button>
                      </form>
                    </div>
                  </div>

                  <!-- Próximo passo -->
                  <div class="mt-2">
                    <a class="btn btn-sm btn-outline-dark disabled" href="#">
                      Step 3 (Campos) — em seguida
                    </a>
                    <span class="hint ms-2">No Step 3 a gente monta os campos dessa seção.</span>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>

          <hr class="my-3">

          <div class="d-flex justify-content-between">
            <a class="btn btn-outline-secondary" href="<?php echo h(BASE_URL); ?>/public/modules/forms/wizard/1.php">← Step 1</a>
            <a class="btn btn-primary disabled" href="#">Ir para Step 3 (Campos) →</a>
          </div>
        </div>
      </div>
    </div>

    <!-- Coluna direita: formulário add/edit seção -->
    <div class="col-lg-5">
      <div class="card shadow-sm">
        <div class="card-body">
          <h5 class="mb-1"><?php echo $editSec ? 'Editar seção' : 'Adicionar seção'; ?></h5>
          <div class="hint mb-3">Seção é um “bloco” que organiza campos. O layout base (gap) pode ser ajustado.</div>

          <form method="post" action="<?php echo h(BASE_URL); ?>/modules/forms/actions/forms_sections_save.php">
            <input type="hidden" name="form_id" value="<?php echo (int)$formId; ?>">
            <input type="hidden" name="section_id" value="<?php echo h($editSec['id'] ?? ''); ?>">

            <div class="mb-3">
              <label class="form-label req">Título da seção</label>
              <input class="form-control" name="title" maxlength="160" required
                     value="<?php echo h($defaultTitle); ?>"
                     placeholder="Ex: Dados do Solicitante">
            </div>

            <div class="mb-3">
              <label class="form-label">Descrição</label>
              <textarea class="form-control" name="description" rows="3"
                        placeholder="Opcional"><?php echo h($defaultDesc); ?></textarea>
            </div>

            <div class="mb-3">
              <label class="form-label">Layout • Gap (px)</label>
              <input type="number" class="form-control" name="gap" min="0" max="48"
                     value="<?php echo (int)$defaultGap; ?>">
              <div class="hint mt-1">Somente visual (espaçamento). O grid 12 colunas vem no Step 3 por campo.</div>
            </div>

            <div class="d-flex justify-content-between">
              <?php if ($editSec): ?>
                <a class="btn btn-outline-secondary"
                   href="<?php echo h(BASE_URL); ?>/public/modules/forms/wizard/2.php?form_id=<?php echo (int)$formId; ?>">
                  Cancelar
                </a>
              <?php else: ?>
                <span></span>
              <?php endif; ?>

              <button class="btn btn-primary">
                <?php echo $editSec ? 'Salvar seção' : 'Adicionar seção'; ?>
              </button>
            </div>

          </form>

        </div>
      </div>

      <div class="mt-3 text-muted small">
        Obs.: Step 2 altera somente <b>sections[]</b> no schema draft.
        Campos serão no Step 3.
      </div>
    </div>
  </div>

</div>
</body>
</html>
