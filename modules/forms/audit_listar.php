<?php
// public/modules/forms/audit_listar.php
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

$q = trim((string)($_GET['q'] ?? ''));       // code ou action
$action = trim((string)($_GET['action'] ?? ''));
$page = max(1,(int)($_GET['page'] ?? 1));
$per  = min(100, max(20,(int)($_GET['per'] ?? 50)));
$off  = ($page-1)*$per;

$where=[]; $types=''; $params=[];
if ($q!==''){
  $where[]="(form_code LIKE ? OR action LIKE ?)";
  $types.='ss';
  $like='%'.$q.'%';
  $params[]=$like; $params[]=$like;
}
if ($action!==''){
  $where[]="action=?";
  $types.='s';
  $params[]=$action;
}
$whereSql = count($where)?('WHERE '.implode(' AND ',$where)):'';

$stmt=$conn->prepare("SELECT COUNT(*) c FROM forms_audit_log $whereSql");
if ($types) $stmt->bind_param($types, ...$params);
$stmt->execute();
$total=(int)($stmt->get_result()->fetch_assoc()['c'] ?? 0);
$stmt->close();

$pages=max(1,(int)ceil($total/$per));
if ($page>$pages){ $page=$pages; $off=($page-1)*$per; }

$sql="SELECT id, form_id, form_code, action, user_id, created_at, details_json
      FROM forms_audit_log
      $whereSql
      ORDER BY id DESC
      LIMIT ? OFFSET ?";

$stmt=$conn->prepare($sql);
if ($types){
  $types2=$types.'ii';
  $params2=array_merge($params, [$per,$off]);
  $stmt->bind_param($types2, ...$params2);
} else {
  $stmt->bind_param("ii",$per,$off);
}
$stmt->execute();
$rows=$stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

function build_q($over=[]){
  $q=$_GET;
  foreach($over as $k=>$v){ if($v===null) unset($q[$k]); else $q[$k]=$v; }
  return http_build_query($q);
}
?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Forms • Auditoria</title>
  <link rel="stylesheet" href="<?php echo h(BASE_URL); ?>/assets/css/bootstrap.min.css">
  <style>.mono{font-family:ui-monospace,Menlo,Consolas,monospace}</style>
</head>
<body class="bg-light">
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-start mb-3">
    <div>
      <h4 class="mb-0">Auditoria • Forms</h4>
      <div class="text-muted">publicar, bloquear, clonar, nova versão, reprocessar…</div>
    </div>
    <a class="btn btn-outline-secondary" href="<?php echo h(BASE_URL); ?>/public/modules/forms/forms_listar.php">Voltar</a>
  </div>

  <div class="card shadow-sm mb-3">
    <div class="card-body">
      <form class="row g-2" method="get">
        <div class="col-md-6">
          <label class="form-label">Buscar</label>
          <input class="form-control" name="q" value="<?php echo h($q); ?>" placeholder="form_code ou action">
        </div>
        <div class="col-md-3">
          <label class="form-label">Action</label>
          <input class="form-control" name="action" value="<?php echo h($action); ?>" placeholder="publish, block...">
        </div>
        <div class="col-md-3">
          <label class="form-label">Por pág.</label>
          <select class="form-select" name="per">
            <?php foreach([20,50,100] as $pp): ?>
              <option value="<?php echo $pp; ?>" <?php echo ($per===$pp?'selected':''); ?>><?php echo $pp; ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-12 d-flex gap-2">
          <button class="btn btn-primary">Filtrar</button>
          <a class="btn btn-outline-secondary" href="<?php echo h(BASE_URL); ?>/public/modules/forms/audit_listar.php">Limpar</a>
        </div>
      </form>
    </div>
  </div>

  <div class="card shadow-sm">
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <div class="text-muted">Total: <b><?php echo (int)$total; ?></b></div>
        <div class="text-muted">Página <b><?php echo (int)$page; ?></b> de <b><?php echo (int)$pages; ?></b></div>
      </div>

      <div class="table-responsive">
        <table class="table table-sm table-hover align-middle">
          <thead>
            <tr>
              <th>ID</th>
              <th>Quando</th>
              <th>Form</th>
              <th>Action</th>
              <th>User</th>
              <th>Details</th>
            </tr>
          </thead>
          <tbody>
          <?php if(!count($rows)): ?>
            <tr><td colspan="6" class="text-muted">Sem registros.</td></tr>
          <?php endif; ?>
          <?php foreach($rows as $r): ?>
            <?php $det = json_decode($r['details_json'] ?? 'null', true); ?>
            <tr>
              <td class="mono"><?php echo (int)$r['id']; ?></td>
              <td class="mono"><?php echo h($r['created_at']); ?></td>
              <td class="mono"><?php echo h($r['form_code'] ?? ''); ?></td>
              <td class="mono"><?php echo h($r['action']); ?></td>
              <td class="mono"><?php echo h($r['user_id'] ?? ''); ?></td>
              <td class="mono small"><?php echo h(json_encode($det, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)); ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <nav class="mt-2">
        <ul class="pagination pagination-sm mb-0">
          <li class="page-item <?php echo ($page<=1?'disabled':''); ?>">
            <a class="page-link" href="?<?php echo h(build_q(['page'=>max(1,$page-1)])); ?>">‹</a>
          </li>
          <li class="page-item disabled"><span class="page-link"><?php echo (int)$page; ?>/<?php echo (int)$pages; ?></span></li>
          <li class="page-item <?php echo ($page>=$pages?'disabled':''); ?>">
            <a class="page-link" href="?<?php echo h(build_q(['page'=>min($pages,$page+1)])); ?>">›</a>
          </li>
        </ul>
      </nav>

    </div>
  </div>
</div>
</body>
</html>
