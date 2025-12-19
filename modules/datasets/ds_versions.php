<?php
// modules/datasets/ds_versions.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../config.php';
require_once ROOT_PATH . '/system/config/autenticacao.php';
require_once ROOT_PATH . '/system/config/connect.php';

if (session_status()===PHP_SESSION_NONE) session_start();
proteger_pagina();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id<=0) die("Informe ?id=DATASET_ID");
?>
<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Mozart — Dataset Versões</title>
<style>
  :root{ --bg:#f6f7f9; --card:#fff; --bd:#e5e7eb; --txt:#111; }
  *{ box-sizing:border-box; }
  body{ margin:0; font:14px/1.45 system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial; background:var(--bg); color:var(--txt); }
  .wrap{ padding:12px; }
  .card{ background:var(--card); border:1px solid var(--bd); border-radius:12px; padding:12px; }
  table{ width:100%; border-collapse:separate; border-spacing:0; margin-top:12px; }
  th, td{ text-align:left; padding:10px; border-bottom:1px solid var(--bd); vertical-align:top; }
  th{ font-size:12px; text-transform:uppercase; letter-spacing:.04em; color:#6b7280; }
  .muted{ color:#6b7280; font-size:12px; }
  .btn{ border:1px solid #d1d5db; background:#fff; padding:8px 12px; border-radius:10px; font-weight:800; cursor:pointer; }
  .btn:hover{ background:#f3f4f6; }
  pre{ background:#0b1020; color:#e5e7eb; padding:12px; border-radius:12px; overflow:auto; max-height:320px; }
  .pill{ display:inline-flex; padding:3px 10px; border-radius:999px; font-weight:900; font-size:12px; border:1px solid var(--bd); background:#fff; }
  .pill.draft{ border-color:#fde68a; }
  .pill.published{ border-color:#86efac; }
  .actions{ display:flex; gap:8px; flex-wrap:wrap; }
</style>
</head>
<body>

<?php include_once __DIR__ . '/includes/ds_nav.php'; ?>

<div class="wrap">
  <div class="card">
    <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
      <div style="font-weight:900">Versões Dataset</div>
      <div class="muted">dataset_id #<?php echo (int)$id; ?></div>
      <div style="flex:1"></div>
      <a class="btn" href="ds_editor.php?id=<?php echo (int)$id; ?>">Abrir Editor</a>
      <a class="btn" href="ds_list.php">Voltar</a>
    </div>

    <table>
      <thead>
        <tr>
          <th>Tipo</th>
          <th>Versão</th>
          <th>Checksum</th>
          <th>Notas</th>
          <th>Criado</th>
          <th>Publicado</th>
          <th>Ações</th>
        </tr>
      </thead>
      <tbody id="tbody">
        <tr><td colspan="7" class="muted">Carregando…</td></tr>
      </tbody>
    </table>

    <div style="margin-top:12px; font-weight:900">Preview config_json</div>
    <pre id="preview">{}</pre>
  </div>
</div>

<script>
const DATASET_ID = <?php echo (int)$id; ?>;
const API = {
  list: 'api/version_list.php?dataset_id=' + DATASET_ID,
  get:  'api/version_get_json.php?id='
};

const $ = (s)=>document.querySelector(s);
function esc(s){
  return (s??'').toString().replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;')
    .replaceAll('"','&quot;').replaceAll("'","&#039;");
}
async function apiGet(url){
  const r = await fetch(url, { credentials:'same-origin' });
  const j = await r.json().catch(()=>null);
  if (!r.ok || !j || j.ok === false) throw new Error(j?.error || ('HTTP '+r.status));
  return j;
}
function downloadText(text, filename){
  const blob = new Blob([text], {type:'application/json'});
  const a = document.createElement('a');
  a.href = URL.createObjectURL(blob);
  a.download = filename;
  document.body.appendChild(a);
  a.click();
  setTimeout(()=>{ URL.revokeObjectURL(a.href); a.remove(); }, 700);
}

async function preview(id){
  const data = await apiGet(API.get + id);
  const cfg = data.item?.config_json || '{}';
  $('#preview').textContent = cfg;
}
async function dl(id, filename){
  const data = await apiGet(API.get + id);
  const cfg = data.item?.config_json || '{}';
  downloadText(cfg, filename);
}

async function load(){
  try{
    const data = await apiGet(API.list);
    const items = data.items || [];
    if (!items.length) {
      $('#tbody').innerHTML = `<tr><td colspan="7" class="muted">Sem versões.</td></tr>`;
      $('#preview').textContent = '{}';
      return;
    }

    $('#tbody').innerHTML = items.map(v=>{
      const typ = v.type;
      const pill = `<span class="pill ${typ}">${typ}</span>`;
      const ver = (typ==='published') ? ('v'+(v.version_num||0)) : 'draft';
      const fname = `dataset_${DATASET_ID}_${typ}${typ==='published' ? ('_v'+(v.version_num||0)) : ''}.json`;
      return `
        <tr>
          <td>${pill}</td>
          <td>${esc(ver)}</td>
          <td class="muted">${esc(v.checksum||'')}</td>
          <td>${esc(v.notes||'')}</td>
          <td class="muted">${esc(v.created_at||'')}</td>
          <td class="muted">${esc(v.published_at||'')}</td>
          <td>
            <div class="actions">
              <button class="btn" onclick="preview(${v.id})">Preview</button>
              <button class="btn" onclick="dl(${v.id}, '${fname.replaceAll("'","") }')">Baixar</button>
            </div>
          </td>
        </tr>
      `;
    }).join('');

    await preview(items[0].id);

  }catch(e){
    $('#tbody').innerHTML = `<tr><td colspan="7" class="muted">Erro: ${esc(e.message)}</td></tr>`;
    $('#preview').textContent = JSON.stringify({ok:false,error:e.message}, null, 2);
  }
}

window.preview = preview;
window.dl = dl;
load();
</script>
</body>
</html>
