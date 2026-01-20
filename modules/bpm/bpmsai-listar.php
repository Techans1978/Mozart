<?php
// modules/bpm/bpmsai-listar.php
ini_set('display_errors',1); ini_set('display_startup_errors',1); error_reporting(E_ALL);
require_once __DIR__.'/../../config.php';
require_once ROOT_PATH.'/system/config/autenticacao.php';
require_once ROOT_PATH.'/system/config/connect.php';
if (session_status()===PHP_SESSION_NONE) session_start();
proteger_pagina();

include_once ROOT_PATH . '/system/includes/head.php';
include_once ROOT_PATH . '/system/includes/navbar.php';

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

// filtros
$q    = trim($_GET['q'] ?? '');
$only = (int)($_GET['only_active'] ?? 1);
$cat  = (int)($_GET['cat'] ?? 0);

// categorias BPM
$cats = [];
try {
  $rs = $conn->query("SELECT id, nome FROM bpm_categorias WHERE ativo=1 ORDER BY sort_order ASC, nome ASC");
  if($rs) $cats = $rs->fetch_all(MYSQLI_ASSOC);
} catch(Throwable $e){ $cats=[]; }

$where = [];
$bindTypes = '';
$bindVals = [];

if($q!==''){
  $where[] = "(f.name LIKE CONCAT('%',?,'%') OR f.code LIKE CONCAT('%',?,'%'))";
  $bindTypes .= 'ss';
  $bindVals[] = $q;
  $bindVals[] = $q;
}
if($only===1){ $where[] = "f.is_active=1"; }
if($cat>0){ $where[] = "f.category_id=?"; $bindTypes.='i'; $bindVals[]=$cat; }

$sql = "
SELECT
  f.id, f.code, f.name, f.description, f.is_active,
  f.participants_cache,
  f.category_id,
  c.nome AS category_name,
  av.version_number AS active_version
FROM bpmsai_flow f
LEFT JOIN bpm_categorias c ON c.id=f.category_id
LEFT JOIN bpmsai_flow_version av ON av.id=f.active_version_id
".
($where?('WHERE '.implode(' AND ', $where)):'').
" ORDER BY f.updated_at DESC, f.created_at DESC, f.name ASC";

$items = [];
$stmt = $conn->prepare($sql);
if($stmt){
  if($bindTypes!=='') $stmt->bind_param($bindTypes, ...$bindVals);
  $stmt->execute();
  $res = $stmt->get_result();
  if($res) $items = $res->fetch_all(MYSQLI_ASSOC);
  $stmt->close();
}
?>
<style>
  #page-wrapper{ background:#f6f7f9; }
  .shell{max-width:1180px;margin:10px auto;padding:0 10px;}
  .filters{display:flex;gap:8px;flex-wrap:wrap;margin:10px 0;align-items:center}
  .grid{display:grid;grid-template-columns:1fr;gap:10px}
  .cardx{background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:12px}
  .top{display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap}
  .badges{display:flex;gap:6px;flex-wrap:wrap;align-items:center}
  .badge{padding:3px 8px;border-radius:999px;border:1px solid #e5e7eb;background:#fafafa}
  .muted{color:#6b7280}
  .chips{display:flex;gap:6px;flex-wrap:wrap;margin-top:6px}
  .chip{padding:3px 8px;border-radius:999px;background:#f3f4f6;border:1px solid #e5e7eb;font-size:12px}
  .actions{display:flex;gap:6px;flex-wrap:wrap;align-items:center}
  .actions .btn{white-space:nowrap}
</style>

<div id="page-wrapper"><div class="container-fluid">
  <div class="row"><div class="col-lg-12"><h1 class="page-header">Fluxos BPMs AI</h1></div></div>

<div class="shell">
  <div class="top">
    <div>
      <h2 style="margin:0">BPMs AI</h2>
      <div class="muted">Fluxos simples por etapas (rascunho → testes → publicar versão).</div>
    </div>
    <div class="actions">
      <a class="btn btn-primary" href="<?= BASE_URL ?>/modules/bpm/bpmsai-wizard.php">+ Novo BPMs AI</a>
    </div>
  </div>

  <form class="filters" method="get">
    <input class="form-control" name="q" value="<?= h($q) ?>" placeholder="Buscar por nome ou código" style="max-width:360px">
    <select class="form-control" name="cat" style="max-width:260px">
      <option value="0">Categoria (todas)</option>
      <?php foreach($cats as $c): ?>
        <option value="<?= (int)$c['id'] ?>" <?= ($cat==(int)$c['id'])?'selected':'' ?>><?= h($c['nome']) ?></option>
      <?php endforeach; ?>
    </select>
    <label class="checkbox-inline" style="margin:0">
      <input type="checkbox" name="only_active" value="1" <?= ($only===1)?'checked':'' ?>> Somente ativos
    </label>
    <button class="btn btn-default" type="submit">Filtrar</button>
  </form>

  <div class="grid">
    <?php if(!$items): ?>
      <div class="cardx muted">Nenhum fluxo encontrado.</div>
    <?php else: foreach($items as $it): ?>
      <div class="cardx">
        <div class="top">
          <div style="min-width:280px;flex:1">
            <div class="badges">
              <span class="badge"><b><?= h($it['code']) ?></b></span>
              <span class="badge">Versão ativa: <b><?= h($it['active_version'] ? ('v'.$it['active_version']) : '-') ?></b></span>
              <?= ((int)$it['is_active']===1) ? '<span class="badge">Ativo</span>' : '<span class="badge" style="background:#fff7ed;border-color:#fed7aa">Inativo</span>' ?>
              <span class="badge"><?= h($it['category_name'] ?: '-') ?></span>
            </div>
            <div style="margin-top:6px"><b style="font-size:16px"><?= h($it['name']) ?></b></div>
            <div class="muted" style="margin-top:4px"><?= h($it['description'] ?: '—') ?></div>
            <?php if(!empty($it['participants_cache'])): ?>
              <div class="chips">
                <?php foreach(array_filter(array_map('trim', explode(',', (string)$it['participants_cache']))) as $p): ?>
                  <span class="chip"><?= h($p) ?></span>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>

          <div class="actions">
            <a class="btn btn-xs btn-default" href="<?= BASE_URL ?>/modules/bpm/bpmsai-wizard.php?flow_id=<?= (int)$it['id'] ?>">Editar</a>
            <a class="btn btn-xs btn-default" href="<?= BASE_URL ?>/modules/bpm/bpmsai-versoes.php?flow_id=<?= (int)$it['id'] ?>">Versões</a>

            <?php if((int)$it['is_active']===1): ?>
              <a class="btn btn-xs btn-warning" href="<?= BASE_URL ?>/modules/bpm/actions/bpmsai_toggle.php?id=<?= (int)$it['id'] ?>&to=0" onclick="return confirm('Inativar este fluxo?')">Inativar</a>
            <?php else: ?>
              <a class="btn btn-xs btn-success" href="<?= BASE_URL ?>/modules/bpm/actions/bpmsai_toggle.php?id=<?= (int)$it['id'] ?>&to=1" onclick="return confirm('Ativar este fluxo?')">Ativar</a>
            <?php endif; ?>

            <a class="btn btn-xs btn-danger" href="<?= BASE_URL ?>/modules/bpm/actions/bpmsai_delete.php?id=<?= (int)$it['id'] ?>" onclick="return confirm('Excluir este fluxo e TODAS as versões?')">Excluir</a>
          </div>
        </div>
      </div>
    <?php endforeach; endif; ?>
  </div>
</div>
  </div>
</div>

<?php include_once ROOT_PATH . '/system/includes/footer.php'; ?>
