<?php
// public/modules/forms/wizard/4.php — Wizard Step 4: Regras (IF/THEN)
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
if (!is_array($schema)) $schema = ['meta'=>[],'sections'=>[],'globals'=>[],'rules'=>[]];

if (!isset($schema['sections']) || !is_array($schema['sections'])) $schema['sections'] = [];
if (!isset($schema['rules']) || !is_array($schema['rules'])) $schema['rules'] = [];

$rules = $schema['rules'];

// montar catálogo de targets (campos e seções)
$sectionTargets = [];
$fieldTargets = []; // name => label

foreach ($schema['sections'] as $sec) {
  $sid = (string)($sec['id'] ?? '');
  $st  = (string)($sec['title'] ?? $sid);
  if ($sid) $sectionTargets[] = ['id'=>$sid,'label'=>$st];

  $fields = $sec['fields'] ?? [];
  if (is_array($fields)) {
    foreach ($fields as $f) {
      $name = (string)($f['name'] ?? '');
      $lbl  = (string)($f['label'] ?? $name);
      if ($name) $fieldTargets[$name] = $lbl;
    }
  }
}
ksort($fieldTargets);

// edição
$editId = (string)($_GET['edit'] ?? '');
$editRule = null;
if ($editId !== '') {
  foreach ($rules as $r) {
    if ((string)($r['id'] ?? '') === $editId) { $editRule = $r; break; }
  }
}

// defaults
$dr = $editRule ?: [];
$dr_id = (string)($dr['id'] ?? '');
$dr_name = (string)($dr['name'] ?? '');
$dr_when = $dr['when'] ?? [];
$dr_then = $dr['then'] ?? [];
$dr_else = $dr['else'] ?? [];

if (!is_array($dr_when)) $dr_when = [];
if (!is_array($dr_then)) $dr_then = [];
if (!is_array($dr_else)) $dr_else = [];

// editor simples: 1 condição e 1 ação por vez (mas salva arrays)
// para “brutão”, vou permitir JSON avançado também:
$adv_when = json_encode($dr_when, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT);
$adv_then = json_encode($dr_then, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT);
$adv_else = json_encode($dr_else, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT);

$ops = [
  'equals' => 'equals (=)',
  'notEquals' => 'notEquals (!=)',
  'empty' => 'empty',
  'notEmpty' => 'notEmpty',
  'gt' => 'gt (>)',
  'gte' => 'gte (>=)',
  'lt' => 'lt (<)',
  'lte' => 'lte (<=)',
  'in' => 'in (lista)',
  'regex' => 'regex',
];

