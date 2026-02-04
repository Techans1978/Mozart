<?php
// public/modules/forms/submissions_listar.php
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

$form_code = trim((string)($_GET['form_code'] ?? ''));
$status    = trim((string)($_GET['status'] ?? ''));
$date_from = trim((string)($_GET['from'] ?? ''));
$date_to   = trim((string)($_GET['to'] ?? ''));
$created_by= trim((string)($_GET['created_by'] ?? ''));

$page = max(1, (int)($_GET['page'] ?? 1));
$per  = min(50, max(10, (int)($_GET['per'] ?? 20)));
$off  = ($page-1)*$per;

// monta WHERE dinâmico
$where = [];
$types = '';
$params = [];

if ($form_code !== '') { $where[] = "s.form_code=?"; $types.='s'; $params[]=$form_code; }
if ($status !== '')    { $where[] = "s.status=?";    $types.='s'; $params[]=$status; }
if ($created_by !== '' && ctype_digit($created_by)) { $where[]="s.created_by=?"; $types.='i'; $params[]=(int)$created_by; }

if ($date_from !== '') { $where[] = "s.created_at>=?"; $types.='s'; $params[]=$date_from.' 00:00:00'; }
if ($date_to !== '')   { $where[] = "s.created_at<=?"; $types.='s'; $params[]=$date_to.' 23:59:59'; }

$whereSql = count($where) ? ('WHERE '.implode(' AND ',$where)) : '';

// total
$sqlCount = "SELECT COUNT(*) AS c FROM forms_form_submission s $whereSql";
$stmt = $conn->prepare($sqlCount);
if ($types) $stmt->bind_param($types, ...$params);
$stmt->execute();
$total = (int)($stmt->get_result()->fetch_assoc()['c'] ?? 0);
$stmt->close();

$pages = max(1, (int)ceil($total/$per));
if ($page > $pages) { $page = $pages; $off = ($page-1)*$per; }

// lista
$sql = "SELECT s.id, s.form_id, s.form_code, s.version, s.status, s.created_by, s.created_at,
               JSON_LENGTH(s.payload_json) AS payload_size
        FROM forms_form_submission s
        $whereSql
        ORDER BY s.id DESC
        LIMIT ? OFFSET ?";

$stmt = $conn->prepare($sql);
if ($types) {
  $types2 = $types.'ii';
  $params2 = array_merge($params, [$per, $off]);
  $stmt->bind_param($types2, ...$params2);
} else {
  $stmt->bind_param("ii", $per, $off);
}
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

function build_q(array $over=[]){
  $q = $_GET;
  foreach($over as $k=>$v){
    if ($v===null) unset($q[$k]); else $q[$k]=$v;
  }
  return http_build_query($q);
}
?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Forms • Submissões</title>
  <link rel="stylesheet" href="<?php echo h(BASE_URL); ?>/assets/css/bootstrap.min.css">
  <style>.mono{font-family:ui-monospace,Menlo,Consolas,monospace}</style>
