<?php
// pages/niveis_form.php
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config.php';
// Autenticação já carrega manifest_loader e protege_pagina()
require_once ROOT_PATH . '/system/config/autenticacao.php';
require_once ROOT_PATH . '/system/config/connect.php';

$mysqli = $mysqli ?? ($conn ?? null);
if (!$mysqli) { die('Sem conexão DB'); }

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// -----------------------------
// Carrega nível (acl_levels)
// -----------------------------
$nivel = [
  'id'             => 0,
  'nome'           => '',
  'descricao'      => '',
  'acesso_backend' => 0,
  'acesso_frontend'=> 1,
  'ativo'          => 1
];

if ($id) {
  $st = $mysqli->prepare("SELECT * FROM acl_levels WHERE id = ?");
  $st->bind_param('i', $id);
  $st->execute();
  $nivelDb = $st->get_result()->fetch_assoc();
  if ($nivelDb) {
    $nivel = $nivelDb;
  }
  $st->close();
}

// -----------------------------
// Carrega permissões "legadas" (acl_permissions) - ainda mantidas por enquanto
// -----------------------------
$perms = [];
if ($id) {
  $rp = $mysqli->prepare("SELECT * FROM acl_permissions WHERE level_id = ? ORDER BY modulo");
  $rp->bind_param('i', $id);
  $rp->execute();
  $r = $rp->get_result();
  while ($p = $r->fetch_assoc()) {
    $perms[$p['modulo']] = $p;
  }
  $rp->close();
}

// Módulos sugeridos (legado) - pode remover no futuro, quando estiver 100% RBAC
$modulosPadrao = ['bpm_designer','fluxos','calendarios','usuarios','empresas','relatorios'];

// -----------------------------
// RBAC: carregar capabilities a partir dos manifests
// -----------------------------
// Loader já foi chamado no autenticacao.php (mozart_manifest_bootstrap())
// mas chamamos de novo por segurança (é idempotente).
mozart_manifest_bootstrap();

// Todas as capabilities registradas nos manifests
$caps = mozart_get_capabilities(); 
// Exemplo de item: 
// 'helpdesk:tickets:read' => ['label'=>'Ver chamados','module_slug'=>'helpdesk', ...]

$modules = $GLOBALS['MOZART_MODULES'] ?? [];

// Agrupa capabilities por módulo para exibir no formulário
$capPorModulo = [];
foreach ($caps as $slug => $meta) {
  $modSlug = $meta['module_slug'] ?? 'core';
  if (!isset($capPorModulo[$modSlug])) {
    $capPorModulo[$modSlug] = [];
  }
  $capPorModulo[$modSlug][$slug] = $meta;
}

// -----------------------------
// Carrega capabilities já atribuídas ao nível (RBAC)
// suponto tabela: acl_level_caps(level_id INT, cap_slug VARCHAR)
// -----------------------------
$capsDoNivel = [];
if ($id) {
  if ($resCap = $mysqli->prepare("SELECT cap_slug FROM acl_level_caps WHERE level_id = ?")) {
    $resCap->bind_param('i', $id);
    $resCap->execute();
    $res = $resCap->get_result();
    while ($row = $res->fetch_assoc()) {
      $capsDoNivel[] = $row['cap_slug'];
    }
    $resCap->close();
  }
}

$page_title = ($id ? "Editar" : "Novo") . " Nível de Acesso";

include ROOT_PATH . 'system/includes/head.php';
include ROOT_PATH . 'system/includes/navbar.php';

function ck($arr, $k){ return !empty($arr[$k]) ? 'checked' : ''; }

