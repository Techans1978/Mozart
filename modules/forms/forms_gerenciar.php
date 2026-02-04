<?php
// public/modules/forms/forms_gerenciar.php
ini_set('display_errors',1); ini_set('display_startup_errors',1); error_reporting(E_ALL);

require_once __DIR__ . '/../../config.php';
require_once ROOT_PATH . '/system/config/autenticacao.php';
require_once ROOT_PATH . '/system/config/connect.php';

if (!isset($conn) && isset($mysqli)) $conn = $mysqli;
if (!($conn instanceof mysqli)) die('Conexão MySQLi $conn não encontrada.');

if (session_status() !== PHP_SESSION_ACTIVE) session_start();
proteger_pagina();
$conn->set_charset('utf8mb4');

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$flash = $_SESSION['__flash']['m'] ?? '';
unset($_SESSION['__flash']);

$formId = (int)($_GET['id'] ?? 0);
if ($formId<=0) die('ID inválido.');

$stmt = $conn->prepare("SELECT * FROM forms_form WHERE id=? LIMIT 1");
$stmt->bind_param("i",$formId);
$stmt->execute();
$form = $stmt->get_result()->fetch_assoc();
$stmt->close();
if(!$form) die('Form não encontrado.');

$stmt = $conn->prepare("SELECT id, version, status, updated_at
                        FROM forms_form_version
                        WHERE form_id=?
                        ORDER BY version DESC");
$stmt->bind_param("i",$formId);
$stmt->execute();
$vers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Gerenciar Form</title>
  <link rel="stylesheet" href="<?php echo h(BASE_URL); ?>/assets/css/bootstrap.min.css">
  <style>.mono{font-family:ui-monospace,Menlo,Consolas,monospace}</style>
</head>
<body class="bg-light">
<div class="container py-4">

  <div class="d-flex justify-content-between align-items-start mb-3">
    <div>
      <h4 class="mb-0">Gerenciar Formulário</h4>
      <div class="text-muted">
        <span class="mono"><?php echo h($form['code']); ?></span> •
        <?php echo h($form['title'] ?? ''); ?> •
        Status: <span class="badge bg-<?php echo ($form['status']==='active'?'success':($form['status']==='blocked'?'danger':'secondary')); ?>"><?php echo h($form['status']); ?></span> •
        Current v<?php echo (int)$form['current_version']; ?>
      </div>
    </div>
    <div class="d-flex gap-2">
      <a class="btn btn-outline-secondary" href="<?php echo h(BASE_URL); ?>/public/modules/forms/forms_listar.php">Voltar</a>
      <a class="btn btn-outline-primary" href="<?php echo h(BASE_URL); ?>/public/modules/forms/wizard/3.php?form_id=<?php echo (int)$formId; ?>">Abrir Wizard</a>
      <a class="btn btn-outline-dark" href="<?php echo h(BASE_URL); ?>/public/modules/forms/runtime/render.php?form_id=<?php echo (int)$formId; ?>">Abrir Runtime</a>
    </div>
  </div>

  <?php if ($flash): ?>
    <div class="alert alert-warning"><?php echo h($flash); ?></div>
  <?php endif; ?>

  <div class="row g-3">

    <div class="col-lg-4">
      <div class="card shadow-sm">
        <div class="card-body">
          <h6 class="mb-2">Ações do Form</h6>

          <form method="post" action="<?php echo h(BASE_URL); ?>/modules/forms/actions/forms_toggle_block.php" class="mb-2">
            <input type="hidden" name="id" value="<?php echo (int)$formId; ?>">
            <button class="btn btn-<?php echo ($form['status']==='blocked'?'success':'danger'); ?> w-100">
              <?php echo ($form['status']==='blocked'?'Desbloquear':'Bloquear'); ?>
            </button>
          </form>

          <form method="post" action="<?php echo h(BASE_URL); ?>/modules/forms/actions/forms_clone.php"
                onsubmit="return confirm('Clonar este formulário?');" class="mb-2">
            <input type="hidden" name="id" value="<?php echo (int)$formId; ?>">
            <button class="btn btn-outline-primary w-100">Clonar (gera novo code)</button>
          </form>

          <?php
// Carrega categorias (global + custom) e marca as selecionadas
$catsAll = [];
$catsSel = [];

$catsAll = [];
$st = $conn->prepare("SELECT id, nome, contexto_tipo
                      FROM moz_form_category
                      WHERE ativo=1 AND contexto_tipo IN ('global','custom')
                      ORDER BY contexto_tipo, sort_order, nome");
$st->execute();
$catsAll = $st->get_result()->fetch_all(MYSQLI_ASSOC);
$st->close();

$st = $conn->prepare("SELECT category_id FROM forms_form_category WHERE form_id=?");
$st->bind_param("i",$formId);
$st->execute();
$catsSel = array_map(fn($r)=> (int)$r['category_id'], $st->get_result()->fetch_all(MYSQLI_ASSOC));
$st->close();

$catsSel = array_flip($catsSel);
?>
<hr>
<h6 class="mb-2">Categorias do Form</h6>
<form method="post" action="<?php echo h(BASE_URL); ?>/modules/forms/actions/forms_set_categories.php">
  <input type="hidden" name="form_id" value="<?php echo (int)$formId; ?>">
  <select class="form-select" name="category_ids[]" multiple size="8">
    <?php foreach($catsAll as $c): ?>
      <option value="<?php echo (int)$c['id']; ?>" <?php echo isset($catsSel[(int)$c['id']])?'selected':''; ?>>
        <?php echo h(($c['contexto_tipo']??'')." • ".($c['nome']??'')); ?>
      </option>
    <?php endforeach; ?>
  </select>
  <button class="btn btn-primary w-100 mt-2">Salvar categorias</button>
</form>


          <div class="alert alert-info small mb-0">
            Bloqueado = runtime não executa. <br>
            Clonar = cria novo form + versão draft.
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-8">
      <div class="card shadow-sm">
        <div class="card-body">
          <h6 class="mb-2">Versões</h6>

          <div class="table-responsive">
            <table class="table table-sm table-hover align-middle">
              <thead>
                <tr>
                  <th>Versão</th>
                  <th>Status</th>
                  <th>Atualizado</th>
                  <th class="text-end">Ações</th>
                </tr>
              </thead>
              <tbody>
              <?php foreach($vers as $v): ?>
                <?php
                  $st = (string)$v['status'];
                  $badge = ($st==='published'?'success':($st==='draft'?'warning':'secondary'));
                ?>
                <tr>
                  <td class="mono">v<?php echo (int)$v['version']; ?></td>
                  <td><span class="badge bg-<?php echo $badge; ?>"><?php echo h($st); ?></span></td>
                  <td class="mono"><?php echo h($v['updated_at'] ?? ''); ?></td>
                  <td class="text-end">

                    <a class="btn btn-sm btn-outline-dark"
                       href="<?php echo h(BASE_URL); ?>/public/modules/forms/runtime/render.php?form_id=<?php echo (int)$formId; ?>&mode=<?php echo ($st==='draft'?'draft':'published'); ?>"
                       target="_blank">
                      Preview
                    </a>

                    <?php if ($st==='draft'): ?>
                      <form method="post" action="<?php echo h(BASE_URL); ?>/modules/forms/actions/forms_publish.php" class="d-inline"
                            onsubmit="return confirm('Publicar v<?php echo (int)$v['version']; ?>?');">
                        <input type="hidden" name="form_id" value="<?php echo (int)$formId; ?>">
                        <input type="hidden" name="version_id" value="<?php echo (int)$v['id']; ?>">
                        <button class="btn btn-sm btn-success">Publicar</button>
                      </form>
                    <?php endif; ?>

                    <?php if ($st!=='archived'): ?>
                      <form method="post" action="<?php echo h(BASE_URL); ?>/modules/forms/actions/forms_archive_version.php" class="d-inline"
                            onsubmit="return confirm('Arquivar v<?php echo (int)$v['version']; ?>?');">
                        <input type="hidden" name="form_id" value="<?php echo (int)$formId; ?>">
                        <input type="hidden" name="version_id" value="<?php echo (int)$v['id']; ?>">
                        <button class="btn btn-sm btn-outline-secondary">Arquivar</button>
                      </form>
                    <?php endif; ?>

                  </td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>

          <div class="text-muted small">
            Publicar: torna a versão “published” e atualiza current_version do form.
          </div>
        </div>
      </div>
    </div>

  </div>

</div>
</body>
</html>
