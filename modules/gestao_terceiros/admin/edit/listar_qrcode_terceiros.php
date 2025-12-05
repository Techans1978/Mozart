<?php
// admin/listar/listar_registros.php
ini_set('display_errors',1); ini_set('display_startup_errors',1); error_reporting(E_ALL);

require_once __DIR__ . '/../autenticacao.php';
proteger_pagina();

require_once __DIR__ . '/../conexao.php';

if (session_status()===PHP_SESSION_NONE) session_start();
if (empty($_SESSION['csrf_qr'])) {
  $_SESSION['csrf_qr'] = bin2hex(random_bytes(16));
}
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$msgOk = !empty($_GET['ok']) ? h($_GET['ok']) : '';

// Busca simples de promotores
$q = "SELECT id, nome_completo, codigo, NOME_FANTASIA FROM promotores ORDER BY id DESC LIMIT 200";
$res = $conn->query($q);
$rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Admin · Promotores</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
  <div class="container py-4">
    <h1 class="h4 mb-4">Promotores · Administração</h1>

    <?php if ($msgOk): ?>
      <div class="alert alert-success"><?= $msgOk ?></div>
    <?php endif; ?>

    <div class="card">
      <div class="card-body p-0">
        <div class="table-responsive">

<div class="panel-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped table-bordered table-hover align-middle mb-0" id="dataTables-example">

            <thead class="table-light">
              <tr>
                <th style="width:80px">ID</th>
                <th>Nome</th>
                <th style="width:160px">Código</th>
                <th>Empresa</th>
                <th style="width:320px">Ações</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($rows as $row): ?>
              <tr>
                <td><?= (int)$row['id'] ?></td>
                <td><?= h($row['nome_completo']) ?></td>
                <td><?= h($row['codigo']) ?></td>
                <td><?= h($row['NOME_FANTASIA']) ?></td>
                <td>
                  <a class="btn btn-sm btn-outline-secondary" href="baixar_qrcode.php?id=<?= (int)$row['id'] ?>">
                    Baixar QR Code
                  </a>
                  <form action="novo_qrcode.php" method="post" style="display:inline"
                        onsubmit="return confirm('Gerar um novo QR Code para <?= h($row['nome_completo']) ?>? O arquivo anterior será substituído.');">
                    <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                    <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf_qr']) ?>">
                    <button type="submit" class="btn btn-sm btn-warning">Novo QR Code</button>
                  </form>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>

                                </div>
                    </div>
                    <!-- table -->

        </div>
      </div>
    </div>
  </div>
</body>
</html>
