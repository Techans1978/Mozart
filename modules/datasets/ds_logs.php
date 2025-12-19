<?php
// modules/datasets/ds_logs.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../config.php';
require_once ROOT_PATH . '/system/config/autenticacao.php';
require_once ROOT_PATH . '/system/config/connect.php';

if (session_status()===PHP_SESSION_NONE) session_start();
proteger_pagina();

$dataset_key = trim($_GET['dataset_key'] ?? '');
?>
<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Mozart — Dataset Logs</title>
<style>
  :root{--bg:#f6f7f9;--card:#fff;--bd:#e5e7eb;--txt:#111;}
  *{box-sizing:border-box;}
  body{margin:0;font:14px/1.45 system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial;background:var(--bg);color:var(--txt);}
  .wrap{padding:12px;}
  .card{background:var(--card);border:1px solid var(--bd);border-radius:12px;padding:12px;}
  .filters{display:grid;grid-template-columns: 1fr 200px 200px 1fr auto;gap:10px;align-items:end;}
  label{display:block;font-weight:900;margin:0 0 6px;color:#111827;}
  input,select{width:100%;padding:10px;border:1px solid #d1d5db;border-radius:10px;}
  .btn{border:1px solid #d1d5db;background:#fff;padding:8px 12px;border-radius:10px;font-weight:800;cursor:pointer;}
  .btn:hover{background:#f3f4f6;}
  table{width:100%;border-collapse:separate;border-spacing:0;margin-top:12px;}
  th,td{text-align:left;padding:10px;border-bottom:1px solid var(--bd);vertical-align:top;}
  th{font-size:12px;text-transform:uppercase;letter-spacing:.04em;color:#6b7280;}
  .muted{color:#6b7280;font-size:12px;}
  .pill{display:inline-flex;padding:3px 10px;border-radius:999px;font-weight:900;font-size:12px;border:1px solid var(--bd);background:#fff;}
  .pill.ok{border-color:#86efac;}
  .pill.error{border-color:#fecaca;}
  pre{background:#0b1020;color:#e5e7eb;padding:12px;border-radius:12px;overflow:auto;max-height:280px;}
  .modal-backdrop{position:fixed;inset:0;background:rgba(0,0,0,.45);display:none;align-items:center;justify-content:center;padding:16px;z-index:50;}
  .modal{width:min(920px,100%);background:#fff;border-radius:14px;border:1px solid var(--bd);overflow:hidden;}
  .modal .hd{padding:12px;border-bottom:1px solid var(--bd);display:flex;gap:10px;align-items:center;}
  .modal .bd{padding:12px;}
</style>
</head>
<body>
<?php include_once __DIR__ . '/includes/ds_nav.php'; ?>

<div class="wrap">
  <div class="card">
    <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
      <div style="font-weight:900">Logs de Execução</div>
      <div class="muted">moz_ds_exec_log</div>
      <div style="flex:1"></div>
      <a class="btn" href="ds_list.php">Voltar</a>
    </div>

    <div class="filters" style="margin-top:10px;">
      <div>
        <label>dataset_key</label>
        <input id="dataset_key" value="<?php echo htmlspecialchars($dataset_key, ENT_QUOTES); ?>" placeholder="ex: ti.filas_por_categoria">
      </div>
      <div>
        <label>Status</label>
        <select id="status">
          <option value="">(todos)</option>
          <option value="ok">ok</option>
          <option value="error">error</option>
        </select>
      </div>
      <div>
        <label>Limite</label>
        <select id="limit">
          <option>50</option>
          <option>100</option>
          <option>200</option>
          <option>500</option>
        </select>
      </div>
      <div>
        <label>Caller</label>
        <input id="caller" placeholder="ex: bpm:serviceTask">
      </div>
      <div>
        <button class="btn" id="btnSearch">Filtrar</button>
      </div>
    </div>

    <table>
      <thead>
        <tr>
          <th>Quando</th>
          <th>Dataset</th>
          <th>Versão</th>
          <th>Status</th>
          <th>Tempo</th>
          <th>Rows</th>
          <th>Caller</th>
          <th>Detalhes</th>
        </tr>
      </thead>
      <tbody id="tbody">
        <tr><td colspan="8" class="muted">Carregando…</td></tr>
      </tbody>
    </table>
  </div>
</div>

<div class="modal-backdrop" id="modal">
  <div class="modal">
    <div class="hd">
      <div style="font-weight:900">Detalhes do Log</div>
      <div style="flex:1"></div>
      <button class="btn" id="mClose">Fechar</button>
    </div>
    <div class="bd">
      <div class="muted" id="mMeta"></div>
      <label>Params</label>
      <pre id="mParams">{}</pre>
      <label>Erro</label>
      <pre id="mErr"></pre>
    </div>
  </div>
</div>

<script>
const API = { list: 'api/exec_log_list.php' };
const $ = (s)=>document.querySelector(s);

async function apiGet(url){
  const r = await fetch(url, { credentials:'same-origin' });
  const j = await r.json().catch(()=>null);
  if (!r.ok || !j || j.ok === false) throw new Error(j?.error || ('HTTP '+r.status));
  return j;
}
function pill(st){ return `<span class="pill ${st}">${st}</span>`; }
function esc(s){
  return (s??'').toString().replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;')
    .replaceAll('"','&quot;').replaceAll("'","&#039;");
}

function openModal(item){
  $('#mMeta').textContent = `#${item.id} • ${item.created_at} • ${item.dataset_key} • ${item.status}`;
  $('#mParams').textContent = item.params_json || '{}';
  $('#mErr').textContent = item.error_msg || '';
  $('#modal').style.display = 'flex';
}
function closeModal(){ $('#modal').style.display = 'none'; }

$('#mClose').onclick = closeModal;
$('#modal').addEventListener('click', (e)=>{ if (e.target.id==='modal') closeModal(); });

async function load(){
  const u = new URL(API.list, window.location.href);
  const key = $('#dataset_key').value.trim();
  const st  = $('#status').value;
  const lim = $('#limit').value;
  const caller = $('#caller').value.trim();
  if (key) u.searchParams.set('dataset_key', key);
  if (st)  u.searchParams.set('status', st);
  if (lim) u.searchParams.set('limit', lim);
  if (caller) u.searchParams.set('caller', caller);

  $('#tbody').innerHTML = `<tr><td colspan="8" class="muted">Carregando…</td></tr>`;

  try{
    const data = await apiGet(u.toString());
    const items = data.items || [];

    if (!items.length) {
      $('#tbody').innerHTML = `<tr><td colspan="8" class="muted">Sem logs.</td></tr>`;
      return;
    }

    $('#tbody').innerHTML = items.map(x=>`
      <tr>
        <td class="muted">${esc(x.created_at||'')}</td>
        <td><div style="font-weight:900">${esc(x.dataset_key||'')}</div></td>
        <td class="muted">${esc(x.version_id||'')}</td>
        <td>${pill(x.status)}</td>
        <td class="muted">${esc(x.exec_ms||0)}ms</td>
        <td class="muted">${esc(x.result_rows_count||'')}</td>
        <td class="muted">${esc(x.caller||'')}</td>
        <td><button class="btn" onclick='openModal(${JSON.stringify(x).replaceAll("'","\\'")})'>Ver</button></td>
      </tr>
    `).join('');
  }catch(e){
    $('#tbody').innerHTML = `<tr><td colspan="8" class="muted">Erro: ${esc(e.message)}</td></tr>`;
  }
}

window.openModal = openModal;
$('#btnSearch').onclick = load;
window.addEventListener('keydown', (e)=>{ if (e.key==='Enter') load(); });

load();
</script>
</body>
</html>
