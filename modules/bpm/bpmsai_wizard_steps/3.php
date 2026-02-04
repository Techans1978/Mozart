<?php

require_once __DIR__ . '/../../../config.php';
require_once ROOT_PATH . '/system/config/autenticacao.php';
require_once ROOT_PATH . '/system/config/connect.php';
proteger_pagina();

// bpmsai-wizard.php (no topo, após connect.php)
if (session_status()===PHP_SESSION_NONE) session_start();

$flow_id = (int)($_GET['flow_id'] ?? 0);
$step    = (int)($_GET['step'] ?? 1);

if ($flow_id > 0) {
  // cria/atualiza estado base na sessão
  $st = $_SESSION['bpmsai_wizard'] ?? [];
  $currentFlowId = (int)($st['flow_id'] ?? 0);

  // se estou abrindo outro flow, recarrega tudo
  if ($currentFlowId !== $flow_id) {
    $st = [
      'flow_id' => $flow_id,
      'flow_version_id' => null,
      'nome' => '',
      'codigo' => '',
      'categoria_id' => null,
      'descricao' => '',
      'original_text' => '',
      'actors_dict' => '',
      'steps' => [],
      'transitions' => [],
      'default_form_slug' => '',
      'formsByStep' => []
    ];

    // 1) carrega metadados do flow
    $stmt = $conn->prepare("SELECT id, code, name, description, category_id, active_version_id FROM bpmsai_flow WHERE id=? LIMIT 1");
    $stmt->bind_param("i", $flow_id);
    $stmt->execute();
    $flow = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($flow) {
      $st['codigo'] = (string)($flow['code'] ?? '');
      $st['nome']   = (string)($flow['name'] ?? '');
      $st['descricao'] = (string)($flow['description'] ?? '');
      $st['categoria_id'] = isset($flow['category_id']) ? (int)$flow['category_id'] : null;

      // 2) carrega versão preferindo draft; senão a ativa; senão última
      $stmt = $conn->prepare("
        SELECT id, json_def, original_text
        FROM bpmsai_flow_version
        WHERE flow_id=?
        ORDER BY (status='draft') DESC, (id=?) DESC, id DESC
        LIMIT 1
      ");
      $activeVid = (int)($flow['active_version_id'] ?? 0);
      $stmt->bind_param("ii", $flow_id, $activeVid);
      $stmt->execute();
      $ver = $stmt->get_result()->fetch_assoc();
      $stmt->close();

      if ($ver && !empty($ver['json_def'])) {
        $st['flow_version_id'] = (int)$ver['id'];

        $def = json_decode($ver['json_def'], true);
        if (is_array($def)) {
          $st['steps'] = $def['steps'] ?? [];
          $st['transitions'] = $def['transitions'] ?? [];
          $st['default_form_slug'] = $def['forms']['default_form']['slug'] ?? '';
          $st['formsByStep'] = $def['forms']['by_step'] ?? [];

          $st['original_text'] = $def['language']['original_text'] ?? ($ver['original_text'] ?? '');
          $st['actors_dict']   = $def['language']['actors_dictionary_text'] ?? '';
        }
      }
    }

    $_SESSION['bpmsai_wizard'] = $st;
  }
}

// modules/bpm/bpmsai_wizard_steps/3.php  (VISUAL HELIX)
if (session_status()===PHP_SESSION_NONE) session_start();

$st = $_SESSION['bpmsai_wizard'] ?? [];
$steps = $st['steps'] ?? [];

// Se vier como JSON string, decodifica
if (is_string($steps) && trim($steps) !== '') {
  $tmp = json_decode($steps, true);
  if (is_array($tmp)) $steps = $tmp;
}

// Garante array
if (!is_array($steps)) $steps = [];

// Normaliza para array numérico (se vier associativo do banco)
if ($steps) {
  $keys = array_keys($steps);
  $isNumericSeq = ($keys === range(0, count($steps)-1));
  if (!$isNumericSeq) $steps = array_values($steps);
}

// Seed apenas se realmente vazio
if (!$steps) {
  $steps = [
    [
      'id'=>'abertura',
      'name'=>'Abertura',
      'type'=>'human',
      'description'=>'Analista preenche dados e anexa documentos.',
      'assignees'=>[['type'=>'perfil','key'=>0,'label'=>'(definir)']],
      'observers'=>[],
      'actions'=>[
        'approve'=>['label'=>'Enviar'],
        'reject'=>['label'=>'Reprovar'],
        'revise'=>['label'=>'Pedir correção']
      ]
    ],
    [
      'id'=>'gerente',
      'name'=>'Gerente',
      'type'=>'human',
      'description'=>'Gerente analisa e decide.',
      'assignees'=>[['type'=>'perfil','key'=>0,'label'=>'(definir)']],
      'observers'=>[],
      'actions'=>[
        'approve'=>['label'=>'Aprovar'],
        'reject'=>['label'=>'Reprovar'],
        'revise'=>['label'=>'Pedir correção']
      ]
    ],
  ];
}

/**
 * 2) Se ainda estiver vazio e for edição/fluxo existente:
 *    tenta carregar do json_def (draft/published)
 */

  if ($stt && $stt->execute()) {
    $row = $stt->get_result()->fetch_assoc();
    $stt->close();

    $def = [];
    if (!empty($row['json_def'])) {
      $tmp = json_decode($row['json_def'], true);
      if (is_array($tmp)) $def = $tmp;
    }

    $loadedSteps = $def['steps'] ?? [];
    if (is_string($loadedSteps)) {
      $tmp = json_decode($loadedSteps, true);
      $loadedSteps = is_array($tmp) ? $tmp : [];
    }

    if (is_array($loadedSteps) && count($loadedSteps)) {
      $steps = $loadedSteps;
      $st['steps'] = $steps;
      $_SESSION['bpmsai_wizard'] = $st; // 👈 garante persistência
    }
  }
}

