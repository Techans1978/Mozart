<?php
ini_set('display_errors',1); ini_set('display_startup_errors',1); error_reporting(E_ALL);

require_once __DIR__ . '/../../config.php';
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

$res = $conn->query("
  SELECT id, code, title, status, current_version,
         COALESCE(updated_at, created_at) AS dt
  FROM forms_form
  ORDER BY COALESCE(updated_at, created_at) DESC, id DESC
");
$rows = [];
if ($res) while($r=$res->fetch_assoc()) $rows[]=$r;
?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Formulários</title>
  <link rel="stylesheet" href="<?php echo h(BASE_URL); ?>/assets/css/bootstrap.min.css">
</head>
<body class="bg-light">
<div class="container py-4">

  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h3 class="mb-0">Formulários</h3>
      <div class="text-muted">Forms AI • Catálogo do sistema</div>
    </div>
    <a class="btn btn-primary" href="<?php echo h(BASE_URL); ?>/public/modules/forms/wizard/1.php">
      + Criar por IA
    </a>
  </div>

  <?php if ($flash): ?>
    <div class="alert alert-warning"><?php echo h($flash); ?></div>
  <?php endif; ?>

  <div class="card shadow-sm">
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-hover align-middle">
          <thead>
            <tr>
              <th>Código</th>
              <th>Título</th>
              <th>Status</th>
              <th>Versão</th>
              <th>Atualizado</th>
              <th class="text-end">Ações</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach($rows as $r): ?>
            <tr>
              <td class="text-monospace"><?php echo h($r['code']); ?></td>
              <td><?php echo h($r['title']); ?></td>
              <td>
                <?php
                  $st = $r['status'];
                  $badge = $st==='published'?'success':($st==='archived'?'secondary':'warning');
                ?>
                <span class="badge bg-<?php echo $badge; ?>"><?php echo h($st); ?></span>
              </td>
              <td>v<?php echo (int)$r['current_version']; ?></td>
              <td><?php echo h($r['dt']); ?></td>
              <td class="text-end">
                <a class="btn btn-sm btn-outline-primary"
                   href="<?php echo h(BASE_URL); ?>/public/modules/forms/forms_editar.php?id=<?php echo (int)$r['id']; ?>">
                  Editar
                </a>

                <a class="btn btn-sm btn-outline-primary"
                  href="<?php echo h(BASE_URL); ?>/public/modules/forms/forms_gerenciar.php?id=<?php echo (int)$r['id']; ?>">
                  Gerenciar
                </a>

                <form method="post" action="<?php echo h(BASE_URL); ?>/modules/forms/actions/forms_new_version.php" class="d-inline"
                      onsubmit="return confirm('Criar nova versão draft para <?php echo h(addslashes($r['code'])); ?>?');">
                  <input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
                  <button class="btn btn-sm btn-outline-dark">Nova Versão</button>
                </form>

                <a class="btn btn-sm btn-outline-secondary"
                  href="<?php echo h(BASE_URL); ?>/public/modules/forms/runtime/render.php?form_id=<?php echo (int)$r['id']; ?>">
                  Runtime
                </a>


                <?php if ($r['status'] !== 'published'): ?>
                  <form class="d-inline" method="post"
                        action="<?php echo h(BASE_URL); ?>/modules/forms/actions/forms_toggle.php"
                        onsubmit="return confirm('Publicar este formulário?');">
                    <input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
                    <input type="hidden" name="to" value="published">
                    <button class="btn btn-sm btn-outline-success">Publicar</button>
                  </form>
                <?php endif; ?>

                <?php if ($r['status'] === 'archived'): ?>
                  <form class="d-inline" method="post"
                        action="<?php echo h(BASE_URL); ?>/modules/forms/actions/forms_toggle.php"
                        onsubmit="return confirm('Desbloquear este formulário (voltar para draft)?');">
                    <input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
                    <input type="hidden" name="to" value="draft">
                    <button class="btn btn-sm btn-outline-secondary">Desbloquear</button>
                  </form>
                <?php else: ?>
                  <form class="d-inline" method="post"
                        action="<?php echo h(BASE_URL); ?>/modules/forms/actions/forms_toggle.php"
                        onsubmit="return confirm('Bloquear este formulário? (Arquivar)');">
                    <input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
                    <input type="hidden" name="to" value="archived">
                    <button class="btn btn-sm btn-outline-secondary">Bloquear</button>
                  </form>
                <?php endif; ?>

                <form class="d-inline" method="post"
                      action="<?php echo h(BASE_URL); ?>/modules/forms/actions/forms_delete.php"
                      onsubmit="return confirm('Excluir DEFINITIVAMENTE este formulário?');">
                  <input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
                  <button class="btn btn-sm btn-outline-danger">Deletar</button>
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
