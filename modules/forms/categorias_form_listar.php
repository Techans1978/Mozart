<?php
// modules/forms/categorias_form_listar.php
ini_set('display_errors',1); ini_set('display_startup_errors',1); error_reporting(E_ALL);

require_once __DIR__ . '/../../config.php';
require_once ROOT_PATH . '/system/config/autenticacao.php';
require_once ROOT_PATH . '/system/config/connect.php';

if (!isset($conn) && isset($mysqli)) { $conn = $mysqli; }
if (!($conn instanceof mysqli)) { die('Conexão MySQLi $conn não encontrada.'); }
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }

function q_all($sql,$types='',$params=[]) {
    global $conn;
    $st = $conn->prepare($sql);
    if (!$st) { die('prepare:'.$conn->error); }
    if ($types && $params) {
        $refs = [];
        foreach($params as $k=>$v){ $refs[$k] = &$params[$k]; }
        $st->bind_param($types, ...$refs);
    }
    $st->execute();
    $rs = $st->get_result();
    $rows = [];
    if ($rs) {
        while($r = $rs->fetch_assoc()) { $rows[] = $r; }
    }
    $st->close();
    return $rows;
}
function table_exists($t) {
    global $conn;
    $t = $conn->real_escape_string($t);
    $rs = $conn->query("SHOW TABLES LIKE '$t'");
    return $rs && $rs->num_rows > 0;
}

$flash   = $_SESSION['__flash'] ?? null; unset($_SESSION['__flash']);
$q       = isset($_GET['q']) ? trim($_GET['q']) : '';
$ativos  = isset($_GET['ativos']) ? (int)$_GET['ativos'] : -1;
$ctx     = isset($_GET['contexto_tipo']) ? trim($_GET['contexto_tipo']) : '';

if (!table_exists('moz_form_category')) {
    include_once ROOT_PATH.'system/includes/head.php';
    include_once ROOT_PATH.'system/includes/navbar.php';
    echo '<div id="page-wrapper"><div class="container-fluid"><div class="row"><div class="col-lg-12">';
    echo '<h3 class="page-header">Categorias de Formulário</h3>';
    echo '<div class="alert alert-danger">Tabela moz_form_category não encontrada no banco.</div>';
    echo '</div></div></div></div>';
    include_once ROOT_PATH.'system/includes/footer.php';
    exit;
}

$sql = "SELECT id, nome, slug, descricao, contexto_tipo, contexto_id, cor_hex, ativo, sort_order
        FROM moz_form_category
        WHERE 1=1";

$types = '';
$params = [];

if ($q !== '') {
    $sql .= " AND (nome LIKE ? OR slug LIKE ?)";
    $types .= 'ss';
    $like = "%".$q."%";
    $params[] = $like;
    $params[] = $like;
}

if ($ativos === 0 || $ativos === 1) {
    $sql .= " AND ativo = ?";
    $types .= 'i';
    $params[] = $ativos;
}

if ($ctx !== '') {
    $sql .= " AND contexto_tipo = ?";
    $types .= 's';
    $params[] = $ctx;
}

$sql .= " ORDER BY contexto_tipo, sort_order, nome";

$rows = q_all($sql, $types, $params);

include_once ROOT_PATH.'system/includes/head.php';
include_once ROOT_PATH.'system/includes/navbar.php';
?>
<div id="page-wrapper">
  <div class="container-fluid">
    <div class="row">
      <div class="col-lg-12">
        <h1 class="page-header">Categorias de Formulário</h1>
      </div>
    </div>

    <?php if ($flash): ?>
      <div class="alert alert-info"><?= htmlspecialchars($flash['m']) ?></div>
    <?php endif; ?>

    <form class="form-inline" method="get" style="margin-bottom:10px;">
      <input class="form-control" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Buscar por nome/slug">

      <label class="checkbox-inline" style="margin-left:8px;">
        <input type="checkbox" name="ativos" value="1" <?= $ativos === 1 ? 'checked' : ''; ?>> Somente ativas
      </label>

      <select class="form-control" name="contexto_tipo" style="margin-left:8px;">
        <option value="">Todos os contextos</option>
        <?php
        $opts = ['global' => 'Global', 'bpm' => 'BPM', 'helpdesk' => 'Helpdesk', 'custom' => 'Custom'];
        foreach ($opts as $val => $label):
        ?>
          <option value="<?= $val ?>" <?= $ctx === $val ? 'selected' : '' ?>>
            <?= htmlspecialchars($label) ?>
          </option>
        <?php endforeach; ?>
      </select>

      <button class="btn btn-primary" type="submit">Filtrar</button>
      <a class="btn btn-success" href="<?= BASE_URL ?>/modules/forms/categorias_form_form.php">+ Nova Categoria</a>
    </form>

    <div class="table-responsive">
      <table class="table table-striped table-bordered">
        <thead>
          <tr>
            <th>#</th>
            <th>Nome</th>
            <th>Slug</th>
            <th>Contexto</th>
            <th>Contexto ID</th>
            <th>Cor</th>
            <th>Ativa</th>
            <th>Ordem</th>
            <th style="width:220px;">Ações</th>
          </tr>
        </thead>
        <tbody>
        <?php if (!$rows): ?>
          <tr>
            <td colspan="9" class="text-center text-muted">Nenhuma categoria encontrada.</td>
          </tr>
        <?php else: foreach ($rows as $r): ?>
          <tr>
            <td><?= (int)$r['id'] ?></td>
            <td><?= htmlspecialchars($r['nome'] ?? '') ?></td>
            <td><?= htmlspecialchars($r['slug'] ?? '') ?></td>
            <td><?= htmlspecialchars($r['contexto_tipo'] ?? '') ?></td>
            <td><?= (int)($r['contexto_id'] ?? 0) ?></td>
            <td>
              <?php if (!empty($r['cor_hex'])): ?>
                <span style="display:inline-block;width:16px;height:16px;border-radius:3px;border:1px solid #ccc;background:<?= htmlspecialchars($r['cor_hex']); ?>"></span>
                <small><?= htmlspecialchars($r['cor_hex']); ?></small>
              <?php endif; ?>
            </td>
            <td><?= ((int)$r['ativo'] === 1 ? 'Ativa' : 'Inativa') ?></td>
            <td><?= (int)$r['sort_order'] ?></td>
            <td>
              <a class="btn btn-xs btn-default"
                 href="<?= BASE_URL ?>/modules/forms/categorias_form_form.php?id=<?= (int)$r['id'] ?>">Editar</a>
              <a class="btn btn-xs btn-warning"
                 href="<?= BASE_URL ?>/modules/forms/actions/categorias_form_toggle.php?id=<?= (int)$r['id'] ?>">
                Ativar/Desativar
              </a>
              <a class="btn btn-xs btn-danger"
                 href="<?= BASE_URL ?>/modules/forms/actions/categorias_form_delete.php?id=<?= (int)$r['id'] ?>"
                 onclick="return confirm('Excluir esta categoria? Certifique-se de que não está sendo usada por formulários.');">
                Excluir
              </a>
            </td>
          </tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php include_once ROOT_PATH.'system/includes/footer.php'; ?>