/**
 * 3) Normaliza para array numérico (se vier associativo)
 */
if (!is_array($steps)) $steps = [];
if ($steps) {
  $keys = array_keys($steps);
  $isNumericSeq = ($keys === range(0, count($steps)-1));
  if (!$isNumericSeq) $steps = array_values($steps);
}

// A partir daqui: seu seed de 2 etapas continua igual (só roda se ainda estiver vazio)

// se vazio, inicia com 2 etapas padrão
if (!$steps) {
  $steps = [
    [
      'id'=>'abertura',
      'name'=>'Abertura',
      'type'=>'human',
      'description'=>'Analista preenche dados e anexa documentos.',
      'assignees'=>[['type'=>'perfil','key'=>0,'label'=>'(definir)']],
      'observers'=>[],
      'actions'=>[
        'approve'=>['label'=>'Enviar'],
        'reject'=>['label'=>'Reprovar'],
        'revise'=>['label'=>'Pedir correção']
      ]
    ],
    [
      'id'=>'gerente',
      'name'=>'Gerente',
      'type'=>'human',
      'description'=>'Gerente analisa e decide.',
      'assignees'=>[['type'=>'perfil','key'=>0,'label'=>'(definir)']],
      'observers'=>[],
      'actions'=>[
        'approve'=>['label'=>'Aprovar'],
        'reject'=>['label'=>'Reprovar'],
        'revise'=>['label'=>'Pedir correção']
      ]
    ],
  ];
}
?>

