<?php
// modules/datasets/ds_list.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../config.php';
require_once ROOT_PATH . '/system/config/autenticacao.php';
require_once ROOT_PATH . '/system/config/connect.php';

if (session_status()===PHP_SESSION_NONE) session_start();
proteger_pagina();
?>
<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Mozart — Datasets</title>
<style>
  :root{ --bg:#f6f7f9; --card:#fff; --bd:#e5e7eb; --txt:#111; }
  *{ box-sizing:border-box; }
  body{ margin:0; font:14px/1.45 system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial; background:var(--bg); color:var(--txt); }
  .wrap{ padding:12px; }
  .card{ background:var(--card); border:1px solid var(--bd); border-radius:12px; padding:12px; }
  .filters{ display:grid; grid-template-columns: 1fr 220px 220px 220px auto; gap:10px; align-items:end; }
  label{ display:block; font-weight:900; margin:0 0 6px; color:#111827; }
  input, select{ width:100%; padding:10px; border:1px solid #d1d5db; border-radius:10px; }
  .btn{ border:1px solid #d1d5db; background:#fff; padding:8px 12px; border-radius:10px; font-weight:800; cursor:pointer; }
  .btn:hover{ background:#f3f4f6; }
  .btn.primary{ background:#111827; border-color:#111827; color:#fff; }
  .btn.danger{ background:#b91c1c; border-color:#b91c1c; color:#fff; }
  table{ width:100%; border-collapse:separate; border-spacing:0; margin-top:12px; }
  th, td{ text-align:left; padding:10px; border-bottom:1px solid var(--bd); vertical-align:top; }
  th{ font-size:12px; text-transform:uppercase; letter-spacing:.04em; color:#6b7280; }
  .muted{ color:#6b7280; font-size:12px; }
  .pill{ display:inline-flex; padding:3px 10px; border-radius:999px; font-weight:900; font-size:12px; border:1px solid var(--bd); background:#fff; }
  .pill.active{ border-color:#86efac; }
  .pill.inactive{ border-color:#fecaca; }
  .pill.connector{ border-color:#bfdbfe; }
  .pill.script{ border-color:#fde68a; }
  .actions{ display:flex; gap:8px; flex-wrap:wrap; }
  .empty{ padding:14px; text-align:center; color:#6b7280; }
  .modal-backdrop{ position:fixed; inset:0; background:rgba(0,0,0,.45); display:none; align-items:center; justify-content:center; padding:16px; z-index:50; }
  .modal{ width:min(920px, 100%); background:#fff; border-radius:14px; border:1px solid var(--bd); overflow:hidden; }
  .modal .hd{ padding:12px; border-bottom:1px solid var(--bd); display:flex; gap:10px; align-items:center; }
  .modal .bd{ padding:12px; }
  .modal textarea{ width:100%; min-height:160px; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono","Courier New", monospace; }
  pre{ background:#0b1020; color:#e5e7eb; padding:12px; border-radius:12px; overflow:auto; max-height:320px; }
  @media (max-width: 1100px){
    .filters{ grid-template-columns: 1fr 1fr; }
  }
</style>
</head>
<body>

<?php include_once __DIR__ . '/includes/ds_nav.php'; ?>

<div class="wrap">
  <div class="card">
    <div class="filters">
      <div>
        <label>Buscar</label>
        <input id="q" placeholder="nome ou dataset_key">
      </div>
      <div>
        <label>Status</label>
        <select id="status">
          <option value="">(todos)</option>
          <option value="active">active</option>
          <option value="inactive">inactive</option>
        </select>
      </div>
      <div>
        <label>Tipo</label>
        <select id="type">
          <option value="">(todos)</option>
          <option value="connector">connector</option>
          <option value="script">script</option>
        </select>
      </div>
      <div>
        <label>Tag</label>
        <input id="tag" placeholder="ex: consinco">
      </div>
      <div>
        <button class="btn" id="btnSearch">Filtrar</button>
      </div>
    </div>

    <table>
      <thead>
        <tr>
          <th>Dataset</th>
          <th>Key</th>
          <th>Tipo</th>
          <th>Status</th>
          <th>Atualizado</th>
          <th>Ações</th>
        </tr>
      </thead>
      <tbody id="tbody">
        <tr><td colspan="6" class="empty">Carregando…</td></tr>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal Runner (Consultar) -->
<div class="modal-backdrop" id="modal">
  <div class="modal">
    <div class="hd">
      <div style="font-weight:900">Consultar Dataset</div>
      <div class="muted" id="mTitle"></div>
      <div style="flex:1"></div>
      <button class="btn" id="mClose">Fechar</button>
    </div>
    <div class="bd">
      <label>Params JSON</label>
      <textarea id="mParams" placeholder='{"loja":82}'></textarea>

      <div style="margin-top:10px; display:flex; gap:8px; flex-wrap:wrap;">
        <button class="btn primary" id="mRun">Executar</button>
        <button class="btn" id="mPretty">Formatar JSON</button>
      </div>

      <label style="margin-top:12px;">Resultado</label>
      <pre id="mResult">{}</pre>
    </div>
  </div>
</div>

<script>
const API = {
  list:   'api/datasets_list.php',
  toggle:'api/dataset_toggle.php',
  del:   'api/dataset_delete.php',
  get:   'api/dataset_get.php',
  run:   'api/run.php'
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
async function apiPost(url, payload){
  const r = await fetch(url, {
    method:'POST',
    credentials:'same-origin',
    headers:{'Content-Type':'application/json; charset=utf-8'},
    body: JSON.stringify(payload||{})
  });
  const j = await r.json().catch(()=>null);
  if (!r.ok || !j || j.ok === false) throw new Error(j?.error || ('HTTP '+r.status));
  return j;
}

function buildUrl(){
  const u = new URL(API.list, window.location.href);
  const q = $('#q').value.trim();
  const st = $('#status').value;
  const tp = $('#type').value;
  const tag = $('#tag').value.trim();
  if (q) u.searchParams.set('q', q);
  if (st) u.searchParams.set('status', st);
  if (tp) u.searchParams.set('type', tp);
  if (tag) u.searchParams.set('tag', tag);
  return u.toString();
}

function pillStatus(st){
  return `<span class="pill ${st}">${st}</span>`;
}
function pillType(tp){
  return `<span class="pill ${tp}">${tp}</span>`;
}

let modalDatasetKey = '';
let modalDatasetName = '';

function openModal(key, name){
  modalDatasetKey = key;
  modalDatasetName = name;
  $('#mTitle').textContent = key + ' — ' + name;
  $('#mParams').value = JSON.stringify({ }, null, 2);
  $('#mResult').textContent = '{}';
  $('#modal').style.display = 'flex';
}
function closeModal(){
  $('#modal').style.display = 'none';
}

async function runModal(){
  try{
    const params = JSON.parse($('#mParams').value || '{}');
    $('#mResult').textContent = 'Executando…';
    const resp = await apiPost(API.run, { dataset_key: modalDatasetKey, params, caller: 'ui:ds_list' });
    $('#mResult').textContent = JSON.stringify(resp, null, 2);
  }catch(e){
    $('#mResult').textContent = JSON.stringify({ ok:false, error:e.message }, null, 2);
  }
}

function prettyModal(){
  try{
    const params = JSON.parse($('#mParams').value || '{}');
    $('#mParams').value = JSON.stringify(params, null, 2);
  }catch{ alert('JSON inválido'); }
}

$('#mClose').onclick = closeModal;
$('#modal').addEventListener('click', (e)=>{ if (e.target.id==='modal') closeModal(); });
$('#mRun').onclick = runModal;
$('#mPretty').onclick = prettyModal;

async function toggle(id){
  try{
    await apiPost(API.toggle, { id });
    await search();
  }catch(e){ alert('Erro: '+e.message); }
}
async function del(id){
  if (!confirm('Excluir este dataset? Isso apaga versões e testcases.')) return;
  try{
    await apiPost(API.del, { id });
    await search();
  }catch(e){ alert('Erro: '+e.message); }
}

async function showCode(id){
  try{
    const data = await apiGet(API.get + '?id=' + id);
    const cfg = data?.draft?.config_json ? JSON.parse(data.draft.config_json) : (data?.published?.config_json ? JSON.parse(data.published.config_json) : {});
    openModal(data.dataset.dataset_key, data.dataset.name);
    $('#mParams').value = JSON.stringify({ /* params */ }, null, 2);
    $('#mResult').textContent = JSON.stringify(cfg, null, 2);
  }catch(e){
    alert('Erro ao carregar config: ' + e.message);
  }
}

async function search(){
  $('#tbody').innerHTML = `<tr><td colspan="6" class="empty">Carregando…</td></tr>`;
  try{
    const data = await apiGet(buildUrl());
    const items = data.items || [];
    if (!items.length) {
      $('#tbody').innerHTML = `<tr><td colspan="6" class="empty">Nenhum resultado.</td></tr>`;
      return;
    }

    $('#tbody').innerHTML = items.map(d => `
      <tr>
        <td>
          <div style="font-weight:900">${esc(d.name)}</div>
          <div class="muted">#${d.id}</div>
        </td>
        <td>
          <div style="font-weight:900">${esc(d.dataset_key)}</div>
        </td>
        <td>${pillType(d.type)}</td>
        <td>${pillStatus(d.status)}</td>
        <td class="muted">${esc(d.updated_at||'')}</td>
        <td>
          <div class="actions">
            <button class="btn" onclick="openModal('${esc(d.dataset_key)}','${esc(d.name)}')">Consultar</button>
            <a class="btn" href="ds_editor.php?id=${d.id}">Editar</a>
            <button class="btn" onclick="showCode(${d.id})">Exibir código/config</button>
            <a class="btn" href="ds_versions.php?id=${d.id}">Histórico</a>
            <button class="btn" onclick="toggle(${d.id})">Ativar/Desativar</button>
            <button class="btn danger" onclick="del(${d.id})">Excluir</button>
          </div>
        </td>
      </tr>
    `).join('');
  }catch(e){
    $('#tbody').innerHTML = `<tr><td colspan="6" class="empty">Erro: ${esc(e.message)}</td></tr>`;
  }
}

$('#btnSearch').onclick = search;
window.addEventListener('keydown', (e)=>{ if (e.key==='Enter') search(); });

window.openModal = openModal;
window.toggle = toggle;
window.del = del;
window.showCode = showCode;

search();
</script>
</body>
</html>
