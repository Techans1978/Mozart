<?php
// modules/datasets/ds_testcases.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../config.php';
require_once ROOT_PATH . '/system/config/autenticacao.php';
require_once ROOT_PATH . '/system/config/connect.php';

if (session_status()===PHP_SESSION_NONE) session_start();
proteger_pagina();

$dataset_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($dataset_id<=0) die("Informe ?id=DATASET_ID");
?>
<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Mozart — Dataset Testcases</title>
<style>
  :root{--bg:#f6f7f9;--card:#fff;--bd:#e5e7eb;--txt:#111;}
  *{box-sizing:border-box;}
  body{margin:0;font:14px/1.45 system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial;background:var(--bg);color:var(--txt);}
  .wrap{padding:12px;}
  .card{background:var(--card);border:1px solid var(--bd);border-radius:12px;padding:12px;}
  .row{display:flex;gap:12px;flex-wrap:wrap;}
  .col{flex:1;min-width:320px;}
  label{display:block;font-weight:900;margin:10px 0 6px;color:#111827;}
  input,textarea{width:100%;padding:10px;border:1px solid #d1d5db;border-radius:10px;}
  textarea{min-height:170px;font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,"Liberation Mono","Courier New",monospace;}
  .btn{border:1px solid #d1d5db;background:#fff;padding:8px 12px;border-radius:10px;font-weight:800;cursor:pointer;}
  .btn:hover{background:#f3f4f6;}
  .btn.primary{background:#111827;border-color:#111827;color:#fff;}
  .btn.ok{background:#065f46;border-color:#065f46;color:#fff;}
  .btn.danger{background:#b91c1c;border-color:#b91c1c;color:#fff;}
  table{width:100%;border-collapse:separate;border-spacing:0;margin-top:12px;}
  th,td{text-align:left;padding:10px;border-bottom:1px solid var(--bd);vertical-align:top;}
  th{font-size:12px;text-transform:uppercase;letter-spacing:.04em;color:#6b7280;}
  .muted{color:#6b7280;font-size:12px;}
  pre{background:#0b1020;color:#e5e7eb;padding:12px;border-radius:12px;overflow:auto;max-height:360px;}
  .actions{display:flex;gap:8px;flex-wrap:wrap;}
</style>
</head>
<body>
<?php include_once __DIR__ . '/includes/ds_nav.php'; ?>

<div class="wrap">
  <div class="card">
    <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
      <div style="font-weight:900">Testcases</div>
      <div class="muted">dataset_id #<?php echo (int)$dataset_id; ?></div>
      <div style="flex:1"></div>
      <a class="btn" href="ds_editor.php?id=<?php echo (int)$dataset_id; ?>">Editor</a>
      <a class="btn" href="ds_versions.php?id=<?php echo (int)$dataset_id; ?>">Versões</a>
      <a class="btn" href="ds_list.php">Voltar</a>
    </div>

    <div class="row">
      <div class="col">
        <label>Nome do testcase</label>
        <input id="tc_name" placeholder="ex: Loja 82 - Telefonia">

        <label>Params JSON</label>
        <textarea id="tc_params" placeholder='{"loja":82,"categoria":"Telefonia"}'></textarea>

        <label>Expected JSON (opcional)</label>
        <textarea id="tc_expected" placeholder='{"min_rows":1}'></textarea>

        <div style="margin-top:10px;display:flex;gap:8px;flex-wrap:wrap;">
          <button class="btn primary" id="btnSave">Salvar testcase</button>
          <button class="btn" id="btnPretty">Formatar JSON</button>
        </div>

        <div class="muted" style="margin-top:8px;">
          Dica: expected v1 é livre (metadado). Depois a gente valida regras (min_rows, contains, etc.).
        </div>
      </div>

      <div class="col">
        <label>Resultado execução</label>
        <pre id="out">{}</pre>
      </div>
    </div>

    <label style="margin-top:14px;">Lista de testcases</label>
    <table>
      <thead>
        <tr>
          <th>Nome</th>
          <th>Ativo</th>
          <th>Criado</th>
          <th>Ações</th>
        </tr>
      </thead>
      <tbody id="tbody">
        <tr><td colspan="4" class="muted">Carregando…</td></tr>
      </tbody>
    </table>
  </div>
</div>

<script>
const DATASET_ID = <?php echo (int)$dataset_id; ?>;
const API = {
  list: 'api/testcase_list.php?dataset_id=' + DATASET_ID,
  save: 'api/testcase_save.php',
  run:  'api/testcase_run.php',
  toggle: 'api/testcase_toggle.php'
};
const $ = (s)=>document.querySelector(s);

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

function safeParse(text){
  text = (text||'').trim();
  if (!text) return {};
  try { return JSON.parse(text); } catch { return null; }
}
function setOut(obj){
  $('#out').textContent = typeof obj === 'string' ? obj : JSON.stringify(obj, null, 2);
}
function pretty(){
  const p = safeParse($('#tc_params').value); if (!p) return alert('Params inválido');
  $('#tc_params').value = JSON.stringify(p, null, 2);
  const e = safeParse($('#tc_expected').value); if (e===null) return alert('Expected inválido');
  $('#tc_expected').value = JSON.stringify(e||{}, null, 2);
}

async function save(){
  const name = ($('#tc_name').value||'').trim();
  if (!name) return alert('Informe nome.');

  const params = safeParse($('#tc_params').value); if (!params) return alert('Params inválido');
  const expected = safeParse($('#tc_expected').value); if (expected===null) return alert('Expected inválido');

  try{
    const resp = await apiPost(API.save, { dataset_id: DATASET_ID, name, params, expected });
    alert('Salvo! testcase_id=' + resp.id);
    await load();
  }catch(e){
    alert('Erro: ' + e.message);
  }
}

async function run(id){
  try{
    setOut('Executando…');
    const resp = await apiPost(API.run, { id });
    setOut(resp);
  }catch(e){
    setOut({ok:false,error:e.message});
  }
}

async function toggle(id){
  try{
    await apiPost(API.toggle, { id });
    await load();
  }catch(e){ alert('Erro: '+e.message); }
}

async function load(){
  try{
    const data = await apiGet(API.list);
    const items = data.items || [];
    const tb = $('#tbody');

    if (!items.length) {
      tb.innerHTML = `<tr><td colspan="4" class="muted">Sem testcases.</td></tr>`;
      return;
    }

    tb.innerHTML = items.map(t=>`
      <tr>
        <td>
          <div style="font-weight:900">${(t.name||'')}</div>
          <div class="muted">#${t.id}</div>
        </td>
        <td>${t.is_active==1 ? 'sim' : 'não'}</td>
        <td class="muted">${t.created_at||''}</td>
        <td>
          <div class="actions">
            <button class="btn primary" onclick="run(${t.id})">Executar</button>
            <button class="btn" onclick="toggle(${t.id})">Ativar/Desativar</button>
          </div>
        </td>
      </tr>
    `).join('');

  }catch(e){
    $('#tbody').innerHTML = `<tr><td colspan="4" class="muted">Erro: ${e.message}</td></tr>`;
  }
}

window.run = run;
window.toggle = toggle;

$('#btnSave').onclick = save;
$('#btnPretty').onclick = pretty;

(function init(){
  $('#tc_params').value = JSON.stringify({ loja:82 }, null, 2);
  $('#tc_expected').value = JSON.stringify({ min_rows: 1 }, null, 2);
  load();
})();
</script>
</body>
</html>
