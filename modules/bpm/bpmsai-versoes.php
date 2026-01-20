<?php
// modules/bpm/bpmsai-versoes.php
ini_set('display_errors',1); ini_set('display_startup_errors',1); error_reporting(E_ALL);

require_once __DIR__ . '/../../config.php';
require_once ROOT_PATH . '/system/config/autenticacao.php';
require_once ROOT_PATH . '/system/config/connect.php';
if (session_status()===PHP_SESSION_NONE) session_start();
proteger_pagina();

include_once ROOT_PATH.'system/includes/head.php';
include_once ROOT_PATH.'modules/bpm/includes/content_header.php';
include_once ROOT_PATH.'modules/bpm/includes/content_style.php';
include_once ROOT_PATH.'system/includes/navbar.php';

$flowId = (int)($_GET['flow_id'] ?? 0);
if($flowId<=0) die('flow_id invalido');

$flow = $conn->query("SELECT * FROM bpmsai_flow WHERE id=".$flowId." LIMIT 1")->fetch_assoc();
if(!$flow) die('Flow nao encontrado');

$rs = $conn->query("SELECT * FROM bpmsai_flow_version WHERE flow_id=".$flowId." ORDER BY version_number DESC, id DESC");
$vers = $rs ? $rs->fetch_all(MYSQLI_ASSOC) : [];
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

?>
<div id="page-wrapper"><div class="container-fluid">
  <div class="row"><div class="col-lg-12">
    <h1 class="page-header">Versões — <?= h($flow['name']) ?></h1>
  </div></div>

  <div class="panel panel-default"><div class="panel-body">
    <a class="btn btn-default" href="/modules/bpm/bpmsai-listar.php">Voltar</a>
    <a class="btn btn-primary" href="/modules/bpm/bpmsai-wizard.php?flow_id=<?= (int)$flowId ?>&step=1">Editar (criar/usar draft)</a>
  </div></div>

  <div class="panel panel-default"><div class="panel-body">
    <table class="table table-bordered">
      <thead><tr>
        <th>Versão</th><th>Status</th><th>Publicada em</th><th>Ações</th>
      </tr></thead>
      <tbody>
      <?php foreach($vers as $v): ?>
        <tr>
          <td><b>v<?= (int)$v['version_number'] ?></b></td>
          <td><code><?= h($v['status']) ?></code></td>
          <td><?= h($v['published_at'] ?? '') ?></td>
          <td>
            <?php if($v['status']==='published'): ?>
              <a class="btn btn-xs btn-default" href="/modules/bpm/actions/bpmsai_set_active_version.php?flow_id=<?= $flowId ?>&ver_id=<?= (int)$v['id'] ?>"
                 onclick="return confirm('Tornar esta versão ativa?');">Tornar ativa</a>
            <?php endif; ?>

            <?php if($v['status']==='draft'): ?>
              <a class="btn btn-xs btn-primary" href="/modules/bpm/bpmsai-wizard.php?flow_id=<?= $flowId ?>&step=1">Abrir draft</a>
            <?php endif; ?>

            <a class="btn btn-xs btn-default" href="/modules/bpm/actions/bpmsai_duplicate.php?flow_id=<?= $flowId ?>&from_ver=<?= (int)$v['id'] ?>"
               onclick="return confirm('Duplicar fluxo a partir desta versão?');">Duplicar</a>

            <?php if($v['status']!=='archived'): ?>
              <a class="btn btn-xs btn-warning" href="/modules/bpm/actions/bpmsai_archive.php?flow_id=<?= $flowId ?>&ver_id=<?= (int)$v['id'] ?>"
                 onclick="return confirm('Arquivar esta versão?');">Arquivar</a>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div></div>

</div></div>
<?php include_once ROOT_PATH.'system/includes/footer.php'; ?>
