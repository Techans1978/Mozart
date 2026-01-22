<?php
// modules/bpm/categorias_bpm_form.php
ini_set('display_errors',1); ini_set('display_startup_errors',1); error_reporting(E_ALL);

require_once __DIR__ . '/../../config.php';
require_once ROOT_PATH . '/system/config/autenticacao.php';
require_once ROOT_PATH . '/system/config/connect.php';

if (!isset($conn) && isset($mysqli)) { $conn = $mysqli; }
if (!($conn instanceof mysqli)) { die('Conexão MySQLi $conn não encontrada.'); }

if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
proteger_pagina();

$conn->set_charset('utf8mb4');

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function q_all(mysqli $conn, string $sql, string $types='', array $params=[]): array {
  $st = $conn->prepare($sql);
  if(!$st){ throw new Exception('prepare: '.$conn->error); }
  if($types !== ''){
    $refs = [];
    foreach($params as $k=>$v){ $refs[$k] = &$params[$k]; }
    $st->bind_param($types, ...$refs);
  }
  if(!$st->execute()){ $e = $st->error; $st->close(); throw new Exception('execute: '.$e); }
  $rs = $st->get_result();
  $rows = [];
  if($rs){ while($r=$rs->fetch_assoc()){ $rows[]=$r; } }
  $st->close();
  return $rows;
}
function q_one(mysqli $conn, string $sql, string $types='', array $params=[]): ?array {
  $r = q_all($conn,$sql,$types,$params);
  return $r ? $r[0] : null;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$cat = [
  'id'=>0,
  'nome'=>'',
  'codigo'=>'',
  'parent_id'=>null,
  'ativo'=>1,
  'sort_order'=>0,
];

if ($id > 0) {
  $row = q_one($conn, "SELECT id,nome,codigo,parent_id,sort_order,ativo FROM bpm_categorias WHERE id=? LIMIT 1", 'i', [$id]);
  if (!$row) {
    $_SESSION['__flash'] = ['m'=>'Categoria não encontrada.'];
    header('Location: '.BASE_URL.'/modules/bpm/categorias_bpm_listar.php');
    exit;
  }
  $cat = $row;
}

// options do select de pai (usa closure table se existir; se não existir, cai pra listagem simples)
$parentOptions = [];
try {
  $types=''; $params=[];
  $sqlParents = "SELECT c.id,
                        GROUP_CONCAT(a.nome ORDER BY p.depth SEPARATOR ' / ') AS path
                 FROM bpm_categorias c
                 JOIN bpm_categorias_paths p ON p.descendant_id=c.id
                 JOIN bpm_categorias a ON a.id=p.ancestor_id
                 WHERE 1=1";
  if ($id>0){
    // não pode escolher pai dentro do próprio subtree (evita ciclo)
    $sqlParents .= " AND c.id NOT IN (SELECT descendant_id FROM bpm_categorias_paths WHERE ancestor_id=?)";
    $types='i'; $params=[$id];
  }
  $sqlParents .= " GROUP BY c.id ORDER BY path";
  $parentOptions = q_all($conn,$sqlParents,$types,$params);
} catch (Throwable $e){
  // fallback sem paths
  $parentOptions = q_all($conn, "SELECT id, nome AS path FROM bpm_categorias ORDER BY nome", '', []);
}

include_once ROOT_PATH.'system/includes/head.php';
include_once ROOT_PATH.'system/includes/navbar.php';

$flash = $_SESSION['__flash'] ?? null;
unset($_SESSION['__flash']);
?>
<div id="page-wrapper">
  <div class="container-fluid">
    <div class="row">
      <div class="col-lg-12">
        <h1 class="page-header"><?= $id>0?'Editar':'Nova'?> Categoria BPM</h1>
      </div>
    </div>

    <?php if ($flash): ?>
      <div class="alert alert-warning"><?= h($flash['m']) ?></div>
    <?php endif; ?>

    <form method="post" action="<?= BASE_URL ?>/modules/bpm/actions/categorias_bpm_save.php" class="form">
      <input type="hidden" name="id" value="<?= (int)$id ?>">

      <div class="form-group">
        <label>Nome *</label>
        <input class="form-control" type="text" name="nome" value="<?= h($cat['nome']) ?>" required>
      </div>

      <div class="form-group">
        <label>Código</label>
        <input class="form-control" type="text" name="codigo" maxlength="40" value="<?= h($cat['codigo'] ?? '') ?>">
        <div class="help-block">Opcional. Se vazio, o sistema pode deixar em branco.</div>
      </div>

      <div class="form-group">
        <label>Categoria Pai</label>
        <select class="form-control" name="parent_id">
          <option value="">— (raiz) —</option>
          <?php foreach($parentOptions as $op): ?>
            <option value="<?= (int)$op['id'] ?>" <?= ((string)($cat['parent_id']??'')===(string)$op['id']?'selected':'') ?>>
              <?= h($op['path']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label>Ordem</label>
        <input class="form-control" type="number" name="sort_order" value="<?= (int)$cat['sort_order'] ?>">
      </div>

      <div class="checkbox">
        <label>
          <input type="checkbox" name="ativo" <?= ((int)$cat['ativo']===1?'checked':'') ?>> Ativa
        </label>
      </div>

      <div class="form-group" style="margin-top:8px;">
        <a class="btn btn-default" href="<?= BASE_URL ?>/modules/bpm/categorias_bpm_listar.php">Voltar</a>
        <button class="btn btn-primary" type="submit">Salvar</button>
      </div>
    </form>
  </div>
</div>
<?php include_once ROOT_PATH.'system/includes/footer.php'; ?>