// helper simples pra saber se capability está marcada pro nível
function cap_checked(array $capsDoNivel, string $slug): string {
  return in_array($slug, $capsDoNivel, true) ? 'checked' : '';
}
?>
<style>
.table-perms th, .table-perms td{ vertical-align: middle !important; }
.badge-pill{ border-radius:12px; padding:3px 8px; background:#f5f5f5; border:1px solid #ddd; margin-right:6px; }
.module-box {
  border:1px solid #ddd;
  border-radius:4px;
  padding:10px 12px;
  margin-bottom:10px;
}
.module-box h5 {
  margin-top:0;
  margin-bottom:8px;
  font-weight:bold;
}
.cap-list {
  columns: 2;
  -webkit-columns: 2;
  -moz-columns: 2;
}
.cap-item {
  break-inside: avoid;
  -webkit-column-break-inside: avoid;
  margin-bottom:4px;
}
</style>

<div id="page-wrapper">
  <div class="container-fluid">
    <div class="row">
      <div class="col-lg-12"><h1 class="page-header"><?= htmlspecialchars($page_title) ?></h1></div>
    </div>

    <form method="post" action="<?= BASE_URL ?>/system/actions/niveis_save.php" id="frmNivel" autocomplete="off">
      <input type="hidden" name="id" value="<?= (int)$nivel['id'] ?>">

      <div class="row">
        <div class="col-md-6">
          <div class="form-group">
            <label>Nome *</label>
            <input type="text" name="nome" class="form-control" maxlength="80" required value="<?= htmlspecialchars($nivel['nome']) ?>">
          </div>
        </div>
        <div class="col-md-6">
          <div class="form-group">
            <label>Descrição</label>
            <input type="text" name="descricao" class="form-control" maxlength="255" value="<?= htmlspecialchars($nivel['descricao']) ?>">
          </div>
        </div>
      </div>

      <div class="row">
        <div class="col-sm-3">
          <div class="checkbox"><label><input type="checkbox" name="acesso_backend" value="1" <?= !empty($nivel['acesso_backend']) ? 'checked' : ''; ?>> Acesso ao Backend</label></div>
        </div>
        <div class="col-sm-3">
          <div class="checkbox"><label><input type="checkbox" name="acesso_frontend" value="1" <?= !empty($nivel['acesso_frontend']) ? 'checked' : ''; ?>> Acesso ao Frontend</label></div>
        </div>
        <div class="col-sm-3">
          <div class="checkbox"><label><input type="checkbox" name="ativo" value="1" <?= !empty($nivel['ativo']) ? 'checked' : ''; ?>> Ativo</label></div>
        </div>
      </div>

      <hr>

      <!-- ==========================
           BLOCO RBAC (NOVO)
           Capabilities vindas dos manifests
      =========================== -->
      <h4 style="margin-top:0">Permissões (RBAC avançado por módulo)</h4>
      <p class="text-muted">
        Selecione abaixo o que este nível pode fazer em cada módulo. Estas permissões vêm dos manifests
        (<code>module_*.php</code>) e são usadas pelo RBAC para proteger menus e páginas.
      </p>

      <?php if (!empty($capPorModulo)): ?>
        <?php foreach ($capPorModulo as $modSlug => $capsModulo): 
          $modName = $modules[$modSlug]['name'] ?? strtoupper($modSlug);
        ?>
          <div class="module-box">
            <h5><?= htmlspecialchars($modName) ?> <small class="text-muted">(<?= htmlspecialchars($modSlug) ?>)</small></h5>
            <div class="cap-list">
              <?php foreach ($capsModulo as $slug => $meta): 
                $label = $meta['label'] ?? $slug;
              ?>
                <div class="cap-item">
                  <label>
                    <input type="checkbox"
                           name="caps[]"
                           value="<?= htmlspecialchars($slug) ?>"
                           <?= cap_checked($capsDoNivel, $slug); ?>>
                    <?= htmlspecialchars($label) ?>
                    <small class="text-muted">[<?= htmlspecialchars($slug) ?>]</small>
                  </label>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="alert alert-warning">
          Nenhuma capability encontrada. Verifique se os manifests (<code>module_*.php</code>) estão retornando
          a chave <code>'capabilities'</code> corretamente.
        </div>
      <?php endif; ?>

      <hr>

      <!-- ==========================
           BLOCO LEGADO (ACL POR MÓDULO)
           Mantido por compatibilidade
      =========================== -->
      <h4 style="margin-top:0">Permissões por módulo (LEGADO)</h4>
      <p class="text-muted">
        Esta seção representa o modelo antigo de permissões (ver/criar/editar/excluir/aprovar por módulo).
        Será mantida por compatibilidade enquanto o RBAC é consolidado.
      </p>

      <div class="table-responsive">
        <table class="table table-bordered table-perms" id="tbPerms">
          <thead>
            <tr>
              <th style="width:220px;">Módulo</th>
              <th>Ver</th>
              <th>Criar</th>
              <th>Editar</th>
              <th>Excluir</th>
              <th>Aprovar</th>
              <th style="width:60px;">Remover</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $existentes = array_unique(array_merge($modulosPadrao, array_keys($perms)));
            foreach ($existentes as $mod) {
              $p = $perms[$mod] ?? [];
              echo '<tr>'.
                '<td><input type="text" class="form-control mod-name" name="mods['.htmlspecialchars($mod).'][modulo]" value="'.htmlspecialchars($mod).'"></td>'.
                '<td class="text-center"><input type="checkbox" name="mods['.htmlspecialchars($mod).'][pode_ver]" '.ck($p,'pode_ver').'></td>'.
                '<td class="text-center"><input type="checkbox" name="mods['.htmlspecialchars($mod).'][pode_criar]" '.ck($p,'pode_criar').'></td>'.
                '<td class="text-center"><input type="checkbox" name="mods['.htmlspecialchars($mod).'][pode_editar]" '.ck($p,'pode_editar').'></td>'.
                '<td class="text-center"><input type="checkbox" name="mods['.htmlspecialchars($mod).'][pode_excluir]" '.ck($p,'pode_excluir').'></td>'.
                '<td class="text-center"><input type="checkbox" name="mods['.htmlspecialchars($mod).'][pode_aprovar]" '.ck($p,'pode_aprovar').'></td>'.
                '<td class="text-center"><button type="button" class="btn btn-xs btn-danger btn-del-row">&times;</button></td>'.
              '</tr>';
            }
            ?>
          </tbody>
        </table>
      </div>

      <div class="form-inline" style="margin-bottom:15px;">
        <input type="text" id="novoModulo" class="form-control" placeholder="novo módulo (ex.: auditoria)">
        <button type="button" id="btnAddModulo" class="btn btn-default">+ Adicionar módulo</button>
      </div>

      <div class="text-right">
        <a href="niveis_listar.php" class="btn btn-default">Voltar</a>
        <button type="submit" class="btn btn-primary">Salvar</button>
      </div>
    </form>
  </div>
</div>

<?php include_once ROOT_PATH . 'system/includes/code_footer.php'; ?>
<script>
(function(){
  $('#btnAddModulo').on('click', function(){
    var m = ($('#novoModulo').val()||'').trim();
    if(!m) { alert('Informe o nome do módulo.'); return; }
    const h = `
      <tr>
        <td><input type="text" class="form-control mod-name" name="mods[${m}][modulo]" value="${m}"></td>
        <td class="text-center"><input type="checkbox" name="mods[${m}][pode_ver]" checked></td>
        <td class="text-center"><input type="checkbox" name="mods[${m}][pode_criar]"></td>
        <td class="text-center"><input type="checkbox" name="mods[${m}][pode_editar]"></td>
        <td class="text-center"><input type="checkbox" name="mods[${m}][pode_excluir]"></td>
        <td class="text-center"><input type="checkbox" name="mods[${m}][pode_aprovar]"></td>
        <td class="text-center"><button type="button" class="btn btn-xs btn-danger btn-del-row">&times;</button></td>
      </tr>`;
    $('#tbPerms tbody').append(h);
    $('#novoModulo').val('');
  });

  $(document).on('click','.btn-del-row', function(){
    $(this).closest('tr').remove();
  });

  // Evita chaves vazias ao renomear módulo
  $('#frmNivel').on('submit', function(){
    $('.mod-name').each(function(){
      const v = ($(this).val()||'').trim();
      if(!v){ $(this).closest('tr').remove(); }
    });
  });
})();
</script>
<?php include ROOT_PATH . 'system/includes/footer.php'; ?>