</head>
<body class="bg-light">
<div class="container py-4">

  <div class="d-flex justify-content-between align-items-start mb-3">
    <div>
      <h4 class="mb-0">Submissões de Formulários</h4>
      <div class="text-muted">Step 8 • Listar / ver / exportar / status</div>
    </div>
    <div class="d-flex gap-2">
      <a class="btn btn-outline-secondary" href="<?php echo h(BASE_URL); ?>/public/modules/forms/forms_listar.php">Voltar (Catálogo)</a>
      <a class="btn btn-outline-dark" href="<?php echo h(BASE_URL); ?>/modules/forms/actions/submissions_export.php?<?php echo h(build_q()); ?>">Export CSV</a>
    </div>
  </div>

  <?php if ($flash): ?>
    <div class="alert alert-warning"><?php echo h($flash); ?></div>
  <?php endif; ?>

  <div class="card shadow-sm mb-3">
    <div class="card-body">
      <form class="row g-2" method="get">
        <div class="col-md-3">
          <label class="form-label">Form Code</label>
          <input class="form-control mono" name="form_code" value="<?php echo h($form_code); ?>" placeholder="ex: RH_SOLICITACAO">
        </div>
        <div class="col-md-2">
          <label class="form-label">Status</label>
          <select class="form-select" name="status">
            <option value="">(todos)</option>
            <?php foreach(['new','processed','error'] as $s): ?>
              <option value="<?php echo h($s); ?>" <?php echo ($status===$s?'selected':''); ?>><?php echo h($s); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label">De</label>
          <input class="form-control" type="date" name="from" value="<?php echo h($date_from); ?>">
        </div>
        <div class="col-md-2">
          <label class="form-label">Até</label>
          <input class="form-control" type="date" name="to" value="<?php echo h($date_to); ?>">
        </div>
        <div class="col-md-1">
          <label class="form-label">User</label>
          <input class="form-control" name="created_by" value="<?php echo h($created_by); ?>" placeholder="id">
        </div>
        <div class="col-md-2">
          <label class="form-label">Por pág.</label>
          <select class="form-select" name="per">
            <?php foreach([10,20,30,50] as $pp): ?>
              <option value="<?php echo $pp; ?>" <?php echo ($per===$pp?'selected':''); ?>><?php echo $pp; ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-12 d-flex gap-2">
          <button class="btn btn-primary">Filtrar</button>
          <a class="btn btn-outline-secondary" href="<?php echo h(BASE_URL); ?>/public/modules/forms/submissions_listar.php">Limpar</a>
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
              <th>Form</th>
              <th>Ver</th>
              <th>Status</th>
              <th>Versão</th>
              <th>Criado por</th>
              <th>Data</th>
              <th class="text-end">Ações</th>
            </tr>
          </thead>
          <tbody>
          <?php if (!count($rows)): ?>
            <tr><td colspan="8" class="text-muted">Nenhum registro.</td></tr>
          <?php endif; ?>

          <?php foreach($rows as $r): ?>
            <tr>
              <td class="mono"><?php echo (int)$r['id']; ?></td>
              <td><span class="mono"><?php echo h($r['form_code']); ?></span></td>
              <td>
                <a class="btn btn-sm btn-outline-primary"
                   href="<?php echo h(BASE_URL); ?>/public/modules/forms/submissions_ver.php?id=<?php echo (int)$r['id']; ?>">
                  Abrir
                </a>
              </td>
              <td>
                <?php
                  $st = (string)$r['status'];
                  $badge = ($st==='processed'?'success':($st==='error'?'danger':'secondary'));
                ?>
                <span class="badge bg-<?php echo $badge; ?>"><?php echo h($st); ?></span>
              </td>
              <td class="mono"><?php echo (int)$r['version']; ?></td>
              <td class="mono"><?php echo h($r['created_by'] ?? ''); ?></td>
              <td><?php echo h($r['created_at']); ?></td>
              <td class="text-end">
                <form method="post" action="<?php echo h(BASE_URL); ?>/modules/forms/actions/submissions_status.php" class="d-inline">
                  <input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
                  <input type="hidden" name="back" value="<?php echo h(build_q(['page'=>$page])); ?>">
                  <select name="status" class="form-select form-select-sm d-inline" style="width:auto;display:inline-block" onchange="this.form.submit()">
                    <?php foreach(['new','processed','error'] as $s): ?>
                      <option value="<?php echo h($s); ?>" <?php echo ($st===$s?'selected':''); ?>><?php echo h($s); ?></option>
                    <?php endforeach; ?>
                  </select>
                </form>

                <form method="post" action="<?php echo h(BASE_URL); ?>/modules/forms/actions/submissions_delete.php"
                      class="d-inline"
                      onsubmit="return confirm('Excluir submissão #<?php echo (int)$r['id']; ?>?');">
                  <input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
                  <input type="hidden" name="back" value="<?php echo h(build_q(['page'=>$page])); ?>">
                  <button class="btn btn-sm btn-outline-danger">Del</button>
                </form>
                <form method="post" action="<?php echo h(BASE_URL); ?>/modules/forms/actions/submissions_reprocess.php" class="d-inline"
                    onsubmit="return confirm('Reprocessar #<?php echo (int)$r['id']; ?>?');">
                <input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
                <button class="btn btn-sm btn-outline-warning">Re</button>
              </form>
              </td>
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