<style>
/* Helix-like cards (lúdico e claro) */
.bpmsai-canvas { display:flex; flex-direction:column; gap:14px; }
.bpmsai-card { border:1px solid #e6e6e6; border-radius:10px; background:#fff; box-shadow:0 1px 2px rgba(0,0,0,.04); }
.bpmsai-card-header { padding:12px 14px; border-bottom:1px solid #f0f0f0; display:flex; align-items:center; justify-content:space-between; gap:10px; }
.bpmsai-title { display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
.bpmsai-badge { display:inline-flex; align-items:center; gap:6px; padding:3px 8px; border-radius:999px; background:#f7f7f7; font-size:12px; }
.bpmsai-index { width:26px; height:26px; border-radius:999px; background:#337ab7; color:#fff; display:inline-flex; align-items:center; justify-content:center; font-weight:700; font-size:12px; }
.bpmsai-card-body { padding:14px; }
.bpmsai-grid { display:grid; grid-template-columns: 1fr 1fr; gap:12px; }
@media (max-width: 992px){ .bpmsai-grid { grid-template-columns:1fr; } }

.bpmsai-chips { display:flex; flex-wrap:wrap; gap:6px; padding:8px; border:1px dashed #ddd; border-radius:8px; min-height:44px; }
.bpmsai-chip { display:inline-flex; align-items:center; gap:6px; padding:6px 10px; border-radius:999px; background:#f5f8ff; border:1px solid #dbe6ff; font-size:12px; }
.bpmsai-chip .x { cursor:pointer; opacity:.7; }
.bpmsai-chip .x:hover { opacity:1; }
.bpmsai-chip.ob { background:#f7fff6; border-color:#dff3db; }
.bpmsai-mini { font-size:12px; color:#777; margin-top:6px; }

.bpmsai-actions-row { display:flex; gap:8px; flex-wrap:wrap; margin-top:8px; }
.bpmsai-actions-row .labelbox { display:flex; align-items:center; gap:8px; }
.bpmsai-actions-row input { width:160px; }

.bpmsai-footerbar { display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap; margin-top:12px; }
.bpmsai-addbtn { border:1px dashed #cbd5e1; background:#fbfbfb; border-radius:10px; padding:14px; text-align:center; cursor:pointer; }
.bpmsai-addbtn:hover { background:#f7f7f7; }
.bpmsai-danger { color:#b00020; }
.bpmsai-muted { color:#777; }
</style>

<form method="post" action="/modules/bpm/bpmsai_wizard_steps/save.php?step=3" id="bpmsai_form_step3">
  <div class="bpmsai-canvas" id="bpmsai_canvas"></div>

  <div class="bpmsai-addbtn" id="bpmsai_add_step">
    <strong>+ Adicionar etapa</strong>
    <div class="bpmsai-mini">Crie etapas como blocos (sem desenhar BPM)</div>
  </div>

  <textarea name="steps_json" id="steps_json" class="hidden" style="display:none;"></textarea>

  <div class="bpmsai-footerbar">
    <div class="bpmsai-mini">
      Dica: use nomes curtos. “Correção” depois você escolhe o destino no passo 4.
    </div>
    <div class="text-right">
      <a class="btn btn-default" href="/modules/bpm/bpmsai-wizard.php?step=2">Voltar</a>
      <button class="btn btn-primary" type="submit">Salvar e avançar</button>
    </div>
  </div>
</form>

<!-- Modal simples (Bootstrap) -->
<div class="modal fade" id="bpmsaiPicker" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Fechar"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title">Adicionar participante</h4>
      </div>
      <div class="modal-body">
        <div class="form-group">
          <label>Tipo</label>
          <select class="form-control" id="pick_type">
            <option value="user">Usuário</option>
            <option value="group">Grupo</option>
            <option value="perfil">Perfil</option>
          </select>
        </div>

        <div class="form-group">
          <label>Buscar</label>
          <input class="form-control" id="pick_q" placeholder="Digite para buscar...">
          <div class="bpmsai-mini">Ex.: “Mayara”, “Gerentes”, “RH”.</div>
        </div>

        <div class="list-group" id="pick_results" style="max-height:240px; overflow:auto;"></div>
        <div class="bpmsai-mini bpmsai-muted">Clique em um resultado para adicionar.</div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-default" data-dismiss="modal">Fechar</button>
      </div>
    </div>
  </div>
</div>

<script>
(function(){
  let steps = <?php echo json_encode($steps, JSON_UNESCAPED_UNICODE); ?>;

  // Se vier como objeto (array associativo), converte pra array
  if (!Array.isArray(steps)) {
    steps = Object.values(steps || {});
  }


  function ensureDefaults(s){
    if(!s || typeof s!=='object') s = {};
    if(!s.type) s.type = 'human';

    if(!s.blocks || typeof s.blocks!=='object'){
      s.blocks = { use_description:true, use_form:false, use_bpmn_convert:false };
    } else {
      if(typeof s.blocks.use_description!=='boolean') s.blocks.use_description = true;
      if(typeof s.blocks.use_form!=='boolean') s.blocks.use_form = false;
      if(typeof s.blocks.use_bpmn_convert!=='boolean') s.blocks.use_bpmn_convert = false;
    }

    if(!s.form || typeof s.form!=='object'){
      s.form = { mode:'none', payload:'' };
    } else {
      if(!s.form.mode) s.form.mode = 'none';
      if(typeof s.form.payload!=='string') s.form.payload = '';
    }

    if(!s.integration || typeof s.integration!=='object'){
      s.integration = { connector_key:'', connector_label:'' };
    } else {
      if(typeof s.integration.connector_key!=='string') s.integration.connector_key = '';
      if(typeof s.integration.connector_label!=='string') s.integration.connector_label = '';
    }

    if(typeof s.static_text!=='string') s.static_text = '';

    if(!s.code || typeof s.code!=='object'){
      s.code = { lang:'js', body:'' };
    } else {
      if(!s.code.lang) s.code.lang = 'js';
      if(typeof s.code.body!=='string') s.code.body = '';
    }

    if(!Array.isArray(s.assignees)) s.assignees = [];
    if(!Array.isArray(s.observers)) s.observers = [];

    if(!s.actions || typeof s.actions!=='object'){
      s.actions = { approve:{label:'Aprovar'}, reject:{label:'Reprovar'}, revise:{label:'Pedir correção'} };
    } else {
      if(!s.actions.approve) s.actions.approve={label:'Aprovar'};
      if(!s.actions.reject)  s.actions.reject ={label:'Reprovar'};
      if(!s.actions.revise)  s.actions.revise ={label:'Pedir correção'};
    }

    if(typeof s.description!=='string') s.description = '';
    if(typeof s.id!=='string') s.id = '';
    if(typeof s.name!=='string') s.name = '';

    return s;
  }

  function slugify(v){
    return (v||'')
      .toString().trim().toLowerCase()
      .replace(/[^\w\- ]+/g,'')
      .replace(/\s+/g,'_')
      .replace(/\_+/g,'_')
      .slice(0,40) || 'etapa';
  }

  function uniqId(base){
    let id = slugify(base);
    let i = 1;
    const exists = (x)=>steps.some(s=>s.id===x);
    while(exists(id)){ id = slugify(base)+'_'+(i++); }
    return id;
  }

  function escapeHtml(str){
    return (str||'').toString()
      .replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;')
      .replaceAll('"','&quot;').replaceAll("'","&#039;");
  }

  function chipHtml(p, mode, stepIdx, pIdx){
    const cls = mode==='observers' ? 'bpmsai-chip ob' : 'bpmsai-chip';
    const tLabel = (p.type==='user'?'Usuário':(p.type==='group'?'Grupo':'Perfil'));
    return `
      <span class="${cls}">
        <strong>${tLabel}:</strong> ${escapeHtml(p.label||'')}
        <span class="bpmsai-muted">#${p.key||0}</span>
        <span class="x" title="Remover" data-step="${stepIdx}" data-mode="${mode}" data-idx="${pIdx}">&times;</span>
      </span>`;
  }

  function isTotallyEmpty(s){
    const emptyName   = !s.name || !s.name.trim();
    const emptyId     = !s.id   || !s.id.trim();
    const emptyDesc   = !s.description || !s.description.trim();
    const emptyStatic = !s.static_text || !s.static_text.trim();
    const emptyCode   = !s.code || !s.code.body || !s.code.body.trim();
    const emptyForm   = !s.form || !s.form.payload || !s.form.payload.trim();
    const emptyAss    = !Array.isArray(s.assignees) || s.assignees.length===0;
    const emptyObs    = !Array.isArray(s.observers) || s.observers.length===0;
    return (emptyName && emptyId && emptyDesc && emptyStatic && emptyCode && emptyForm && emptyAss && emptyObs);
  }

  function syncHidden(){
    const clean = steps
      .map(s=>{
        const c = JSON.parse(JSON.stringify(ensureDefaults(s)));
        (c.assignees||[]).forEach(p=>{
          if(p.label==='(definir)' && (!p.key || p.key===0)) p.label='';
        });
        return c;
      })
      .filter(s=>!isTotallyEmpty(s));

    document.getElementById('steps_json').value = JSON.stringify(clean, null, 2);
  }

  function render(){
    const canvas = document.getElementById('bpmsai_canvas');
    canvas.innerHTML = '';

    steps = steps.map(ensureDefaults);

    steps.forEach((s, i)=>{
      const idx = i+1;

      const ass = (s.assignees||[]).map((p,pi)=>chipHtml(p,'assignees',i,pi)).join('') ||
        `<span class="bpmsai-mini bpmsai-muted">Nenhum responsável. Clique em “+” para adicionar.</span>`;
      const obs = (s.observers||[]).map((p,pi)=>chipHtml(p,'observers',i,pi)).join('') ||
        `<span class="bpmsai-mini bpmsai-muted">Sem observadores.</span>`;

      const card = document.createElement('div');
      card.className = 'bpmsai-card';
      card.innerHTML = `
        <div class="bpmsai-card-header">
          <div class="bpmsai-title">
            <span class="bpmsai-index">${idx}</span>

            <div style="min-width:240px;">
              <div class="form-group" style="margin:0">
                <input class="form-control input-sm" data-k="name" data-i="${i}" value="${escapeHtml(s.name||'')}" placeholder="Nome da etapa (ex: Gerente)">
              </div>
            </div>

            <span class="bpmsai-badge">
              ID:
              <input class="form-control input-sm" style="width:170px" data-k="id" data-i="${i}"
                value="${escapeHtml(s.id||'')}" placeholder="id (ex: gerente)">
            </span>

            <span class="bpmsai-badge">
              Tipo:
              <select class="form-control input-sm" style="width:170px" data-k="type" data-i="${i}">
                <option value="human" ${s.type==='human'?'selected':''}>Humana</option>
                <option value="integration" ${s.type==='integration'?'selected':''}>Integração</option>
                <option value="text" ${s.type==='text'?'selected':''}>Texto</option>
                <option value="code" ${s.type==='code'?'selected':''}>Código</option>
              </select>
            </span>
          </div>

          <div>
            <button type="button" class="btn btn-xs btn-danger" data-del="${i}" title="Excluir etapa">
              <i class="fa fa-trash"></i> Excluir
            </button>
          </div>
        </div>

        <div class="bpmsai-card-body">
          <div class="form-group">
            <label>Fontes / Conteúdo desta etapa</label>

            <div class="bpmsai-actions-row" style="margin-top:6px">
              <label style="display:inline-flex;align-items:center;gap:6px;margin:0">
                <input type="checkbox" data-k="blk_desc" data-i="${i}" ${s.blocks.use_description?'checked':''}>
                Descrição (IA)
              </label>

              <label style="display:inline-flex;align-items:center;gap:6px;margin:0">
                <input type="checkbox" data-k="blk_form" data-i="${i}" ${s.blocks.use_form?'checked':''}>
                Formulário
              </label>

              <label style="display:inline-flex;align-items:center;gap:6px;margin:0">
                <input type="checkbox" data-k="blk_bpmn" data-i="${i}" ${s.blocks.use_bpmn_convert?'checked':''}>
                Ler desenho de BPM e converter <span class="bpmsai-muted">(em breve)</span>
              </label>
            </div>

            <div class="form-group" style="margin-top:10px; ${s.blocks.use_description?'':'display:none;'}">
              <label>Descrição (IA)</label>
              <textarea class="form-control" rows="3" data-k="description" data-i="${i}"
                placeholder="Descreva em 1–3 frases o que acontece nesta etapa...">${escapeHtml(s.description||'')}</textarea>
            </div>

            <div class="form-group" style="margin-top:10px; ${s.blocks.use_form?'':'display:none;'}">
              <label>Formulário</label>
              <div class="bpmsai-grid" style="grid-template-columns: 220px 1fr;">
                <div>
                  <select class="form-control" data-k="form_mode" data-i="${i}">
                    <option value="none" ${s.form.mode==='none'?'selected':''}>Nenhum</option>
                    <option value="html" ${s.form.mode==='html'?'selected':''}>Carregar via HTML</option>
                    <option value="xml" ${s.form.mode==='xml'?'selected':''}>Carregar via XML</option>
                    <option value="forms" ${s.form.mode==='forms'?'selected':''}>Ler do módulo Forms</option>
                  </select>
                  <div class="bpmsai-mini bpmsai-muted">Você escolhe a origem do formulário.</div>
                </div>

                <div>
                  <textarea class="form-control" rows="4" data-k="form_payload" data-i="${i}"
                    placeholder="Se HTML/XML: cole aqui. Se Forms: informe o código/slug do formulário...">${escapeHtml(s.form.payload||'')}</textarea>
                  <div class="bpmsai-mini bpmsai-muted">
                    HTML/XML: cole o conteúdo. Forms: coloque o identificador do formulário (ex.: slug).
                  </div>
                </div>
              </div>
            </div>

            <div class="form-group" style="margin-top:10px; ${s.type==='integration'?'':'display:none;'}">
              <label>Conectores (em breve)</label>
              <div class="bpmsai-mini bpmsai-muted">
                Aqui você vai escolher um conector criado no módulo de Conectores (ainda vamos criar).
              </div>
              <div class="bpmsai-grid" style="grid-template-columns: 1fr 1fr;">
                <div>
                  <input class="form-control" data-k="connector_label" data-i="${i}" value="${escapeHtml(s.integration.connector_label||'')}"
                    placeholder="Nome do conector (ex.: Consinco - Contabilização)">
                </div>
                <div>
                  <input class="form-control" data-k="connector_key" data-i="${i}" value="${escapeHtml(s.integration.connector_key||'')}"
                    placeholder="Chave do conector (ex.: consinco_contabil)">
                </div>
              </div>
            </div>

            <div class="form-group" style="margin-top:10px; ${s.type==='text'?'':'display:none;'}">
              <label>Texto (instruções / observações)</label>
              <textarea class="form-control" rows="4" data-k="static_text" data-i="${i}"
                placeholder="Digite um texto que ficará registrado nesta etapa...">${escapeHtml(s.static_text||'')}</textarea>
            </div>

            <div class="form-group" style="margin-top:10px; ${s.type==='code'?'':'display:none;'}">
              <label>Código (auto-exec em breve)</label>
              <div class="bpmsai-grid" style="grid-template-columns: 220px 1fr;">
                <div>
                  <select class="form-control" data-k="code_lang" data-i="${i}">
                    <option value="js" ${s.code.lang==='js'?'selected':''}>JavaScript</option>
                    <option value="php" ${s.code.lang==='php'?'selected':''}>PHP</option>
                    <option value="other" ${s.code.lang==='other'?'selected':''}>Outro</option>
                  </select>
                  <div class="bpmsai-mini bpmsai-muted">Execução automática será implementada depois.</div>
                </div>
                <div>
                  <textarea class="form-control" rows="6" data-k="code_body" data-i="${i}"
                    placeholder="Cole aqui seu código...">${escapeHtml(s.code.body||'')}</textarea>
                </div>
              </div>
            </div>
          </div>

          <div class="bpmsai-grid">
            <div>
              <label>Responsáveis (podem agir)</label>
              <div class="bpmsai-chips" id="ass_${i}">${ass}</div>
              <div class="bpmsai-actions-row">
                <button type="button" class="btn btn-xs btn-default" data-add="${i}" data-mode="assignees">+ Adicionar responsável</button>
              </div>
            </div>

            <div>
              <label>Observadores (somente visualizam)</label>
              <div class="bpmsai-chips" id="obs_${i}">${obs}</div>
              <div class="bpmsai-actions-row">
                <button type="button" class="btn btn-xs btn-default" data-add="${i}" data-mode="observers">+ Adicionar observador</button>
              </div>
            </div>
          </div>

          <hr>

          <label>Ações (labels)</label>
          <div class="bpmsai-actions-row">
            <div class="labelbox">
              <span class="bpmsai-badge">Aprovar</span>
              <input class="form-control input-sm" data-k="action_approve" data-i="${i}" value="${escapeHtml(s.actions.approve.label||'Aprovar')}">
            </div>
            <div class="labelbox">
              <span class="bpmsai-badge">Reprovar</span>
              <input class="form-control input-sm" data-k="action_reject" data-i="${i}" value="${escapeHtml(s.actions.reject.label||'Reprovar')}">
            </div>
            <div class="labelbox">
              <span class="bpmsai-badge">Correção</span>
              <input class="form-control input-sm" data-k="action_revise" data-i="${i}" value="${escapeHtml(s.actions.revise.label||'Pedir correção')}">
            </div>
          </div>

          <div class="bpmsai-mini">
            “Destinos” (aprovar/correção) você define no <b>passo 4</b>.
            “Reprovar” finaliza por padrão.
          </div>
        </div>
      `;
      canvas.appendChild(card);
    });

    syncHidden();
  }

  function addStep(){
    const baseName = 'Nova Etapa';
    const id = uniqId(baseName);
    steps.push({
      id, name: baseName, type:'human',
      blocks:{ use_description:true, use_form:false, use_bpmn_convert:false },
      description:'',
      form:{ mode:'none', payload:'' },
      integration:{ connector_key:'', connector_label:'' },
      static_text:'',
      code:{ lang:'js', body:'' },
      assignees:[],
      observers:[],
      actions:{ approve:{label:'Aprovar'}, reject:{label:'Reprovar'}, revise:{label:'Pedir correção'} }
    });
    render();
    window.scrollTo({ top: document.body.scrollHeight, behavior: 'smooth' });
  }

  // picker modal state
  let pickStepIndex = null;
  let pickMode = null;
  function openPicker(stepIndex, mode){
    pickStepIndex = stepIndex;
    pickMode = mode;
    document.getElementById('pick_q').value = '';
    document.getElementById('pick_results').innerHTML = '';
    $('#bpmsaiPicker').modal('show');
    setTimeout(()=>document.getElementById('pick_q').focus(), 200);
  }

  async function searchPicker(){
    const type = document.getElementById('pick_type').value;
    const q = document.getElementById('pick_q').value || '';
    const resBox = document.getElementById('pick_results');
    resBox.innerHTML = '<div class="bpmsai-mini bpmsai-muted">Buscando...</div>';

    const url = '/modules/bpm/api/bpmsai_assignee_search.php?type='+encodeURIComponent(type)+'&q='+encodeURIComponent(q);
    const r = await fetch(url);
    const j = await r.json();
    if(!j || !j.ok){
      resBox.innerHTML = '<div class="bpmsai-mini bpmsai-danger">Falha na busca.</div>';
      return;
    }
    const items = j.items || [];
    if(!items.length){
      resBox.innerHTML = '<div class="bpmsai-mini bpmsai-muted">Nenhum resultado.</div>';
      return;
    }
    resBox.innerHTML = '';
    items.forEach(it=>{
      const a = document.createElement('a');
      a.href = '#';
      a.className = 'list-group-item';
      a.innerHTML = `<strong>${escapeHtml(it.label)}</strong>
        <div class="bpmsai-mini">${escapeHtml(it.type)} #${it.key} ${it.extra?('— '+escapeHtml(it.extra)):""}</div>`;
      a.addEventListener('click', (ev)=>{
        ev.preventDefault();
        addParticipant(it);
      });
      resBox.appendChild(a);
    });
  }

  function addParticipant(it){
    if(pickStepIndex===null || pickMode===null) return;
    const s = steps[pickStepIndex];
    if(!s) return;

    const arr = (pickMode==='observers') ? s.observers : s.assignees;
    if(arr.some(p=>p.type===it.type && String(p.key)===String(it.key))){
      $('#bpmsaiPicker').modal('hide');
      return;
    }
    arr.push({ type: it.type, key: it.key, label: it.label });
    $('#bpmsaiPicker').modal('hide');
    render();
  }

  // listeners
  document.getElementById('bpmsai_add_step').addEventListener('click', function(e){
    e.preventDefault(); e.stopPropagation();
    addStep();
  });

  document.addEventListener('click', function(e){
    const del = e.target.closest('[data-del]');
    if(del){
      const i = parseInt(del.getAttribute('data-del'),10);
      if(Number.isInteger(i) && steps[i]){
        if(confirm('Excluir esta etapa?')){
          steps.splice(i,1);
          render();
        }
      }
      return;
    }

    const add = e.target.closest('[data-add]');
    if(add){
      const i = parseInt(add.getAttribute('data-add'),10);
      const mode = add.getAttribute('data-mode');
      openPicker(i, mode);
      return;
    }

    const x = e.target.closest('.x[data-step][data-mode][data-idx]');
    if(x){
      const si = parseInt(x.getAttribute('data-step'),10);
      const mode = x.getAttribute('data-mode');
      const pi = parseInt(x.getAttribute('data-idx'),10);
      const s = steps[si];
      if(s){
        const arr = (mode==='observers') ? s.observers : s.assignees;
        arr.splice(pi,1);
        render();
      }
      return;
    }
  });

  function setByK(s, k, el){
    const v = (el.type==='checkbox') ? !!el.checked : el.value;

    if(k==='name'){ s.name = v; return 'maybe_id'; }
    if(k==='id'){ s.id = slugify(v); return; }
    if(k==='type'){ s.type = v; return 'rerender'; }

    if(k==='blk_desc'){ s.blocks.use_description = !!v; return 'rerender'; }
    if(k==='blk_form'){ s.blocks.use_form = !!v; return 'rerender'; }
    if(k==='blk_bpmn'){ s.blocks.use_bpmn_convert = !!v; return 'rerender'; }

    if(k==='description'){ s.description = v; return; }
    if(k==='form_mode'){ s.form.mode = v; return; }
    if(k==='form_payload'){ s.form.payload = v; return; }

    if(k==='connector_label'){ s.integration.connector_label = v; return; }
    if(k==='connector_key'){ s.integration.connector_key = v; return; }

    if(k==='static_text'){ s.static_text = v; return; }
    if(k==='code_lang'){ s.code.lang = v; return; }
    if(k==='code_body'){ s.code.body = v; return; }

    if(k==='action_approve'){ s.actions.approve.label = v; return; }
    if(k==='action_reject'){ s.actions.reject.label = v; return; }
    if(k==='action_revise'){ s.actions.revise.label = v; return; }
  }

  document.addEventListener('input', function(e){
    const el = e.target;
    if(!el || !el.matches('[data-k][data-i]')) return;

    const i = parseInt(el.getAttribute('data-i'),10);
    const k = el.getAttribute('data-k');
    const s = steps[i];
    if(!s) return;

    const r = setByK(s, k, el);

    if(k==='name'){
      if(!s.id || s.id.trim()===''){
        s.id = uniqId(el.value);
        render();
        return;
      }
    }

    if(r==='rerender'){
      render(); return;
    }

    syncHidden();
  });

  document.addEventListener('change', function(e){
    const el = e.target;
    if(!el || !el.matches('[data-k][data-i]')) return;

    const i = parseInt(el.getAttribute('data-i'),10);
    const k = el.getAttribute('data-k');
    const s = steps[i];
    if(!s) return;

    const r = setByK(s, k, el);
    if(r==='rerender'){ render(); return; }
    syncHidden();
  });

  document.getElementById('pick_type').addEventListener('change', searchPicker);
  document.getElementById('pick_q').addEventListener('input', function(){
    clearTimeout(window.__bpmsai_t);
    window.__bpmsai_t = setTimeout(searchPicker, 250);
  });
  $('#bpmsaiPicker').on('shown.bs.modal', function(){ searchPicker(); });

  document.getElementById('bpmsai_form_step3').addEventListener('submit', function(ev){
    const ids = new Set();
    for(const s0 of steps.map(ensureDefaults)){
      if(isTotallyEmpty(s0)) continue; // não valida lixo
      if(!s0.name || !s0.name.trim()){ alert('Uma etapa está sem nome.'); ev.preventDefault(); return; }
      if(!s0.id || !s0.id.trim()){ alert('Uma etapa está sem ID.'); ev.preventDefault(); return; }
      if(ids.has(s0.id)){ alert('ID repetido: '+s0.id); ev.preventDefault(); return; }
      ids.add(s0.id);
      if(!Array.isArray(s0.assignees) || s0.assignees.length===0){
        if(!confirm('Há etapa sem responsável. Deseja salvar mesmo assim?')){ ev.preventDefault(); return; }
      }
    }
    syncHidden();
  });

  render();
})();
</script>
