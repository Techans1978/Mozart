<?php
// modules/forms/categorias_form_form.php
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
function q_one($sql,$types='',$params=[]) {
    $r = q_all($sql,$types,$params);
    return $r ? $r[0] : null;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Valores padrão
$cat = [
    'id'            => 0,
    'nome'          => '',
    'slug'          => '',
    'descricao'     => '',
    'contexto_tipo' => 'global',
    'contexto_id'   => null,
    'cor_hex'       => '',
    'ativo'         => 1,
    'sort_order'    => 0,
];

// Carrega categoria se edição
if ($id > 0) {
    $row = q_one("SELECT * FROM moz_form_category WHERE id = ?", 'i', [$id]);
    if (!$row) {
        $_SESSION['__flash'] = ['m' => 'Categoria de formulário não encontrada.'];
        header('Location: categorias_form_listar.php');
        exit;
    }
    $cat = $row;
}

include_once ROOT_PATH.'system/includes/head.php';
include_once ROOT_PATH.'system/includes/navbar.php';
?>
<div id="page-wrapper">
  <div class="container-fluid">
    <div class="row">
      <div class="col-lg-12">
        <h1 class="page-header"><?= $id > 0 ? 'Editar' : 'Nova' ?> Categoria de Formulário</h1>
      </div>
    </div>

    <form method="post" action="<?= BASE_URL ?>/modules/forms/actions/categorias_form_save.php" class="form">
      <input type="hidden" name="id" value="<?= (int)$id ?>">

      <div class="form-group">
        <label>Nome *</label>
        <input class="form-control" type="text" name="nome"
               value="<?= htmlspecialchars($cat['nome'] ?? '') ?>" required>
      </div>

      <div class="form-group">
        <label>Slug (código interno)</label>
        <input class="form-control" type="text" name="slug" maxlength="150"
               value="<?= htmlspecialchars($cat['slug'] ?? '') ?>">
        <p class="help-block">
          Opcional. Se vazio, pode ser gerado automaticamente a partir do nome.
        </p>
      </div>

      <div class="form-group">
        <label>Descrição</label>
        <textarea class="form-control" name="descricao" rows="3"><?= htmlspecialchars($cat['descricao'] ?? '') ?></textarea>
      </div>

      <div class="form-group">
        <label>Contexto</label>
        <div class="row">
          <div class="col-md-4">
            <select class="form-control" name="contexto_tipo">
              <?php
              $ctx = $cat['contexto_tipo'] ?? 'global';
              $opts = ['global' => 'Global', 'bpm' => 'BPM', 'helpdesk' => 'Helpdesk', 'custom' => 'Custom'];
              foreach ($opts as $val => $label):
              ?>
                <option value="<?= $val ?>" <?= $ctx === $val ? 'selected' : '' ?>>
                  <?= htmlspecialchars($label) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-4">
            <input class="form-control" type="number" name="contexto_id"
                   value="<?= (int)($cat['contexto_id'] ?? 0) ?>"
                   placeholder="ID do contexto (opcional)">
          </div>
        </div>
        <p class="help-block">
          Ex.: contexto BPM com ID do processo, ou Helpdesk com ID de serviço específico.
        </p>
      </div>

      <div class="form-group">
        <label>Cor (hex, opcional)</label>
        <input class="form-control" type="text" name="cor_hex" maxlength="7"
               value="<?= htmlspecialchars($cat['cor_hex'] ?? '') ?>"
               placeholder="#0EA5E9">
        <p class="help-block">
          Opcional. Use para colorir badges na listagem de formulários.
        </p>
      </div>

      <div class="form-group">
        <label>Ordem</label>
        <input class="form-control" type="number" name="sort_order"
               value="<?= (int)($cat['sort_order'] ?? 0) ?>">
      </div>

      <div class="checkbox">
        <label>
          <input type="checkbox" name="ativo" <?= ((int)($cat['ativo'] ?? 1) === 1 ? 'checked' : '') ?>> Ativa
        </label>
      </div>

      <div class="form-group" style="margin-top:8px;">
        <a class="btn btn-default" href="categorias_form_listar.php">Voltar</a>
        <button class="btn btn-primary" type="submit">Salvar</button>
      </div>
    </form>
  </div>
</div>
<?php include_once ROOT_PATH.'system/includes/footer.php'; ?>
