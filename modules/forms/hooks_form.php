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

$id = (int)($_GET['id'] ?? 0);

$row = ['form_code'=>'','endpoint'=>'','method'=>'POST','secret'=>'','ativo'=>1];

if ($id>0){
  $st=$conn->prepare("SELECT * FROM forms_reprocess_hook WHERE id=? LIMIT 1");
  $st->bind_param("i",$id);
  $st->execute();
  $db=$st->get_result()->fetch_assoc();
  $st->close();
  if($db) $row = $db;
}
?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?php echo ($id>0?'Editar':'Novo'); ?> Hook</title>
  <link rel="stylesheet" href="<?php echo h(BASE_URL); ?>/assets/css/bootstrap.min.css">
  <style>.mono{font-family:ui-monospace,Menlo,Consolas,monospace}</style>
</head>
<body class="bg-light">
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-start mb-3">
    <div>
      <h4 class="mb-0"><?php echo ($id>0?'Editar':'Novo'); ?> Hook</h4>
      <div class="text-muted">form_code → endpoint interno</div>
    </div>
    <a class="btn btn-outline-secondary" href="<?php echo h(BASE_URL); ?>/public/modules/forms/hooks_listar.php">Voltar</a>
  </div>

  <div class="card shadow-sm">
    <div class="card-body">
      <form method="post" action="<?php echo h(BASE_URL); ?>/modules/forms/actions/hooks_save.php">
        <input type="hidden" name="id" value="<?php echo (int)$id; ?>">

        <div class="mb-2">
          <label class="form-label">Form Code</label>
          <input class="form-control mono" name="form_code" value="<?php echo h($row['form_code']); ?>" required>
          <div class="form-text">Ex: RH_SOLICITACAO</div>
        </div>

        <div class="mb-2">
          <label class="form-label">Endpoint</label>
          <input class="form-control mono" name="endpoint" value="<?php echo h($row['endpoint']); ?>" required>
          <div class="form-text">Somente interno: /modules/... ou BASE_URL...</div>
        </div>

        <div class="row g-2 mb-2">
          <div class="col-md-3">
            <label class="form-label">Método</label>
            <select class="form-select mono" name="method">
              <?php foreach(['POST'] as $m): ?>
                <option value="<?php echo h($m); ?>" <?php echo (strtoupper($row['method'])===$m?'selected':''); ?>><?php echo h($m); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Ativo</label>
            <select class="form-select" name="ativo">
              <option value="1" <?php echo ((int)$row['ativo']===1?'selected':''); ?>>Sim</option>
              <option value="0" <?php echo ((int)$row['ativo']===0?'selected':''); ?>>Não</option>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Secret (opcional)</label>
            <input class="form-control mono" name="secret" value="<?php echo h($row['secret']); ?>" placeholder="HMAC sha256">
          </div>
        </div>

        <button class="btn btn-primary">Salvar</button>
      </form>
    </div>
  </div>
</div>
</body>
</html>
