<?php
// public/modules/forms/forms_gerenciar_picker.php
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

$q = trim((string)($_GET['q'] ?? ''));
$like = '%'.$q.'%';

if ($q !== '') {
  $stmt = $conn->prepare("SELECT id, code, title, status, current_version
                          FROM forms_form
                          WHERE code LIKE ? OR title LIKE ?
                          ORDER BY id DESC
                          LIMIT 200");
  $stmt->bind_param("ss",$like,$like);
} else {
  $stmt = $conn->prepare("SELECT id, code, title, status, current_version
                          FROM forms_form
                          ORDER BY id DESC
                          LIMIT 200");
}
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Escolher Form</title>
  <link rel="stylesheet" href="<?php echo h(BASE_URL); ?>/assets/css/bootstrap.min.css">
  <style>.mono{font-family:ui-monospace,Menlo,Consolas,monospace}</style>
</head>
<body class="bg-light">
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-start mb-3">
    <div>
      <h4 class="mb-0">Gerenciar Formulários</h4>
      <div class="text-muted">Escolha um formulário para abrir o painel de versões</div>
    </div>
    <a class="btn btn-outline-secondary" href="<?php echo h(BASE_URL); ?>/public/modules/forms/forms_listar.php">Voltar</a>
  </div>

  <div class="card shadow-sm mb-3">
    <div class="card-body">
      <form class="row g-2" method="get">
        <div class="col-md-8">
          <label class="form-label">Buscar</label>
          <input class="form-control" name="q" value="<?php echo h($q); ?>" placeholder="code ou título...">
        </div>
        <div class="col-md-4 d-flex align-items-end gap-2">
          <button class="btn btn-primary">Filtrar</button>
          <a class="btn btn-outline-secondary" href="<?php echo h(BASE_URL); ?>/public/modules/forms/forms_gerenciar_picker.php">Limpar</a>
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
              <th>Code</th>
              <th>Título</th>
              <th>Status</th>
              <th>Current</th>
              <th class="text-end">Ação</th>
            </tr>
          </thead>
          <tbody>
            <?php if(!count($rows)): ?>
              <tr><td colspan="6" class="text-muted">Nenhum encontrado.</td></tr>
            <?php endif; ?>
            <?php foreach($rows as $r): ?>
              <tr>
                <td class="mono"><?php echo (int)$r['id']; ?></td>
                <td class="mono"><?php echo h($r['code']); ?></td>
                <td><?php echo h($r['title'] ?? ''); ?></td>
                <td>
                  <?php
                    $st=(string)$r['status'];
                    $bg = ($st==='active'?'success':($st==='blocked'?'danger':'secondary'));
                  ?>
                  <span class="badge bg-<?php echo $bg; ?>"><?php echo h($st); ?></span>
                </td>
                <td class="mono">v<?php echo (int)$r['current_version']; ?></td>
                <td class="text-end">
                  <a class="btn btn-sm btn-outline-primary"
                     href="<?php echo h(BASE_URL); ?>/public/modules/forms/forms_gerenciar.php?id=<?php echo (int)$r['id']; ?>">
                    Abrir
                  </a>
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
