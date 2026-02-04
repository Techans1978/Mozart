<?php
ini_set('display_errors',1); ini_set('display_startup_errors',1); error_reporting(E_ALL);

require_once __DIR__ . '/../../config.php';
require_once ROOT_PATH . '/system/config/autenticacao.php';
require_once ROOT_PATH . '/system/config/connect.php';

if (!isset($conn) && isset($mysqli)) $conn = $mysqli;
if (!($conn instanceof mysqli)) die('Sem DB');

if (session_status()!==PHP_SESSION_ACTIVE) session_start();
proteger_pagina();
$conn->set_charset('utf8mb4');

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$flash = $_SESSION['__flash']['m'] ?? '';
unset($_SESSION['__flash']);

$q = trim((string)($_GET['q'] ?? ''));
$like = '%'.$q.'%';

if ($q !== '') {
  $st = $conn->prepare("SELECT * FROM forms_reprocess_hook
                        WHERE form_code LIKE ? OR endpoint LIKE ?
                        ORDER BY ativo DESC, form_code ASC
                        LIMIT 300");
  $st->bind_param("ss",$like,$like);
} else {
  $st = $conn->prepare("SELECT * FROM forms_reprocess_hook
                        ORDER BY ativo DESC, form_code ASC
                        LIMIT 300");
}
$st->execute();
$rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);
$st->close();
?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Forms • Hooks</title>
  <link rel="stylesheet" href="<?php echo h(BASE_URL); ?>/assets/css/bootstrap.min.css">
  <style>.mono{font-family:ui-monospace,Menlo,Consolas,monospace}</style>
</head>
<body class="bg-light">
<div class="container py-4">

  <div class="d-flex justify-content-between align-items-start mb-3">
    <div>
      <h4 class="mb-0">Hooks de Reprocessamento</h4>
      <div class="text-muted">Mapeia form_code → endpoint interno (BPM/Helpdesk/etc)</div>
    </div>
    <div class="d-flex gap-2">
      <a class="btn btn-outline-secondary" href="<?php echo h(BASE_URL); ?>/public/modules/forms/dashboard.php">Dashboard</a>
      <a class="btn btn-primary" href="<?php echo h(BASE_URL); ?>/public/modules/forms/hooks_form.php">Novo Hook</a>
    </div>
  </div>

  <?php if ($flash): ?>
    <div class="alert alert-warning"><?php echo h($flash); ?></div>
  <?php endif; ?>

  <div class="card shadow-sm mb-3">
    <div class="card-body">
      <form class="row g-2" method="get">
        <div class="col-md-8">
          <label class="form-label">Buscar</label>
          <input class="form-control" name="q" value="<?php echo h($q); ?>" placeholder="form_code ou endpoint...">
        </div>
        <div class="col-md-4 d-flex align-items-end gap-2">
          <button class="btn btn-outline-primary">Filtrar</button>
          <a class="btn btn-outline-secondary" href="<?php echo h(BASE_URL); ?>/public/modules/forms/hooks_listar.php">Limpar</a>
        </div>
      </form>
    </div>
  </div>

  <div class="card shadow-sm">
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-sm table-hover align-middle">
          <thead>
            <tr>
              <th>ID</th>
              <th>Form Code</th>
              <th>Endpoint</th>
              <th>Método</th>
              <th>Ativo</th>
              <th class="text-end">Ações</th>
            </tr>
          </thead>
          <tbody>
          <?php if (!count($rows)): ?>
            <tr><td colspan="6" class="text-muted">Nenhum hook cadastrado.</td></tr>
          <?php endif; ?>

          <?php foreach($rows as $r): ?>
            <tr>
              <td class="mono"><?php echo (int)$r['id']; ?></td>
              <td class="mono"><?php echo h($r['form_code']); ?></td>
              <td class="mono small"><?php echo h($r['endpoint']); ?></td>
              <td class="mono"><?php echo h($r['method']); ?></td>
              <td>
                <span class="badge bg-<?php echo ((int)$r['ativo']===1?'success':'secondary'); ?>">
                  <?php echo ((int)$r['ativo']===1?'sim':'não'); ?>
                </span>
              </td>
              <td class="text-end">

                <a class="btn btn-sm btn-outline-primary"
                   href="<?php echo h(BASE_URL); ?>/public/modules/forms/hooks_form.php?id=<?php echo (int)$r['id']; ?>">
                  Editar
                </a>

                <form method="post" action="<?php echo h(BASE_URL); ?>/modules/forms/actions/hooks_toggle.php" class="d-inline">
                  <input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
                  <button class="btn btn-sm btn-outline-secondary"><?php echo ((int)$r['ativo']===1?'Desativar':'Ativar'); ?></button>
                </form>

                <form method="post" action="<?php echo h(BASE_URL); ?>/modules/forms/actions/hooks_test.php" class="d-inline">
                  <input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
                  <button class="btn btn-sm btn-outline-warning">Testar</button>
                </form>

                <form method="post" action="<?php echo h(BASE_URL); ?>/modules/forms/actions/hooks_delete.php" class="d-inline"
                      onsubmit="return confirm('Excluir hook #<?php echo (int)$r['id']; ?>?');">
                  <input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
                  <button class="btn btn-sm btn-outline-danger">Del</button>
                </form>

              </td>
            </tr>
          <?php endforeach; ?>

          </tbody>
        </table>
      </div>
    </div>
  </div>

</div>
</body>
</html>
