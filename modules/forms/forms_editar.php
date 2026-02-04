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

$id = (int)($_GET['id'] ?? 0);
if ($id<=0) die('ID inválido.');

$stmt = $conn->prepare("SELECT * FROM forms_form WHERE id=? LIMIT 1");
$stmt->bind_param("i",$id);
$stmt->execute();
$form = $stmt->get_result()->fetch_assoc();
$stmt->close();
if(!$form) die('Formulário não encontrado.');

$flash = $_SESSION['__flash']['m'] ?? '';
unset($_SESSION['__flash']);
?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Editar Formulário</title>
  <link rel="stylesheet" href="<?php echo h(BASE_URL); ?>/assets/css/bootstrap.min.css">
</head>
<body class="bg-light">
<div class="container py-4">

  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h3 class="mb-0">Editar Formulário</h3>
      <div class="text-muted"><?php echo h($form['code']); ?> • v<?php echo (int)$form['current_version']; ?></div>
    </div>
    <a class="btn btn-outline-secondary" href="<?php echo h(BASE_URL); ?>/public/modules/forms/forms_listar.php">Voltar</a>
  </div>

  <?php if ($flash): ?>
    <div class="alert alert-warning"><?php echo h($flash); ?></div>
  <?php endif; ?>

  <div class="card shadow-sm">
    <div class="card-body">
      <form method="post" action="<?php echo h(BASE_URL); ?>/modules/forms/actions/forms_update.php">
        <input type="hidden" name="id" value="<?php echo (int)$form['id']; ?>">

        <div class="mb-3">
          <label class="form-label">Título</label>
          <input class="form-control" name="title" maxlength="160" required
                 value="<?php echo h($form['title']); ?>">
        </div>

        <div class="mb-3">
          <label class="form-label">Descrição</label>
          <textarea class="form-control" name="description" rows="3"><?php echo h($form['description']); ?></textarea>
        </div>

        <div class="mb-3">
          <label class="form-label">Tags</label>
          <input class="form-control" name="tags" maxlength="255"
                 value="<?php echo h($form['tags']); ?>">
        </div>

        <div class="d-flex justify-content-between">
          <a class="btn btn-outline-primary"
             href="<?php echo h(BASE_URL); ?>/public/modules/forms/wizard/2.php?form_id=<?php echo (int)$form['id']; ?>">
            Abrir Editor (Step 2)
          </a>

          <button class="btn btn-primary">Salvar</button>
        </div>
      </form>
    </div>
  </div>

</div>
</body>
</html>