$actions = [
  'show' => 'show',
  'hide' => 'hide',
  'setRequired' => 'setRequired',
  'setEnabled' => 'setEnabled',
  'setValue' => 'setValue',
  'clearValue' => 'clearValue',
  'setMax' => 'setMax (fixo)',
  'maxFromField' => 'maxFromField (de outro campo)',
];
?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Forms AI • Wizard • Step 4</title>
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
        Wizard • Step 4/10 — Regras •
        <span class="mono"><?php echo h($form['code']); ?></span> • v<?php echo (int)$curVer; ?> •
        <span class="badge bg-warning"><?php echo h($ver['status']); ?></span>
      </div>
    </div>
    <div class="d-flex gap-2">
      <a class="btn btn-outline-secondary" href="<?php echo h(BASE_URL); ?>/public/modules/forms/wizard/3.php?form_id=<?php echo (int)$formId; ?>">← Step 3</a>
      <a class="btn btn-outline-secondary" href="<?php echo h(BASE_URL); ?>/public/modules/forms/forms_listar.php">Catálogo</a>
    </div>
  </div>

  <?php if ($flash): ?>
    <div class="alert alert-warning"><?php echo h($flash); ?></div>
  <?php endif; ?>

  <div class="row g-3">

    <!-- Lista de regras -->
    <div class="col-lg-6">
      <div class="card shadow-sm">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Regras do formulário</h5>
            <a class="btn btn-sm btn-outline-primary"
               href="<?php echo h(BASE_URL); ?>/public/modules/forms/wizard/4.php?form_id=<?php echo (int)$formId; ?>">
              Nova regra
            </a>
          </div>
          <div class="hint mt-1">Ordem importa (primeiro dispara primeiro).</div>

          <div class="mt-3 d-grid gap-2">
            <?php if (!count($rules)): ?>
              <div class="text-muted">Nenhuma regra criada ainda.</div>
            <?php endif; ?>

            <?php foreach ($rules as $idx => $r): ?>
              <?php
                $rid = (string)($r['id'] ?? '');
                $rname = (string)($r['name'] ?? '(sem nome)');
              ?>
              <div class="cardish">
                <div class="d-flex justify-content-between">
                  <div>
                    <div class="fw-semibold"><?php echo h($rname); ?></div>
                    <div class="hint">ID: <span class="mono"><?php echo h($rid); ?></span></div>
                  </div>
                  <div class="d-flex gap-1">
                    <form method="post" action="<?php echo h(BASE_URL); ?>/modules/forms/actions/forms_rules_move.php" class="d-inline">
                      <input type="hidden" name="form_id" value="<?php echo (int)$formId; ?>">
                      <input type="hidden" name="rule_id" value="<?php echo h($rid); ?>">
                      <input type="hidden" name="dir" value="up">
                      <button class="btn btn-sm btn-outline-secondary" <?php echo ($idx===0?'disabled':''); ?>>↑</button>
                    </form>
                    <form method="post" action="<?php echo h(BASE_URL); ?>/modules/forms/actions/forms_rules_move.php" class="d-inline">
                      <input type="hidden" name="form_id" value="<?php echo (int)$formId; ?>">
                      <input type="hidden" name="rule_id" value="<?php echo h($rid); ?>">
                      <input type="hidden" name="dir" value="down">
                      <button class="btn btn-sm btn-outline-secondary" <?php echo ($idx===count($rules)-1?'disabled':''); ?>>↓</button>
                    </form>

                    <a class="btn btn-sm btn-outline-primary"
                       href="<?php echo h(BASE_URL); ?>/public/modules/forms/wizard/4.php?form_id=<?php echo (int)$formId; ?>&edit=<?php echo h($rid); ?>">
                      Editar
                    </a>

                    <form method="post" action="<?php echo h(BASE_URL); ?>/modules/forms/actions/forms_rules_delete.php"
                          class="d-inline"
                          onsubmit="return confirm('Excluir regra: <?php echo h(addslashes($rname)); ?> ?');">
                      <input type="hidden" name="form_id" value="<?php echo (int)$formId; ?>">
                      <input type="hidden" name="rule_id" value="<?php echo h($rid); ?>">
                      <button class="btn btn-sm btn-outline-danger">Excluir</button>
                    </form>
                  </div>
                </div>

                <details class="mt-2">
                  <summary class="hint">Ver JSON</summary>
                  <pre class="mono small mt-2 mb-0"><?php echo h(json_encode($r, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT)); ?></pre>
                </details>
              </div>
            <?php endforeach; ?>
          </div>

          <hr class="my-3">
          <div class="d-flex justify-content-between">
            <a class="btn btn-outline-secondary" href="<?php echo h(BASE_URL); ?>/public/modules/forms/wizard/3.php?form_id=<?php echo (int)$formId; ?>">← Step 3</a>
            <a class="btn btn-primary disabled" href="#">Step 5 (Datasets avançados) →</a>
          </div>
        </div>
      </div>
    </div>

    <!-- Editor de regra -->
    <div class="col-lg-6">
      <div class="card shadow-sm">
        <div class="card-body">
          <h5 class="mb-1"><?php echo $editRule ? 'Editar regra' : 'Adicionar regra'; ?></h5>
          <div class="hint mb-3">
            Use o modo avançado (JSON) pra fazer regra complexa. Depois a gente faz editor 100% visual.
          </div>

          <form method="post" action="<?php echo h(BASE_URL); ?>/modules/forms/actions/forms_rules_save.php">
            <input type="hidden" name="form_id" value="<?php echo (int)$formId; ?>">
            <input type="hidden" name="rule_id" value="<?php echo h($dr_id); ?>">

            <div class="mb-3">
              <label class="form-label">Nome da regra</label>
              <input class="form-control" name="name" maxlength="180" value="<?php echo h($dr_name); ?>"
                     placeholder="Ex: Exigir justificativa se urgência=Sim" required>
            </div>

            <div class="mb-3">
              <label class="form-label">WHEN (condições) — JSON</label>
              <textarea class="form-control mono" rows="6" name="adv_when"
                placeholder='[{"field":"urgencia","op":"equals","value":"1"}]'><?php echo h($adv_when); ?></textarea>
              <div class="hint mt-1">
                field = name do campo • op: <?php echo h(implode(', ', array_keys($ops))); ?>
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label">THEN (ações) — JSON</label>
              <textarea class="form-control mono" rows="6" name="adv_then"
                placeholder='[{"action":"show","targetType":"field","target":"justificativa"}]'><?php echo h($adv_then); ?></textarea>
              <div class="hint mt-1">
                actions: <?php echo h(implode(', ', array_keys($actions))); ?> • targetType: field|section
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label">ELSE (ações) — JSON (opcional)</label>
              <textarea class="form-control mono" rows="6" name="adv_else"
                placeholder='[{"action":"hide","targetType":"field","target":"justificativa"}]'><?php echo h($adv_else); ?></textarea>
            </div>

            <div class="d-flex justify-content-between">
              <?php if ($editRule): ?>
                <a class="btn btn-outline-secondary"
                   href="<?php echo h(BASE_URL); ?>/public/modules/forms/wizard/4.php?form_id=<?php echo (int)$formId; ?>">
                  Cancelar
                </a>
              <?php else: ?>
                <span></span>
              <?php endif; ?>

              <button class="btn btn-primary"><?php echo $editRule ? 'Salvar regra' : 'Adicionar regra'; ?></button>
            </div>
          </form>

          <hr class="my-3">
          <div class="hint">
            Targets disponíveis:
            <details class="mt-2">
              <summary>Campos (name → label)</summary>
              <div class="mt-2">
                <?php foreach($fieldTargets as $n=>$lbl): ?>
                  <div><span class="mono"><?php echo h($n); ?></span> — <?php echo h($lbl); ?></div>
                <?php endforeach; ?>
              </div>
            </details>

            <details class="mt-2">
              <summary>Seções (id → título)</summary>
              <div class="mt-2">
                <?php foreach($sectionTargets as $s): ?>
                  <div><span class="mono"><?php echo h($s['id']); ?></span> — <?php echo h($s['label']); ?></div>
                <?php endforeach; ?>
              </div>
            </details>
          </div>

        </div>
      </div>
    </div>

  </div>

</div>
</body>
</html>
