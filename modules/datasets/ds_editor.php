<?php
// modules/datasets/ds_editor.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../config.php';
require_once ROOT_PATH . '/system/config/autenticacao.php';
require_once ROOT_PATH . '/system/config/connect.php';

if (session_status()===PHP_SESSION_NONE) session_start();
proteger_pagina();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
?>
<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Mozart — Dataset Editor</title>
<style>
  :root{ --bg:#f6f7f9; --card:#fff; --bd:#e5e7eb; --txt:#111; }
  *{ box-sizing:border-box; }
  body{ margin:0; font:14px/1.45 system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial; background:var(--bg); color:var(--txt); }
  .wrap{ padding:12px; }
  .card{ background:var(--card); border:1px solid var(--bd); border-radius:12px; padding:12px; }
  .tabs{ display:flex; gap:8px; flex-wrap:wrap; margin:8px 0 12px; }
  .tab{ border:1px solid #d1d5db; background:#fff; padding:8px 12px; border-radius:10px; font-weight:900; cursor:pointer; }
  .tab.active{ background:#111827; border-color:#111827; color:#fff; }
  label{ display:block; font-weight:900; margin:10px 0 6px; color:#111827; }
  input, select, textarea{ width:100%; padding:10px; border:1px solid #d1d5db; border-radius:10px; }
  textarea{ min-height:180px; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono","Courier New", monospace; }
  .row{ display:flex; gap:12px; flex-wrap:wrap; }
  .col{ flex:1; min-width: 280px; }
  .btn{ border:1px solid #d1d5db; background:#fff; padding:8px 12px; border-radius:10px; font-weight:800; cursor:pointer; }
  .btn:hover{ background:#f3f4f6; }
  .btn.primary{ background:#111827; border-color:#111827; color:#fff; }
  .btn.ok{ background:#065f46; border-color:#065f46; color:#fff; }
  .hint{ color:#6b7280; font-size:12px; margin-top:6px; }
  pre{ background:#0b1020; color:#e5e7eb; padding:12px; border-radius:12px; overflow:auto; max-height:260px; }
  .hidden{ display:none; }
</style>
</head>
<body>

<?php include_once __DIR__ . '/includes/ds_nav.php'; ?>

<div class="wrap">
  <div class="card">
    <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
      <div style="font-weight:900">Editor Dataset</div>
      <div class="hint"><?php echo $id>0 ? ('#'.$id) : '(novo)'; ?></div>
      <div style="flex:1"></div>
      <button class="btn primary" id="btnSave">Salvar (Draft)</button>
      <button class="btn ok" id="btnPublish">Publicar</button>
      <a class="btn" href="ds_list.php">Voltar</a>
    </div>

    <div class="tabs">
      <button class="tab active" data-tab="meta">Metadados</button>
      <button class="tab" data-tab="config">Config</button>
      <button class="tab" data-tab="mapping">Mapping</button>
      <button class="tab" data-tab="transform">Transform</button>
      <button class="tab" data-tab="preview">Preview JSON</button>
    </div>

    <!-- META -->
    <div id="tab-meta">
      <div class="row">
        <div class="col">
          <label>Nome</label>
          <input id="name" placeholder="ex: Filas por categoria">
        </div>
        <div class="col">
          <label>dataset_key</label>
          <input id="dataset_key" placeholder="ex: ti.filas_por_categoria">
          <div class="hint">Chave estável usada no BPM/Cérebro.</div>
        </div>
      </div>

      <label>Descrição</label>
      <input id="description" placeholder="opcional">

      <div class="row">
        <div class="col">
          <label>Tipo</label>
          <select id="type">
            <option value="connector">connector (conector + mapping)</option>
            <option value="script">script (PHP)</option>
          </select>
        </div>
        <div class="col">
          <label>Tags (separar por vírgula)</label>
          <input id="tags" placeholder="ex: consinco,ti">
        </div>
        <div class="col">
          <label>Cache TTL (segundos)</label>
          <input id="cache_ttl_sec" type="number" value="0">
          <div class="hint">0 = sem cache (v1).</div>
        </div>
      </div>

      <label>Notas do Draft (opcional)</label>
      <input id="notes" placeholder="ex: ajuste de mapping">
    </div>

    <!-- CONFIG -->
    <div id="tab-config" class="hidden">
      <div class="row">
        <div class="col">
          <label>Connector Type (quando tipo=connector)</label>
          <select id="connector_type">
            <option value="http">http</option>
            <option value="mysql">mysql</option>
          </select>
          <div class="hint">V1: http e mysql. Oracle/Consinco entra via connector futuro (mesma ideia).</div>
        </div>
      </div>

      <div id="cfg-http">
        <div class="row">
          <div class="col">
            <label>HTTP Method</label>
            <select id="http_method">
              <option>GET</option><option>POST</option><option>PUT</option><option>PATCH</option><option>DELETE</option>
            </select>
          </div>
          <div class="col">
            <label>URL (pode usar {{param}})</label>
            <input id="http_url" placeholder="https://api.exemplo.com/itens?loja={{loja}}">
          </div>
        </div>

        <label>Headers (JSON)</label>
        <textarea id="http_headers" placeholder='{"Accept":"application/json"}'></textarea>

        <label>Body (JSON opcional)</label>
        <textarea id="http_body" placeholder='{"loja":"{{loja}}"}'></textarea>

        <div class="row">
          <div class="col">
            <label>Timeout (seg)</label>
            <input id="http_timeout" type="number" value="20">
          </div>
        </div>
      </div>

      <div id="cfg-mysql" class="hidden">
        <div class="row">
          <div class="col"><label>Host</label><input id="mysql_host" placeholder="127.0.0.1"></div>
          <div class="col"><label>DB</label><input id="mysql_db" placeholder="banco"></div>
        </div>
        <div class="row">
          <div class="col"><label>User</label><input id="mysql_user" placeholder="usuario"></div>
          <div class="col"><label>Pass</label><input id="mysql_pass" type="password" placeholder="senha"></div>
          <div class="col"><label>Port</label><input id="mysql_port" type="number" value="3306"></div>
        </div>

        <label>SQL (use ? para binds)</label>
        <textarea id="mysql_sql" placeholder="SELECT id, nome FROM pessoas WHERE loja_id = ? LIMIT 50"></textarea>

        <label>Bind array (JSON) — use {{param}} dentro</label>
        <textarea id="mysql_bind" placeholder='["{{loja_id}}"]'></textarea>
      </div>

      <div id="cfg-script" class="hidden">
        <label>Script PHP (deve preencher $rows e opcional $meta)</label>
        <textarea id="script_code" placeholder="<?php\n// $params disponível\n$rows = [ ['ok'=>true] ];\n$meta = ['info'=>'...'];\n?>"></textarea>
        <div class="hint">V1: script via eval (admin only recomendado).</div>
      </div>
    </div>

    <!-- MAPPING -->
    <div id="tab-mapping" class="hidden">
      <label>Mapping JSON</label>
      <textarea id="mapping_json" placeholder='{"rows_from":"data.items","fields":{"sku":"sku","descricao":"desc"}}'></textarea>
      <div class="hint">
        - HTTP: define <b>rows_from</b> e <b>fields</b> (paths simples)<br>
        - MySQL: geralmente não precisa mapping (já vem rows)
      </div>
    </div>

    <!-- TRANSFORM -->
    <div id="tab-transform" class="hidden">
      <div class="row">
        <div class="col">
          <label>Transform habilitado?</label>
          <select id="transform_enabled">
            <option value="0">Não</option>
            <option value="1">Sim</option>
          </select>
        </div>
      </div>

      <label>Transform PHP (recebe $rows e $params; deve manter $rows array)</label>
      <textarea id="transform_code" placeholder="<?php\nforeach ($rows as &$r) { $r['nome'] = strtoupper($r['nome'] ?? ''); }\n?>"></textarea>
    </div>

    <!-- PREVIEW -->
    <div id="tab-preview" class="hidden">
      <div style="display:flex; gap:8px; flex-wrap:wrap;">
        <button class="btn" id="btnBuild">Gerar JSON</button>
        <button class="btn" id="btnCopy">Copiar JSON</button>
      </div>
      <pre id="preview">{}</pre>
    </div>
  </div>
</div>

<script>
const ID = <?php echo (int)$id; ?>;
const API = {
  get: 'api/dataset_get.php?id=',
  save: 'api/dataset_save.php',
  publish: 'api/dataset_publish.php'
};
const $ = (s)=>document.querySelector(s);

function esc(s){
  return (s??'').toString().replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;');
}
function safeCopy(text){
  return (navigator.clipboard?.writeText(text).catch(()=>{
    const ta=document.createElement('textarea'); ta.value=text;
    ta.style.position='fixed'; ta.style.left='-9999px'; document.body.appendChild(ta);
    ta.select(); try{document.execCommand('copy');} finally{ta.remove();}
  }));
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

function showTab(name){
  document.querySelectorAll('.tab').forEach(t=>t.classList.toggle('active', t.dataset.tab===name));
  ['meta','config','mapping','transform','preview'].forEach(x=>{
    $('#tab-'+x).classList.toggle('hidden', x!==name);
  });
}

document.querySelectorAll('.tab').forEach(t=>{
  t.onclick = ()=>showTab(t.dataset.tab);
});

function jsonParseOrEmpty(v){
  v = (v||'').trim();
  if (!v) return {};
  try { return JSON.parse(v); } catch { return null; }
}

function toggleCfgAreas(){
  const type = $('#type').value;
  const ctype = $('#connector_type').value;

  $('#cfg-script').classList.toggle('hidden', type!=='script');
  $('#cfg-http').classList.toggle('hidden', !(type==='connector' && ctype==='http'));
  $('#cfg-mysql').classList.toggle('hidden', !(type==='connector' && ctype==='mysql'));
}

$('#type').onchange = toggleCfgAreas;
$('#connector_type').onchange = toggleCfgAreas;

function buildConfig(){
  const type = $('#type').value;

  if (type === 'script') {
    return {
      type: 'script',
      code: $('#script_code').value || ''
    };
  }

  // connector
  const ctype = $('#connector_type').value;

  if (ctype === 'http') {
    const headers = jsonParseOrEmpty($('#http_headers').value);
    if (headers === null) throw new Error('Headers JSON inválido');
    const bodyRaw = ($('#http_body').value||'').trim();
    let body = null;
    if (bodyRaw) {
      const bodyParsed = jsonParseOrEmpty(bodyRaw);
      if (bodyParsed === null) throw new Error('Body JSON inválido');
      body = bodyParsed;
    }
    return {
      type: 'connector',
      connector: { type: 'http' },
      request: {
        method: $('#http_method').value || 'GET',
        url: $('#http_url').value || '',
        headers: headers || {},
        body: body,
        timeout_sec: parseInt($('#http_timeout').value,10) || 20
      }
    };
  }

  if (ctype === 'mysql') {
    const bind = jsonParseOrEmpty($('#mysql_bind').value);
    if (bind === null) throw new Error('Bind JSON inválido');
    return {
      type: 'connector',
      connector: {
        type: 'mysql',
        host: $('#mysql_host').value || '127.0.0.1',
        db: $('#mysql_db').value || '',
        user: $('#mysql_user').value || '',
        pass: $('#mysql_pass').value || '',
        port: parseInt($('#mysql_port').value,10) || 3306
      },
      request: {
        sql: $('#mysql_sql').value || '',
        bind: Array.isArray(bind) ? bind : []
      }
    };
  }

  throw new Error('Connector type inválido');
}

function buildPayload(){
  const tags = ($('#tags').value||'').split(',').map(s=>s.trim()).filter(Boolean);

  const cfg = buildConfig();

  const mapping = jsonParseOrEmpty($('#mapping_json').value);
  if (mapping === null) throw new Error('Mapping JSON inválido');
  if (Object.keys(mapping||{}).length) cfg.mapping = mapping;

  const trEnabled = ($('#transform_enabled').value === '1');
  const trCode = ($('#transform_code').value || '').trim();
  cfg.transform = { enabled: trEnabled, code: trCode };

  return {
    id: ID,
    dataset_key: ($('#dataset_key').value||'').trim(),
    name: ($('#name').value||'').trim(),
    description: ($('#description').value||'').trim(),
    type: ($('#type').value||'connector'),
    tags: tags,
    cache_ttl_sec: parseInt($('#cache_ttl_sec').value,10) || 0,
    notes: ($('#notes').value||'').trim(),
    config: cfg
  };
}

async function load(){
  if (!ID) {
    // defaults
    $('#type').value = 'connector';
    $('#connector_type').value = 'http';
    $('#http_headers').value = JSON.stringify({ Accept:'application/json' }, null, 2);
    $('#mapping_json').value = JSON.stringify({ rows_from:'data.items', fields:{} }, null, 2);
    toggleCfgAreas();
    return;
  }

  const data = await apiGet(API.get + ID);
  const ds = data.dataset;

  $('#name').value = ds.name || '';
  $('#dataset_key').value = ds.dataset_key || '';
  $('#description').value = ds.description || '';
  $('#type').value = ds.type || 'connector';
  $('#cache_ttl_sec').value = ds.cache_ttl_sec || 0;

  // tags_json pode ser string json
  try {
    const tags = JSON.parse(ds.tags_json || '[]');
    $('#tags').value = Array.isArray(tags) ? tags.join(',') : '';
  } catch { $('#tags').value=''; }

  // usar draft se existir, senão published
  let cfg = {};
  try{
    if (data.draft?.config_json) cfg = JSON.parse(data.draft.config_json);
    else if (data.published?.config_json) cfg = JSON.parse(data.published.config_json);
  }catch(e){ cfg = {}; }

  // preencher conforme cfg
  if (cfg.type === 'script' || ds.type === 'script') {
    $('#type').value = 'script';
    $('#script_code').value = cfg.code || '';
  } else {
    $('#type').value = 'connector';
    const ctype = cfg?.connector?.type || 'http';
    $('#connector_type').value = ctype;

    if (ctype === 'http') {
      $('#http_method').value = cfg?.request?.method || 'GET';
      $('#http_url').value = cfg?.request?.url || '';
      $('#http_headers').value = JSON.stringify(cfg?.request?.headers || {}, null, 2);
      $('#http_body').value = cfg?.request?.body ? JSON.stringify(cfg.request.body, null, 2) : '';
      $('#http_timeout').value = cfg?.request?.timeout_sec || 20;
    }
    if (ctype === 'mysql') {
      $('#mysql_host').value = cfg?.connector?.host || '127.0.0.1';
      $('#mysql_db').value = cfg?.connector?.db || '';
      $('#mysql_user').value = cfg?.connector?.user || '';
      $('#mysql_pass').value = cfg?.connector?.pass || '';
      $('#mysql_port').value = cfg?.connector?.port || 3306;
      $('#mysql_sql').value = cfg?.request?.sql || '';
      $('#mysql_bind').value = JSON.stringify(cfg?.request?.bind || [], null, 2);
    }
  }

  // mapping + transform
  $('#mapping_json').value = JSON.stringify(cfg?.mapping || {}, null, 2);
  $('#transform_enabled').value = (cfg?.transform?.enabled ? '1' : '0');
  $('#transform_code').value = cfg?.transform?.code || '';

  toggleCfgAreas();
}

async function saveDraft(){
  try{
    const payload = buildPayload();
    if (!payload.dataset_key || !payload.name) return alert('dataset_key e nome são obrigatórios.');
    const resp = await apiPost(API.save, payload);
    alert('Salvo! id=' + resp.id);
    // se era novo, recarrega com ?id=
    if (!ID) window.location = 'ds_editor.php?id=' + resp.id;
  }catch(e){
    alert('Erro: ' + e.message);
  }
}

async function publish(){
  try{
    if (!ID) return alert('Salve antes de publicar.');
    const notes = prompt('Notas da publicação (opcional):', '');
    const resp = await apiPost(API.publish, { id: ID, notes: notes || '' });
    alert('Publicado! v' + resp.version_num);
  }catch(e){
    alert('Erro: ' + e.message);
  }
}

$('#btnSave').onclick = saveDraft;
$('#btnPublish').onclick = publish;

$('#btnBuild').onclick = ()=>{
  try{
    const payload = buildPayload();
    $('#preview').textContent = JSON.stringify(payload.config, null, 2);
  }catch(e){
    $('#preview').textContent = JSON.stringify({ok:false,error:e.message}, null, 2);
  }
};

$('#btnCopy').onclick = async ()=>{
  await safeCopy($('#preview').textContent || '');
  alert('Copiado!');
};

load();
</script>
</body>
</html>
