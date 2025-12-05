<?php
// modules/forms/forms_listar.php
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(E_ALL);

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
        $refs=[];
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

function table_exists($t){
    global $conn;
    $t = $conn->real_escape_string($t);
    $rs = $conn->query("SHOW TABLES LIKE '$t'");
    return $rs && $rs->num_rows > 0;
}

$flash   = $_SESSION['__flash'] ?? null; unset($_SESSION['__flash']);

$q       = isset($_GET['q']) ? trim($_GET['q']) : '';
$tipo    = isset($_GET['tipo']) ? trim($_GET['tipo']) : '';
$ativos  = isset($_GET['ativos']) ? (int)$_GET['ativos'] : -1;

// Garante existência da tabela
if (!table_exists('moz_forms')) {
    include_once ROOT_PATH.'system/includes/head.php';
    include_once ROOT_PATH.'system/includes/navbar.php';
    echo '<div id="page-wrapper"><div class="container-fluid"><div class="row"><div class="col-lg-12">';
    echo '<h3 class="page-header">Formulários</h3>';
    echo '<div class="alert alert-danger">Tabela moz_forms não encontrada no banco.</div>';
    echo '</div></div></div></div>';
    include_once ROOT_PATH.'system/includes/footer.php';
    exit;
}

// Monta query com filtros
$sql = "SELECT id, nome, slug, tipo, categoria, caminho_json, caminho_html,
               versao, ativo, criado_por, criado_em, atualizado_em
        FROM moz_forms
        WHERE 1=1";

$types = '';
$params = [];

if ($q !== '') {
    $sql .= " AND (nome LIKE ? OR slug LIKE ? OR categoria LIKE ?)";
    $types .= 'sss';
    $like = "%".$q."%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

if ($tipo !== '') {
    $sql .= " AND tipo = ?";
    $types .= 's';
    $params[] = $tipo;
}

if ($ativos === 0 || $ativos === 1) {
    $sql .= " AND ativo = ?";
    $types .= 'i';
    $params[] = $ativos;
}

$sql .= " ORDER BY tipo, categoria, nome, versao DESC";

$rows = q_all($sql, $types, $params);

include_once ROOT_PATH.'system/includes/head.php';
include_once ROOT_PATH.'system/includes/navbar.php';
?>
<div id="page-wrapper">
  <div class="container-fluid">

    <div class="row">
      <div class="col-lg-12">
        <h1 class="page-header">Formulários (Forms.js)</h1>
      </div>
    </div>

    <?php if ($flash): ?>
      <div class="alert alert-info"><?= htmlspecialchars($flash['m']) ?></div>
    <?php endif; ?>

    <form class="form-inline" method="get" style="margin-bottom:10px;">
      <input class="form-control"
             name="q"
             value="<?= htmlspecialchars($q) ?>"
             placeholder="Buscar por nome/slug/categoria">

      <select class="form-control" name="tipo" style="margin-left:8px;">
        <option value="">Todos os tipos</option>
        <?php
        $tipos = [
          'helpdesk' => 'Helpdesk',
          'bpm'      => 'BPM',
          'outro'    => 'Outro'
        ];
        foreach ($tipos as $val => $label):
        ?>
          <option value="<?= $val ?>" <?= $tipo === $val ? 'selected' : '' ?>>
            <?= htmlspecialchars($label) ?>
          </option>
        <?php endforeach; ?>
      </select>

      <label class="checkbox-inline" style="margin-left:8px;">
        <input type="checkbox" name="ativos" value="1" <?= $ativos === 1 ? 'checked' : ''; ?>>
        Somente ativos
      </label>

      <button class="btn btn-primary" type="submit">Filtrar</button>

      <!-- Ajuste o caminho do designer conforme onde ele estiver -->
      <a class="btn btn-success" href="<?= BASE_URL ?>/modules/forms/forms_designer.php">
        + Novo Formulário
      </a>
    </form>

    <div class="table-responsive">
      <table class="table table-striped table-bordered">
        <thead>
          <tr>
            <th>#</th>
            <th>Nome</th>
            <th>Slug</th>
            <th>Tipo</th>
            <th>Categoria</th>
            <th>Versão</th>
            <th>Ativo</th>
            <th>Criado em</th>
            <th style="width:230px;">Ações</th>
          </tr>
        </thead>
        <tbody>
        <?php if (!$rows): ?>
          <tr>
            <td colspan="9" class="text-center text-muted">Nenhum formulário encontrado.</td>
          </tr>
        <?php else: foreach ($rows as $r): ?>
          <tr>
            <td><?= (int)$r['id'] ?></td>
            <td><?= htmlspecialchars($r['nome']) ?></td>
            <td><?= htmlspecialchars($r['slug']) ?></td>
            <td><?= htmlspecialchars($r['tipo']) ?></td>
            <td><?= htmlspecialchars($r['categoria'] ?? '') ?></td>
            <td><?= (int)$r['versao'] ?></td>
            <td><?= ((int)$r['ativo'] === 1 ? 'Ativo' : 'Inativo') ?></td>
            <td><?= htmlspecialchars($r['criado_em']) ?></td>
            <td>
              <!-- Editar no designer: você depois ajusta o designer para carregar pelo ID -->
              <a class="btn btn-xs btn-default"
                 href="<?= BASE_URL ?>/modules/forms/forms_designer.php?id=<?= (int)$r['id'] ?>">
                Editar
              </a>

              <a class="btn btn-xs btn-warning"
                 href="<?= BASE_URL ?>/modules/forms/actions/forms_toggle.php?id=<?= (int)$r['id'] ?>">
                Ativar/Desativar
              </a>

              <a class="btn btn-xs btn-danger"
                 href="<?= BASE_URL ?>/modules/forms/actions/forms_delete.php?id=<?= (int)$r['id'] ?>"
                 onclick="return confirm('Excluir este formulário? Certifique-se de que ele não está em uso em processos ou serviços.');">
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
