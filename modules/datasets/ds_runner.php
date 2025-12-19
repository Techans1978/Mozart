<?php
// modules/datasets/ds_runner.php
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
<title>Mozart — Dataset Runner</title>
<style>
  :root{ --bg:#f6f7f9; --card:#fff; --bd:#e5e7eb; --txt:#111; }
  *{ box-sizing:border-box; }
  body{ margin:0; font:14px/1.45 system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial; background:var(--bg); color:var(--txt); }
  .wrap{ padding:12px; }
  .card{ background:var(--card); border:1px solid var(--bd); border-radius:12px; padding:12px; }
  .row{ display:flex; gap:12px; flex-wrap:wrap; }
  .col{ flex:1; min-width: 280px; }
  label{ display:block; font-weight:900; margin:10px 0 6px; color:#111827; }
  input, textarea{ width:100%; padding:10px; border:1px solid #d1d5db; border-radius:10px; }
  textarea{ min-height:220px; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono","Courier New", monospace; }
  .btn{ border:1px solid #d1d5db; background:#fff; padding:8px 12px; border-radius:10px; font-weight:800; cursor:pointer; }
  .btn:hover{ background:#f3f4f6; }
  .btn.primary{ background:#111827; border-color:#111827; color:#fff; }
  .btn.danger{ background:#b91c1c; border-color:#b91c1c; color:#fff; }
  pre{ background:#0b1020; color:#e5e7eb; padding:12px; border-radius:12px; overflow:auto; max-height:420px; }
  .hint{ color:#6b7280; font-size:12px; margin-top:6px; }
  .history{ display:flex; flex-direction:column; gap:8px; margin-top:10px; }
  .hitem{ border:1px solid var(--bd); border-radius:12px; padding:10px; background:#fff; }
  .hitem .t{ font-weight:900; }
  .hitem .s{ color:#6b7280; font-size:12px; margin-top:4px; }
  .hitem .a{ margin-top:8px; display:flex; gap:8px; flex-wrap:wrap; }
</style>
</head>
<body>

<?php include_once __DIR__ . '/includes/ds_nav.php'; ?>

<div class="wrap">
  <div class="card">
    <div style="font-weight:900">Runner — executar dataset_key</div>
    <div class="hint">Chama <b>api/run.php</b>. Use params JSON (objeto).</div>

    <div class="row">
      <div class="col">
        <label>dataset_key</label>
        <input id="dataset_key" placeholder="ex: ti.filas_por_categoria">

        <label>Params JSON</label>
        <textarea id="params" placeholder='{"categoria":"Telefonia"}'></textarea>

        <div style="margin-top:10px; display:flex; gap:8px; flex-wrap:wrap;">
          <button class="btn primary" id="btnRun">Executar</button>
          <button class="btn" id="btnPretty">Formatar JSON</button>
          <button class="btn danger" id="btnClearHist">Limpar histórico</button>
        </div>
      </div>

      <div class="col">
        <label>Resultado</label>
        <pre id="result">{}</pre>

        <label>Histórico (local)</label>
        <div class="history" id="history"></div>
      </div>
    </div>
  </div>
</div>

<script>
const API = { run: 'api/run.php' };
const $ = (s)=>document.querySelector(s);

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

function safeJsonParse(text){
  try { return JSON.parse(text); } catch { return null; }
}
function setResult(obj){
  $('#result').textContent = typeof obj === 'string' ? obj : JSON.stringify(obj, null, 2);
}

function loadHistory(){
  const h = JSON.parse(localStorage.getItem('ds_runner_hist') || '[]');
  const box = $('#history');
  box.innerHTML = '';
  if (!h.length) { box.innerHTML = '<div class="hint">Sem histórico.</div>'; return; }

  for (const item of h) {
    const div = document.createElement('div');
    div.className = 'hitem';
    div.innerHTML = `
      <div class="t">${item.dataset_key}</div>
      <div class="s">${item.at} • ${item.ok ? 'ok' : 'erro'} • ${item.exec_ms ?? ''}ms</div>
      <div class="a">
        <button class="btn" data-act="load">Carregar</button>
        <button class="btn" data-act="view">Ver resultado</button>
      </div>
    `;
    div.querySelector('[data-act="load"]').onclick = () => {
      $('#dataset_key').value = item.dataset_key;
      $('#params').value = item.params_pretty || item.params_raw || '';
      setResult(item.response || {});
      window.scrollTo({top:0, behavior:'smooth'});
    };
    div.querySelector('[data-act="view"]').onclick = () => setResult(item.response || {});
    box.appendChild(div);
  }
}

function pushHistory(item){
  const h = JSON.parse(localStorage.getItem('ds_runner_hist') || '[]');
  h.unshift(item);
  while (h.length > 25) h.pop();
  localStorage.setItem('ds_runner_hist', JSON.stringify(h));
  loadHistory();
}

async function run(){
  const key = $('#dataset_key').value.trim();
  if (!key) return alert('Informe dataset_key.');

  const obj = safeJsonParse($('#params').value || '{}');
  if (!obj) return alert('Params JSON inválido.');

  setResult('Executando…');
  const started = Date.now();

  try{
    const resp = await apiPost(API.run, { dataset_key: key, params: obj, caller:'ui:ds_runner' });
    setResult(resp);

    pushHistory({
      at: new Date().toISOString().slice(0,19).replace('T',' '),
      ok: true,
      dataset_key: key,
      exec_ms: resp.exec_ms ?? (Date.now()-started),
      params_raw: $('#params').value,
      params_pretty: JSON.stringify(obj, null, 2),
      response: resp
    });
  }catch(e){
    const msg = { ok:false, error:e.message };
    setResult(msg);
    pushHistory({
      at: new Date().toISOString().slice(0,19).replace('T',' '),
      ok: false,
      dataset_key: key,
      exec_ms: (Date.now()-started),
      params_raw: $('#params').value,
      params_pretty: JSON.stringify(obj, null, 2),
      response: msg
    });
  }
}

$('#btnRun').onclick = run;
$('#btnPretty').onclick = ()=>{
  const obj = safeJsonParse($('#params').value || '{}');
  if (!obj) return alert('JSON inválido.');
  $('#params').value = JSON.stringify(obj, null, 2);
};
$('#btnClearHist').onclick = ()=>{
  if (!confirm('Limpar histórico local?')) return;
  localStorage.removeItem('ds_runner_hist');
  loadHistory();
};

window.addEventListener('keydown', (e)=>{
  const mod = e.ctrlKey || e.metaKey;
  if (mod && e.key.toLowerCase()==='enter') { e.preventDefault(); run(); }
});

(function init(){
  $('#params').value = JSON.stringify({ loja: 82 }, null, 2);
  loadHistory();
})();
</script>
</body>
</html>
