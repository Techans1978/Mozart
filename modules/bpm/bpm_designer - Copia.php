<?php
// modules/bpm/bpm_designer.php
// Mozart BPM — Modeler com Properties + Element Templates (CDN + fallback local)

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../config.php';
require_once ROOT_PATH . '/system/config/autenticacao.php';
require_once ROOT_PATH . '/system/config/connect.php';

// Abre <html><head>...<body>
include_once ROOT_PATH . 'system/includes/head.php';

// Inclui dependencias BPM
include_once ROOT_PATH . 'modules/bpm/includes/content_header.php';
include_once ROOT_PATH . 'modules/bpm/includes/content_style.php';

// (se o seu navbar ficar dentro do head/footer, não precisa incluir aqui)
include_once ROOT_PATH . 'system/includes/navbar.php';
?>

<!-- ===== Estilos locais desta tela ===== -->
<style>
  :root { --toolbar-h:56px; --sidebar-w:360px; --gap:10px; }
  #page-wrapper { background:#f6f7f9; }
  .shell { display:flex; flex-direction:column; height: calc(100vh - 70px); }
  .toolbar {
    height:var(--toolbar-h); display:flex; gap:8px; align-items:center; padding:8px 12px;
    background:#fff; border:1px solid #e5e7eb; border-radius:10px; margin-bottom:10px;
  }
  .toolbar h2 { font-size:16px; margin:0 12px 0 0; font-weight:600; color:#111827; }
  .toolbar .spacer { flex:1; }
  .btn {
    border:1px solid #d1d5db; background:#fff; padding:8px 12px; border-radius:10px; cursor:pointer;
    transition:.15s; font-weight:600;
  }
  .btn:hover { background:#f3f4f6; }
  .btn.primary { background:#111827; color:#fff; border-color:#111827; }
  .btn.primary:hover { background:#0b1220; }
  input[type="file"] { display:none; }

  .work { display:flex; gap:var(--gap); height: calc(100% - var(--toolbar-h) - 10px); }
  #canvas {
    flex:1; background:#fff; border:1px solid #e5e7eb; border-radius:12px; overflow:hidden; min-height:520px;
  }
  #properties {
    width:var(--sidebar-w); background:#fff; border:1px solid #e5e7eb; border-radius:12px; overflow:auto; min-height:520px;
  }

  /* ===== Painel Mozart (cards colapsáveis) ===== */
  .mozart-panel {
    font-size:13px;
    padding:10px;
  }
  .moz-title {
    font-size:14px;
    font-weight:600;
    margin:0 0 2px;
    color:#111827;
  }
  .moz-subtitle {
    font-size:11px;
    color:#6b7280;
  }
  .moz-card {
    border:1px solid #e5e7eb;
    border-radius:8px;
    margin-top:8px;
    background:#fff;
    overflow:hidden;
  }
  .moz-card-header {
    width:100%;
    text-align:left;
    border:0;
    background:#f9fafb;
    padding:6px 10px;
    display:flex;
    align-items:center;
    justify-content:space-between;
    cursor:pointer;
    font-size:12px;
    font-weight:600;
    color:#111827;
  }
  .moz-card-header:hover {
    background:#f3f4f6;
  }
  .moz-card-body {
    padding:8px 10px 10px;
    border-top:1px solid #e5e7eb;
  }
  .moz-card.collapsed .moz-card-body {
    display:none;
  }
  .moz-chevron {
    font-size:11px;
    color:#6b7280;
  }

  .moz-field,
  .mozart-field {
    margin-bottom:6px;
  }
  .moz-field label,
  .mozart-field label {
    display:block;
    font-size:11px;
    color:#4b5563;
    margin-bottom:2px;
  }
  .moz-field input,
  .moz-field select,
  .moz-field textarea,
  .mozart-field input,
  .mozart-field select,
  .mozart-field textarea {
    width:100%;
    font-size:12px;
    padding:4px 6px;
    border-radius:4px;
    border:1px solid #d1d5db;
  }
  .moz-row-2 {
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:10px;
  }
  .moz-row-2 label {
    display:block;
  }

  .moz-section-title {
    font-size:12px;
    font-weight:600;
    margin:4px 0 4px;
  }

  .btn.btn-xs {
    padding:4px 8px;
    font-size:11px;
    border-radius:6px;
  }
</style>

<!-- Page Content -->
<div id="page-wrapper">
  <div class="container-fluid">
    <div class="row"><div class="col-lg-12"><h1 class="page-header"><?= APP_NAME ?></h1></div></div>

    <div class="row">
      <div class="col-lg-12">
<!-- Top Content -->

  <div class="shell">
    <div class="toolbar">
      <h2>Mozart BPM — Designer</h2>

      <button class="btn" id="btnNew">Novo</button>

      <input type="file" id="fileOpen" accept=".bpmn,.xml" />
      <button class="btn" id="btnOpen" title="Ctrl+O">Abrir</button>

      <button class="btn" id="btnSave"    title="Ctrl+S">Salvar</button>
      <button class="btn" id="btnSaveAs"  title="Ctrl+Shift+S">Salvar como…</button>

      <button class="btn" id="btnExportXML">Baixar XML</button>
      <button class="btn" id="btnExportSVG">Baixar SVG</button>

      <div class="spacer"></div>

      <label for="tplSelect" class="mb-0" style="font-size:12px; color:#6b7280;">Template</label>
      <select id="tplSelect" class="form-control" style="min-width:200px; margin:0 6px;">
        <option value="">— selecionar —</option>
      </select>
      <button class="btn" id="btnApplyTpl" title="Aplica o template no elemento selecionado">Aplicar</button>

      <button class="btn primary" id="btnPublish">Publicar</button>
    </div>

    <div class="work">
      <div id="canvas"></div>
      <div id="properties"></div>
    </div>
  </div>

  <!-- Fim Content -->
        </div>
    </div>
  </div>
</div>

<?php
// carrega seus scripts globais + Camunda JS (inserido no code_footer.php)
include_once ROOT_PATH . 'system/includes/code_footer.php';

// Inclui dependencias BPM (carrega Camunda)
include_once ROOT_PATH . 'modules/bpm/includes/content_footer.php';
?>

<script>
(function () {
  const $ = (sel, ctx=document) => ctx.querySelector(sel);

  // ================== MOZART MODDLE (namespace mozart:*) ==================
  const MOZART_MODDLE = {
    name: "Mozart",
    uri: "http://mozart.superabc.com.br/schema/bpmn",
    prefix: "mozart",
    xml: { tagAlias: "lowerCase" },
    types: [
      {
        // ainda vamos usar para UserTask depois
        name: "MozartUserTaskProps",
        extends: [ "bpmn:UserTask" ],
        properties: [
          { name: "name",            isAttr: true, type: "String" },
          { name: "description",     isAttr: true, type: "String" },
          { name: "category",        isAttr: true, type: "String" },
          { name: "assignmentType",  isAttr: true, type: "String" },
          { name: "assignmentValue", isAttr: true, type: "String" },
          { name: "executionMode",   isAttr: true, type: "String" },
          { name: "formId",          isAttr: true, type: "String" },
          { name: "formMode",        isAttr: true, type: "String" },
          { name: "buttons",         isAttr: true, type: "String" },
          { name: "buttonMap",       isAttr: true, type: "String" },
          { name: "slaHours",        isAttr: true, type: "String" },
          { name: "slaEscalateTo",   isAttr: true, type: "String" },
          { name: "visibleTo",       isAttr: true, type: "String" },
          { name: "reopenAllowed",   isAttr: true, type: "String" },
          { name: "onComplete",      isAttr: true, type: "String" }
        ]
      }
    ]
  };

  // ================== Utilidades simples ==================
  const saveAs = (blob, filename) => {
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    setTimeout(()=> { URL.revokeObjectURL(a.href); a.remove(); }, 1000);
  };

  const readFile = (file) => new Promise((res, rej) => {
    const fr = new FileReader();
    fr.onload = () => res(String(fr.result));
    fr.onerror = rej;
    fr.readAsText(file);
  });

  function ensureCamundaLoaded() {
    return new Promise((resolve) => {
      function ok() {
        const Ctor =
          (window.CamundaPlatformModeler && (window.CamundaPlatformModeler.default || window.CamundaPlatformModeler)) ||
          (window.BpmnModeler && (window.BpmnModeler.default || window.BpmnModeler));
        resolve(Ctor || null);
      }
      setTimeout(ok, 0);
      setTimeout(() => {
        const Ctor =
          (window.CamundaPlatformModeler && (window.CamundaPlatformModeler.default || window.CamundaPlatformModeler)) ||
          (window.BpmnModeler && (window.BpmnModeler.default || window.BpmnModeler));
        if (!Ctor) {
          console.warn('CDN do camunda-bpmn-js falhou, tentando fallback local…');
          const css = document.createElement('link');
          css.rel = 'stylesheet';
          css.href = '<?= BASE_URL ?>/modules/bpm/vendor/camunda-bpmn-js@5/dist/assets/camunda-platform-modeler.css';
          document.head.appendChild(css);

          const s = document.createElement('script');
          s.src = '<?= BASE_URL ?>/modules/bpm/vendor/camunda-bpmn-js@5/dist/camunda-platform-modeler.development.js';
          s.onload = ok;
          s.onerror = () => resolve(null);
          document.body.appendChild(s);
        }
      }, 1000);
    });
  }

  // ================== Templates existentes (HTTP / Gateway / etc.) ==================
  const ELEMENT_TEMPLATES = [
    {
      name: "HTTP Task (GET)",
      id: "mozart.http.get.v1",
      appliesTo: [ "bpmn:ServiceTask" ],
      properties: [
        { label:"URL", type:"String", binding:{ type:"camunda:inputParameter", name:"url" }, constraints:{ notEmpty:true } },
        { label:"Query Params (json)", type:"Text", binding:{ type:"camunda:inputParameter", name:"query" } },
        { label:"Resultado → variável", type:"String", binding:{ type:"camunda:outputParameter", source:"${httpResult}", script:false, name:"resultVar" } }
      ]
    },
    {
      name: "Gateway de Decisão (expressão)",
      id: "mozart.gateway.expr.v1",
      appliesTo: [ "bpmn:ExclusiveGateway" ],
      properties: [
        { label:"Expressão (EL)", type:"String", binding:{ type:"bpmn:conditionExpression", language:"groovy" }, constraints:{ notEmpty:true } }
      ]
    }
  ];

  function populateTemplateChooser(templates) {
    const sel = $('#tplSelect');
    sel.innerHTML = '<option value="">— selecionar —</option>';
    templates.forEach(t => {
      const opt = document.createElement('option');
      opt.value = t.id;
      opt.textContent = t.name;
      sel.appendChild(opt);
    });
  }

  // ================== Boot do Modeler ==================
  let modeler = null;
  let currentFileName = 'diagram.bpmn';

  ensureCamundaLoaded().then((Ctor) => {
    if (!Ctor) {
      alert('❌ Não foi possível carregar o camunda-bpmn-js (CDN e fallback). Verifique modules/bpm/vendor/camunda-bpmn-js@5/dist/');
      return;
    }

    modeler = new Ctor({
      container: '#canvas',
      // vamos usar nosso painel custom, então não passamos o propertiesPanel nativo
      keyboard: { bindTo: document },
      moddleExtensions: {
        mozart: MOZART_MODDLE
      }
    });

    try {
      const templatesSvc = modeler.get('elementTemplates');
      if (templatesSvc?.set) templatesSvc.set(ELEMENT_TEMPLATES);
      populateTemplateChooser(ELEMENT_TEMPLATES);
    } catch (e) {
      console.warn('ElementTemplates service indisponível no bundle atual:', e);
    }

    modeler.on('import.done', (e) => {
      if (e?.warnings?.length) console.warn('Import warnings:', e.warnings);
      modeler.get('canvas').zoom('fit-viewport', 'auto');
      // após import, mostra painel de processo
      renderMozartPanel(modeler, null);
    });

    // Painel Mozart
    setupMozartPanel(modeler);

    // Toolbar / drag and drop / atalhos
    newDiagram();
    bindToolbar(modeler);
    bindDnD(modeler);
    bindShortcuts(modeler);
  });

  // ================== Toolbar / Ações ==================
  function bindToolbar(modeler) {
    bind('btnNew',      async () => { await newDiagram(); renderMozartPanel(modeler, null); });
    bind('btnOpen',     () => $('#fileOpen').click());
    bind('btnSave',     () => saveDiagram(false));
    bind('btnSaveAs',   () => saveDiagram(true));
    bind('btnExportXML',() => exportXML());
    bind('btnExportSVG',() => exportSVG());
    bind('btnApplyTpl', () => applySelectedTemplate());
    bind('btnPublish',  () => publish());
    $('#fileOpen').addEventListener('change', async (ev) => {
      const f = ev.target.files?.[0];
      if (!f) return;
      currentFileName = /\.(bpmn|xml)$/i.test(f.name) ? f.name : (f.name + '.bpmn');
      const xml = await readFile(f);
      await importXML(xml);
      ev.target.value = '';
    });
  }
  function bind(id, fn){ const el = document.getElementById(id); if (el) el.onclick = fn; }

  async function newDiagram() {
    if (!modeler) return;
    currentFileName = 'diagram.bpmn';
    await modeler.createDiagram();
    modeler.get('canvas').zoom('fit-viewport', 'auto');
  }

  async function importXML(xml) {
    try { await modeler.importXML(xml); }
    catch (err) { console.error(err); alert('Falha ao importar BPMN: ' + (err?.message || err)); }
  }

  async function saveDiagram(forceAs) {
    if (!modeler) return;
    const { xml } = await modeler.saveXML({ format:true });
    const name = forceAs ? prompt('Nome do arquivo .bpmn:', currentFileName) : currentFileName;
    if (!name) return;
    currentFileName = /\.bpmn$/i.test(name) ? name : (name + '.bpmn');
    saveAs(new Blob([xml], { type:'application/xml' }), currentFileName);
  }

  async function exportXML() {
    if (!modeler) return;
    const { xml } = await modeler.saveXML({ format:true });
    const base = currentFileName.replace(/\.(bpmn|xml)$/i,'') || 'diagram';
    saveAs(new Blob([xml], { type:'application/xml' }), base + '.bpmn');
  }

  async function exportSVG() {
    if (!modeler) return;
    const { svg } = await modeler.saveSVG();
    const base = currentFileName.replace(/\.(bpmn|xml)$/i,'') || 'diagram';
    saveAs(new Blob([svg], { type:'image/svg+xml' }), base + '.svg');
  }

  function bindDnD(modeler) {
    document.body.addEventListener('dragover', e => e.preventDefault());
    document.body.addEventListener('drop', async e => {
      e.preventDefault();
      const file = e.dataTransfer?.files?.[0];
      if (file && /\.(bpmn|xml)$/i.test(file.name)) {
        currentFileName = file.name;
        const xml = await readFile(file);
        await importXML(xml);
      }
    });
  }

  function bindShortcuts(modeler) {
    window.addEventListener('keydown', (e) => {
      const mod = e.ctrlKey || e.metaKey;
      if (mod && e.key.toLowerCase()==='s') {
        e.preventDefault();
        if (e.shiftKey) saveDiagram(true);
        else saveDiagram(false);
      }
      if (mod && e.key.toLowerCase()==='o') {
        e.preventDefault();
        $('#fileOpen').click();
      }
    });
  }

  function applySelectedTemplate() {
    const selId = $('#tplSelect').value;
    if (!selId) return alert('Selecione um template.');
    const sel = modeler.get('selection').get()[0];
    if (!sel) return alert('Selecione um elemento do diagrama.');
    try {
      const templatesSvc = modeler.get('elementTemplates');
      const tpl = ELEMENT_TEMPLATES.find(t => t.id === selId);
      if (!templatesSvc?.applyTemplate) throw new Error('Service indisponível');
      templatesSvc.applyTemplate(sel, tpl);
    } catch (e) {
      console.warn('Falhou ao aplicar template:', e);
      alert('Este bundle não expôs o serviço de Element Templates. Quando habilitarmos o pacote completo do Camunda isso some.');
    }
  }

  async function publish() {
    try {
      const { xml } = await modeler.saveXML({ format:true });
      console.log('XML pronto para publicar (%s bytes)', xml.length);
      alert('✔ XML preparado para publicar.\n\nQuando o endpoint estiver pronto, envio via fetch().');
    } catch (e) {
      console.error(e);
      alert('Erro ao publicar: ' + (e?.message || e));
    }
  }

  // ================== Painel Mozart ==================

  function setupMozartPanel(modeler) {
    const container = document.getElementById('properties');
    if (!container) return;

    container.innerHTML = `
      <div class="mozart-panel" id="mozart-panel">
        <h3 class="moz-title" id="mz-title">Processo · Configuração</h3>
        <small class="moz-subtitle" id="mz-subtitle">Configurações gerais do processo.</small>
        <div id="mz-body" style="margin-top:8px;"></div>
      </div>
    `;

    const selection = modeler.get('selection');
    const eventBus  = modeler.get('eventBus');

    eventBus.on('selection.changed', function(e) {
      const element = e.newSelection[0] || null;
      renderMozartPanel(modeler, element);
    });

    eventBus.on('element.changed', function(e) {
      const selected = selection.get()[0];
      if (selected && selected.id === e.element.id) {
        renderMozartPanel(modeler, selected);
      }
    });

    // primeira renderização: processo
    renderMozartPanel(modeler, null);
  }

  function tipoEvento(bo) {
    if (!bo) return { grupo: null, subtipo: null, label: 'Nada' };

    const type = bo.$type;
    const defs = bo.eventDefinitions || [];
    let subtipo = 'none';

    if (defs.length) {
      const t = defs[0].$type;
      switch (t) {
        case 'bpmn:MessageEventDefinition':     subtipo = 'mensagem';     break;
        case 'bpmn:TimerEventDefinition':       subtipo = 'timer';        break;
        case 'bpmn:SignalEventDefinition':      subtipo = 'sinal';        break;
        case 'bpmn:ConditionalEventDefinition': subtipo = 'condicional';  break;
        case 'bpmn:EscalationEventDefinition':  subtipo = 'escalacao';    break;
        case 'bpmn:ErrorEventDefinition':       subtipo = 'erro';         break;
        case 'bpmn:CompensateEventDefinition':  subtipo = 'compensacao';  break;
        case 'bpmn:LinkEventDefinition':        subtipo = 'link';         break;
        default: subtipo = 'none';
      }
    }

    if (type === 'bpmn:StartEvent') {
      return { grupo: 'startEvent', subtipo, label: 'Evento de Início' };
    }
    if (type === 'bpmn:EndEvent') {
      return { grupo: 'endEvent', subtipo, label: 'Evento de Fim' };
    }
    if (type === 'bpmn:IntermediateThrowEvent') {
      return { grupo: 'intermediateEvent', modo: 'throw', subtipo, label: 'Evento Intermediário (throw)' };
    }
    if (type === 'bpmn:IntermediateCatchEvent') {
      return { grupo: 'intermediateEvent', modo: 'catch', subtipo, label: 'Evento Intermediário (catch)' };
    }

    return { grupo: 'outro', subtipo: null, label: type || 'Elemento' };
  }

  function defaultMozartConfig(bo) {
    const t = tipoEvento(bo);
    if (!t.grupo) return null;

    if (t.grupo === 'startEvent') {
      return {
        tipo: 'startEvent',
        inicio: t.subtipo || 'none',
        geral: { nome: bo.name || '', id: bo.id || '' },
        documentacao: '',
        formulario: {
          idFormulario: '',
          iniciador: 'campo_usuario'
        },
        assincrono: { antes: false, depois: false },
        listenersExecucao: [],
        propriedadesExecucao: []
      };
    }

    if (t.grupo === 'intermediateEvent') {
      return {
        tipo: 'intermediateEvent',
        modo: t.modo || 'catch',
        subtipo: t.subtipo || 'none',
        geral: { nome: bo.name || '', id: bo.id || '' },
        documentacao: '',
        assincrono: { antes: false, depois: false },
        inputs: [],
        outputs: [],
        listenersExecucao: [],
        propriedadesExecucao: []
      };
    }

    if (t.grupo === 'endEvent') {
      return {
        tipo: 'endEvent',
        subtipo: t.subtipo || 'none',
        geral: { nome: bo.name || '', id: bo.id || '' },
        documentacao: '',
        assincrono: { antes: false, depois: false },
        inputs: [],
        listenersExecucao: [],
        propriedadesExecucao: []
      };
    }

    // fallback genérico
    return {
      tipo: 'generico',
      geral: { nome: bo.name || '', id: bo.id || '' }
    };
  }

  function getMozartConfigFromBO(bo) {
    if (!bo) return null;
    const attrs = bo.$attrs || {};
    let raw = attrs['mozart:config'] || attrs['mozartConfig'];
    if (!raw) {
      return defaultMozartConfig(bo);
    }
    try {
      return JSON.parse(raw);
    } catch (e) {
      console.warn('mozart:config inválido, recriando default.', e);
      return defaultMozartConfig(bo);
    }
  }

  function saveMozartConfig(modeler, element, cfg) {
    const modeling = modeler.get('modeling');
    const bo = element.businessObject;
    const attrs = bo.$attrs || (bo.$attrs = {});
    attrs['mozart:config'] = JSON.stringify(cfg || {}, null, 2);

    modeling.updateProperties(element, { 'mozart:config': attrs['mozart:config'] });
  }

  // inicializa comportamento de colapso dos cards
  function initMozartCollapsibles(root) {
    const cards = root.querySelectorAll('.moz-card');
    cards.forEach(card => {
      const header = card.querySelector('.moz-card-header');
      if (!header) return;
      header.onclick = () => {
        const isCollapsed = card.classList.contains('collapsed');
        card.classList.toggle('collapsed', !isCollapsed);
        card.classList.toggle('expanded', isCollapsed);
        const chev = header.querySelector('.moz-chevron');
        if (chev) chev.textContent = isCollapsed ? '▾' : '▸';
      };
    });
  }

  function bindGatewayTemplateBridge(rootEl) {
  const gwSel = rootEl.querySelector('#gw-template');
  if (!gwSel) return;

  const toolbarTpl = document.getElementById('tplSelect');
  const btnApply   = document.getElementById('btnApplyTpl');

  if (!toolbarTpl || !btnApply) return;

  // Mapeia seus templates "de painel" -> templates reais do ELEMENT_TEMPLATES (por id)
  // Ajuste aqui quando criar novos templates.
  const MAP = {
    expr:   'mozart.gateway.expr.v1',  // já existe no seu ELEMENT_TEMPLATES
    rules:  '',                       // futuro (ex.: DMN)
    default:''                        // futuro (ex.: “sempre segue fluxo padrão”)
  };

  gwSel.addEventListener('change', () => {
    const v = gwSel.value;
    const tplId = MAP[v] || '';

    if (!tplId) return; // por enquanto sem template real para essa opção

    // seleciona no toolbar
    toolbarTpl.value = tplId;

    // aplica no elemento selecionado
    btnApply.click();
  });
}


  // Renderiza painel
    // Renderiza painel
  function renderMozartPanel(modeler, element) {
    const panel = $('#mozart-panel');
    const title = $('#mz-title');
    const subtitle = $('#mz-subtitle');
    const body = $('#mz-body');

    if (!panel || !title || !subtitle || !body) return;

    // ======= MODO PROCESSO (sem elemento selecionado) =======
    if (!element) {
      // tenta pegar o root (process)
      let procName = 'Processo sem nome';
      let procId   = '(sem id)';
      try {
        const canvasRoot = modeler.get('canvas').getRootElement();
        if (canvasRoot && canvasRoot.businessObject) {
          const boRoot = canvasRoot.businessObject;
          procName = boRoot.name || procName;
          procId   = boRoot.id   || procId;
        }
      } catch (e) {}

      title.textContent = 'Processo · Configuração';
      subtitle.textContent = `Definições gerais — ID: ${procId}`;

      body.innerHTML = `
        <div class="moz-panel moz-panel-b">
          <!-- GERAL -->
          <section class="moz-card expanded">
            <button class="moz-card-header" type="button">
              <span>Geral</span>
              <span class="moz-chevron">▾</span>
            </button>
            <div class="moz-card-body">
              <div class="moz-field">
                <label>Nome do processo</label>
                <input type="text" id="proc_name" value="${procName.replace(/"/g,'&quot;')}">
              </div>
              <div class="moz-field">
                <label>Categoria</label>
                <input type="text" id="proc_category" placeholder="Ex: Compras, RH, TI...">
              </div>
              <div class="moz-field">
                <label>ID (gerado pelo sistema)</label>
                <input type="text" id="proc_key" value="${procId}" disabled>
              </div>
              <div class="moz-field">
                <label>Tag de versão</label>
                <input type="text" id="proc_version" disabled>
              </div>
              <small style="font-size:11px;color:#6b7280;">
                Última atualização em <span id="proc_last_update">—</span> por <span id="proc_last_user">—</span>
              </small>
              <hr>
              <div class="moz-field">
                <label>Documentação (BPMN documentation)</label>
                <textarea rows="3" id="proc_bpmn_doc"></textarea>
              </div>
              <div class="moz-field">
                <label>Descrição / instruções (Mozart)</label>
                <textarea rows="3" id="proc_description"></textarea>
              </div>
            </div>
          </section>

          <!-- HISTÓRICO -->
          <section class="moz-card collapsed">
            <button class="moz-card-header" type="button">
              <span>Histórico</span>
              <span class="moz-chevron">▸</span>
            </button>
            <div class="moz-card-body">
              <button type="button" class="btn btn-xs" id="proc_view_log">Ver log do processo</button>
              <button type="button" class="btn btn-xs" id="proc_clear_log">Limpar log</button>
              <p style="font-size:11px;color:#6b7280;margin-top:6px;">
                Logs de execução / publicação deste processo.
              </p>
            </div>
          </section>

          <!-- LISTA DE TAREFAS -->
          <section class="moz-card collapsed">
            <button class="moz-card-header" type="button">
              <span>Lista de tarefas</span>
              <span class="moz-chevron">▸</span>
            </button>
            <div class="moz-card-body">
              <label style="display:flex;align-items:center;gap:6px;">
                <input type="checkbox" id="proc_startable">
                <span>Processo inicializável (pode ser iniciado pela interface)</span>
              </label>
            </div>
          </section>

          <!-- SLA & Visibilidade (padrão) -->
          <section class="moz-card collapsed">
            <button class="moz-card-header" type="button">
              <span>SLA &amp; Visibilidade (padrão)</span>
              <span class="moz-chevron">▸</span>
            </button>
            <div class="moz-card-body moz-row-2">
              <div>
                <label>
                  Prazo padrão (horas)
                  <input type="number" min="0" id="proc_sla_hours">
                </label>
                <label>
                  Escalonar para
                  <input type="text" id="proc_sla_escalate_to" placeholder="grupo_gerentes, usuario_x...">
                </label>
              </div>
              <div>
                <label>
                  Perfis / grupos com acesso
                  <input type="text" id="proc_visible_to" placeholder="Ex: compras,diretoria">
                </label>
                <label>
                  Permitir reabrir?
                  <select id="proc_reopen_allowed">
                    <option value="0">Não</option>
                    <option value="1">Sim</option>
                  </select>
                </label>
                <label>
                  Abrir automaticamente a cada X dias
                  <input type="number" min="0" id="proc_auto_open_days" placeholder="0 = desativado">
                </label>
              </div>
            </div>
          </section>

          <!-- BOTÕES & FLUXOS padrão -->
          <section class="moz-card collapsed">
            <button class="moz-card-header" type="button">
              <span>Botões &amp; Fluxos (padrão)</span>
              <span class="moz-chevron">▸</span>
            </button>
            <div class="moz-card-body">
              <label>
                Botões padrão
                <input type="text" id="proc_default_buttons" placeholder="Ex: concluir,reprovar,devolver">
              </label>
              <label>
                Mapa de botões padrão → fluxos
                <textarea rows="3" id="proc_default_button_map"
                  placeholder="concluir:Flow_Concluido&#10;reprovar:Flow_Reprovado"></textarea>
              </label>
            </div>
          </section>

          <!-- CONTINUAÇÕES ASSÍNCRONAS -->
          <section class="moz-card collapsed">
            <button class="moz-card-header" type="button">
              <span>Continuações assíncronas</span>
              <span class="moz-chevron">▸</span>
            </button>
            <div class="moz-card-body">
              <label>
                Modo padrão
                <select id="proc_async_mode">
                  <option value="none">Nenhum</option>
                  <option value="before">Antes</option>
                  <option value="after">Depois</option>
                  <option value="exclusive">Exclusiva</option>
                </select>
              </label>
              <label>
                Criar tarefa de execução?
                <select id="proc_async_create_job">
                  <option value="0">Não</option>
                  <option value="1">Sim</option>
                </select>
              </label>
              <div id="proc_async_job_config">
                <label>
                  Tempo de repetição do ciclo (cron / ISO)
                  <input type="text" id="proc_async_cycle" placeholder="Ex: R3/PT10M">
                </label>
                <label>
                  Prioridade do job
                  <input type="number" id="proc_async_priority" value="50">
                </label>
              </div>
            </div>
          </section>

          <!-- Entradas (Inputs) -->
          <section class="moz-card collapsed">
            <button class="moz-card-header" type="button">
              <span>Entradas (Inputs)</span>
              <span class="moz-chevron">▸</span>
            </button>
            <div class="moz-card-body">
              <div id="proc_inputs_container"></div>
              <button type="button" class="btn btn-xs" id="proc_add_input">Adicionar entrada</button>
              <p style="font-size:11px;color:#6b7280;margin-top:6px;">
                Local variable name, tipo (Lista, Mapa, Script, String, Expressão) e valor.
              </p>
            </div>
          </section>

          <!-- Saídas (Outputs) -->
          <section class="moz-card collapsed">
            <button class="moz-card-header" type="button">
              <span>Saídas (Outputs)</span>
              <span class="moz-chevron">▸</span>
            </button>
            <div class="moz-card-body">
              <div id="proc_outputs_container"></div>
              <button type="button" class="btn btn-xs" id="proc_add_output">Adicionar saída</button>
            </div>
          </section>

          <!-- Injeção de Campos -->
          <section class="moz-card collapsed">
            <button class="moz-card-header" type="button">
              <span>Injeção de Campos (Field injections)</span>
              <span class="moz-chevron">▸</span>
            </button>
            <div class="moz-card-body">
              <div id="proc_fields_container"></div>
              <button type="button" class="btn btn-xs" id="proc_add_field">Adicionar campo</button>
            </div>
          </section>

          <!-- Ouvintes de Execução -->
          <section class="moz-card collapsed">
            <button class="moz-card-header" type="button">
              <span>Ouvintes de Execução (Execution Listeners)</span>
              <span class="moz-chevron">▸</span>
            </button>
            <div class="moz-card-body">
              <div id="proc_listeners_container"></div>
              <button type="button" class="btn btn-xs" id="proc_add_listener">Adicionar ouvinte</button>
            </div>
          </section>

          <!-- Propriedades de Execução -->
          <section class="moz-card collapsed">
            <button class="moz-card-header" type="button">
              <span>Propriedades de Execução</span>
              <span class="moz-chevron">▸</span>
            </button>
            <div class="moz-card-body">
              <div id="proc_exec_props_container"></div>
              <button type="button" class="btn btn-xs" id="proc_add_exec_prop">Adicionar propriedade</button>
            </div>
          </section>

          <!-- Propriedades de Extensão -->
          <section class="moz-card collapsed">
            <button class="moz-card-header" type="button">
              <span>Propriedades de Extensão</span>
              <span class="moz-chevron">▸</span>
            </button>
            <div class="moz-card-body">
              <div id="proc_ext_props_container"></div>
              <button type="button" class="btn btn-xs" id="proc_add_ext_prop">Adicionar propriedade</button>
            </div>
          </section>

          <!-- Código adicional -->
          <section class="moz-card collapsed">
            <button class="moz-card-header" type="button">
              <span>Código adicional</span>
              <span class="moz-chevron">▸</span>
            </button>
            <div class="moz-card-body">
              <label>
                Linguagem
                <select id="proc_code_lang">
                  <option value="php">PHP</option>
                  <option value="python">Python</option>
                  <option value="js">JavaScript</option>
                </select>
              </label>
              <label>
                Local de injeção
                <select id="proc_code_position">
                  <option value="head">Antes de &lt;/head&gt;</option>
                  <option value="body">Antes de &lt;/body&gt;</option>
                </select>
              </label>
              <label>
                Código
                <textarea rows="4" id="proc_code_custom"></textarea>
              </label>
            </div>
          </section>

          <!-- CSS personalizado -->
          <section class="moz-card collapsed">
            <button class="moz-card-header" type="button">
              <span>CSS personalizado</span>
              <span class="moz-chevron">▸</span>
            </button>
            <div class="moz-card-body">
              <label>
                CSS
                <textarea rows="6" id="proc_css_custom" placeholder="Seletores e estilos específicos deste processo..."></textarea>
              </label>
            </div>
          </section>
        </div>
      `;

      // inicializa colapsáveis
      initMozartCollapsibles(body);

      // (por enquanto sem salvar nada no BPMN, é só visual pra ficar bonitinho 😎)
      return;
    }

    // ======= MODO ELEMENTO (StartEvent / EndEvent / etc.) =======
    const bo  = element.businessObject;
    const t   = tipoEvento(bo);
    const cfg = getMozartConfigFromBO(bo) || {};
    const modeling = modeler.get('modeling');

    // inicio dos start event
    if (t.grupo === 'startEvent') {

      // label bonitinho do subtipo
      let startLabel = 'Padrão (None)';
      let extraCard  = '';

      switch (t.subtipo) {
        case 'mensagem':
          startLabel = 'Mensagem (Message Start Event)';
          extraCard = `
            <section class="moz-card expanded">
              <button class="moz-card-header" type="button">
                <span>Message</span>
                <span class="moz-chevron">▾</span>
              </button>
              <div class="moz-card-body">
                <div class="moz-field">
                  <label>Global message reference</label>
                  <input type="text" id="se-msg-ref">
                </div>
                <div class="moz-field">
                  <label>Name</label>
                  <input type="text" id="se-msg-name">
                </div>
              </div>
            </section>
          `;
          break;

        case 'timer':
          startLabel = 'Timer (Timer Start Event)';
          extraCard = `
            <section class="moz-card expanded">
              <button class="moz-card-header" type="button">
                <span>Timer</span>
                <span class="moz-chevron">▾</span>
              </button>
              <div class="moz-card-body">
                <div class="moz-field">
                  <label>Tipo</label>
                  <select id="se-timer-type">
                    <option value="date">Date</option>
                    <option value="duration">Duration</option>
                    <option value="cycle">Cycle</option>
                  </select>
                </div>
                <div class="moz-field">
                  <label>Valor</label>
                  <input type="text" id="se-timer-val"
                         placeholder="Ex.: 2025-12-31T23:59:59 ou PT10M ou R3/PT1H">
                </div>
              </div>
            </section>
          `;
          break;

        case 'condicional':
          startLabel = 'Condition (Conditional Start Event)';
          extraCard = `
            <section class="moz-card expanded">
              <button class="moz-card-header" type="button">
                <span>Condition</span>
                <span class="moz-chevron">▾</span>
              </button>
              <div class="moz-card-body">
                <div class="moz-field">
                  <label>Nome da variável</label>
                  <input type="text" id="se-cond-var">
                </div>
                <div class="moz-field">
                  <label>Tipo</label>
                  <select id="se-cond-type">
                    <option value="none">&lt;none&gt;</option>
                    <option value="expression">Expression</option>
                    <option value="script">Script</option>
                  </select>
                </div>
                <div class="moz-field">
                  <label>Expressão da condição</label>
                  <textarea rows="3" id="se-cond-expr"></textarea>
                </div>
              </div>
            </section>
          `;
          break;

        case 'sinal':
          startLabel = 'Signal (Signal Start Event)';
          extraCard = `
            <section class="moz-card expanded">
              <button class="moz-card-header" type="button">
                <span>Signal</span>
                <span class="moz-chevron">▾</span>
              </button>
              <div class="moz-card-body">
                <div class="moz-field">
                  <label>Referência global</label>
                  <input type="text" id="se-signal-ref"
                         placeholder="ID global do sinal">
                </div>
                <div class="moz-field">
                  <label>Nome</label>
                  <input type="text" id="se-signal-name">
                </div>
              </div>
            </section>
          `;
          break;

        default:
          startLabel = 'Padrão (None)';
          extraCard = '';
      }

      title.textContent = 'Start Event';
      subtitle.textContent = `${startLabel} — ID: ${bo.id || '(sem id)'}`;

      body.innerHTML = `
        <div class="moz-panel moz-panel-b">

          <!-- GENERAL -->
          <section class="moz-card expanded">
            <button class="moz-card-header" type="button">
              <span>General</span>
              <span class="moz-chevron">▾</span>
            </button>
            <div class="moz-card-body">
              <div class="moz-field">
                <label>Name</label>
                <input type="text" id="se-name"
                       value="${(bo.name || '').replace(/"/g,'&quot;')}">
              </div>
              <div class="moz-field">
                <label>ID (criado pelo sistema)</label>
                <input type="text" id="se-id"
                       value="${bo.id || ''}" readonly>
              </div>
              <div class="moz-field">
                <label>Tipo de início</label>
                <input type="text" value="${startLabel}" readonly>
              </div>
            </div>
          </section>

          <!-- DOCUMENTATION -->
          <section class="moz-card collapsed">
            <button class="moz-card-header" type="button">
              <span>Documentation</span>
              <span class="moz-chevron">▸</span>
            </button>
            <div class="moz-card-body">
              <div class="moz-field">
                <label>Element documentation</label>
                <textarea rows="2" id="se-doc"></textarea>
              </div>
              <div class="moz-field">
                <label>Descrição / instruções</label>
                <textarea rows="2" id="se-desc"></textarea>
              </div>
            </div>
          </section>

          <!-- FORMULÁRIO -->
          <section class="moz-card collapsed">
            <button class="moz-card-header" type="button">
              <span>Formulário</span>
              <span class="moz-chevron">▸</span>
            </button>
            <div class="moz-card-body">
              <div class="moz-field">
                <label>Formulário</label>
                <select id="se-form">
                  <option value="">Selecione um formulário...</option>
                </select>
              </div>
            </div>
          </section>

          <!-- SLA & VISIBILIDADE -->
          <section class="moz-card collapsed">
            <button class="moz-card-header" type="button">
              <span>SLA &amp; Visibilidade</span>
              <span class="moz-chevron">▸</span>
            </button>
            <div class="moz-card-body">
              <div class="moz-row-2">
                <div class="moz-field">
                  <label>Prazo (horas)</label>
                  <input type="number" min="0" id="se-sla-hours">
                </div>
                <div class="moz-field">
                  <label>Escalonar para</label>
                  <select id="se-escalate-type">
                    <option value="none">Nenhum</option>
                    <option value="group">Grupo</option>
                    <option value="user">Usuário</option>
                  </select>
                </div>
              </div>
              <div class="moz-row-2">
                <div class="moz-field">
                  <label>Destino escalonamento</label>
                  <select id="se-escalate-ref">
                    <option value="">Selecione...</option>
                  </select>
                </div>
                <div class="moz-field">
                  <label>Responsável</label>
                  <select id="se-owner-type">
                    <option value="">Tipo...</option>
                    <option value="role">Papel</option>
                    <option value="group">Grupo</option>
                    <option value="user">Usuário</option>
                  </select>
                </div>
              </div>
              <div class="moz-field">
                <label>Referência do responsável</label>
                <select id="se-owner-ref">
                  <option value="">Selecione...</option>
                </select>
              </div>
            </div>
          </section>

          <!-- BOTÕES & FLUXOS -->
          <section class="moz-card collapsed">
            <button class="moz-card-header" type="button">
              <span>Botões &amp; Fluxos</span>
              <span class="moz-chevron">▸</span>
            </button>
            <div class="moz-card-body">
              <div class="moz-field">
                <label>Botões</label>
                <textarea rows="3" id="se-buttons"
                          placeholder='Ex.: Aprovar;Reprovar;Cancelar'></textarea>
              </div>
              <div class="moz-field">
                <label>Mapa de Botões</label>
                <textarea rows="3" id="se-buttons-map"
                  placeholder='Ex.: Aprovar:Flow_Aprovar&#10;Reprovar:Flow_Reprovar'></textarea>
              </div>
            </div>
          </section>

          ${extraCard}

          <!-- START INITIATION -->
          <section class="moz-card collapsed">
            <button class="moz-card-header" type="button">
              <span>Start initiation</span>
              <span class="moz-chevron">▸</span>
            </button>
            <div class="moz-card-body">
              <div class="moz-field">
                <label>Modo</label>
                <select id="se-init-mode">
                  <option value="none">Nenhum</option>
                  <option value="manual">Manual</option>
                  <option value="auto">Automático</option>
                  <option value="external">Externo (API)</option>
                </select>
              </div>
            </div>
          </section>

          <!-- CONTINUAÇÕES ASSÍNCRONAS -->
          <section class="moz-card collapsed">
            <button class="moz-card-header" type="button">
              <span>Continuações assíncronas</span>
              <span class="moz-chevron">▸</span>
            </button>
            <div class="moz-card-body">
              <div class="moz-field">
                <label>Modo</label>
                <select id="se-async-mode">
                  <option value="none">Nenhum</option>
                  <option value="before">Antes</option>
                  <option value="after">Depois</option>
                </select>
              </div>
              <div class="moz-row-2">
                <div class="moz-field">
                  <label>Retry time cycle</label>
                  <input type="text" id="se-async-retry" placeholder="Ex.: R3/PT10M">
                </div>
                <div class="moz-field">
                  <label>Priority</label>
                  <input type="number" id="se-async-priority">
                </div>
              </div>
            </div>
          </section>

          <!-- EXECUTION LISTENERS -->
          <section class="moz-card collapsed">
            <button class="moz-card-header" type="button">
              <span>Execution listeners</span>
              <span class="moz-chevron">▸</span>
            </button>
            <div class="moz-card-body">
              <div class="moz-field">
                <label>Definição</label>
                <textarea rows="4" id="se-exec-listeners"
                          placeholder="Depois definimos o formato (JSON, etc.)"></textarea>
              </div>
            </div>
          </section>

          <!-- EXECUTION PROPERTIES -->
          <section class="moz-card collapsed">
            <button class="moz-card-header" type="button">
              <span>Execution properties</span>
              <span class="moz-chevron">▸</span>
            </button>
            <div class="moz-card-body">
              <div class="moz-field">
                <label>Propriedades</label>
                <textarea rows="3" id="se-exec-props"></textarea>
              </div>
            </div>
          </section>

          <!-- EXTENSION PROPERTIES -->
          <section class="moz-card collapsed">
            <button class="moz-card-header" type="button">
              <span>Extension properties</span>
              <span class="moz-chevron">▸</span>
            </button>
            <div class="moz-card-body">
              <div class="moz-field">
                <label>Propriedades</label>
                <textarea rows="3" id="se-ext-props"></textarea>
              </div>
            </div>
          </section>

          <!-- CONNECTORES -->
          <section class="moz-card collapsed">
            <button class="moz-card-header" type="button">
              <span>Conectores</span>
              <span class="moz-chevron">▸</span>
            </button>
            <div class="moz-card-body">
              <div class="moz-field">
                <label>Configuração</label>
                <textarea rows="3" id="se-connectors"></textarea>
              </div>
            </div>
          </section>

          <!-- JSON AVANÇADO -->
          <section class="moz-card collapsed">
            <button class="moz-card-header" type="button">
              <span>Config Mozart (JSON avançado)</span>
              <span class="moz-chevron">▸</span>
            </button>
            <div class="moz-card-body">
              <div class="moz-field">
                <label>mozart:config</label>
                <textarea rows="8" id="se-json">${JSON.stringify(cfg, null, 2)}</textarea>
              </div>
              <button type="button" class="btn btn-xs" id="se-json-apply">
                Aplicar JSON no elemento
              </button>
            </div>
          </section>

        </div>
      `;

      // colapsáveis
      initMozartCollapsibles(body);

      // atualizar nome no diagrama
      const nameInput = body.querySelector('#se-name');
      if (nameInput) {
        nameInput.addEventListener('change', () => {
          const novoNome = nameInput.value;
          modeling.updateProperties(element, { name: novoNome });

          const cfgAtual = getMozartConfigFromBO(bo) || {};
          if (!cfgAtual.geral) cfgAtual.geral = {};
          cfgAtual.geral.nome = novoNome;
          saveMozartConfig(modeler, element, cfgAtual);
        });
      }

      // aplicar JSON manual
      const jsonBtn  = body.querySelector('#se-json-apply');
      const jsonArea = body.querySelector('#se-json');
      if (jsonBtn && jsonArea) {
        jsonBtn.addEventListener('click', () => {
          try {
            const novoCfg = JSON.parse(jsonArea.value || '{}');
            saveMozartConfig(modeler, element, novoCfg);
            if (novoCfg.geral && novoCfg.geral.nome) {
              modeling.updateProperties(element, { name: novoCfg.geral.nome });
            }
            renderMozartPanel(modeler, element);
            alert('Config Mozart aplicada ao Start Event.');
          } catch (e) {
            console.error(e);
            alert('JSON inválido. Verifique a sintaxe.');
          }
        });
      }

      return;
    }
    // fim dos start event

    // inicio dos end event
    if (t.grupo === 'endEvent') {

      // ===== labels + cards extras por subtipo =====
      let endLabel  = 'Padrão (None End Event)';
      let extraCard = '';

      // Helpers (visual apenas)
      const asOpt = (v, txt) => `<option value="${v}">${txt}</option>`;

      // Cards comuns (padrão)
      const cardGeral = `
        <section class="moz-card expanded">
          <button class="moz-card-header" type="button">
            <span>Geral</span>
            <span class="moz-chevron">▾</span>
          </button>
          <div class="moz-card-body">
            <div class="moz-field">
              <label>Name</label>
              <input type="text" id="ee-name" value="${(bo.name || '').replace(/"/g,'&quot;')}">
            </div>
            <div class="moz-field">
              <label>ID (criado pelo sistema)</label>
              <input type="text" id="ee-id" value="${bo.id || ''}" readonly>
            </div>
          </div>
        </section>
      `;

      const cardDoc = `
        <section class="moz-card collapsed">
          <button class="moz-card-header" type="button">
            <span>Documentação</span>
            <span class="moz-chevron">▸</span>
          </button>
          <div class="moz-card-body">
            <div class="moz-field">
              <label>Element documentation</label>
              <textarea rows="2" id="ee-doc"></textarea>
            </div>
            <div class="moz-field">
              <label>Descrição / instruções</label>
              <textarea rows="2" id="ee-desc"></textarea>
            </div>
          </div>
        </section>
      `;

      const cardSlaVis = `
        <section class="moz-card collapsed">
          <button class="moz-card-header" type="button">
            <span>SLA &amp; Visibilidade</span>
            <span class="moz-chevron">▸</span>
          </button>
          <div class="moz-card-body">
            <div class="moz-row-2">
              <div class="moz-field">
                <label>Prazo (horas)</label>
                <input type="number" min="0" id="ee-sla-hours">
              </div>
              <div class="moz-field">
                <label>Escalonar para</label>
                <select id="ee-escalate-type">
                  ${asOpt('none','Nenhum')}
                  ${asOpt('group','Grupo')}
                  ${asOpt('user','Usuário')}
                </select>
              </div>
            </div>

            <div class="moz-row-2">
              <div class="moz-field">
                <label>Destino escalonamento</label>
                <select id="ee-escalate-ref">
                  <option value="">Selecione...</option>
                </select>
              </div>
              <div class="moz-field">
                <label>Responsável</label>
                <select id="ee-owner-type">
                  <option value="">Tipo...</option>
                  ${asOpt('role','Papel')}
                  ${asOpt('group','Grupo')}
                  ${asOpt('user','Usuário')}
                </select>
              </div>
            </div>

            <div class="moz-field">
              <label>Referência do responsável</label>
              <select id="ee-owner-ref">
                <option value="">Selecione...</option>
              </select>
            </div>
          </div>
        </section>
      `;

      const cardAsync = `
        <section class="moz-card collapsed">
          <button class="moz-card-header" type="button">
            <span>Continuações assíncronas</span>
            <span class="moz-chevron">▸</span>
          </button>
          <div class="moz-card-body">
            <div class="moz-field">
              <label>Modo</label>
              <select id="ee-async-mode">
                ${asOpt('none','Nenhum')}
                ${asOpt('before','Antes')}
                ${asOpt('after','Depois')}
              </select>
            </div>

            <div id="ee-async-job" style="display:none;">
              <div class="moz-row-2">
                <div class="moz-field">
                  <label>Retry time cycle</label>
                  <input type="text" id="ee-async-retry" placeholder="Ex.: R3/PT10M">
                </div>
                <div class="moz-field">
                  <label>Priority</label>
                  <input type="number" id="ee-async-priority" placeholder="50">
                </div>
              </div>
            </div>
          </div>
        </section>
      `;

      const cardInputs = `
        <section class="moz-card collapsed">
          <button class="moz-card-header" type="button">
            <span>Inputs</span>
            <span class="moz-chevron">▸</span>
          </button>
          <div class="moz-card-body">
            <div id="ee-inputs-container"></div>
            <button type="button" class="btn btn-xs" id="ee-add-input">Adicionar input</button>
            <p style="font-size:11px;color:#6b7280;margin-top:6px;">
              Nome da variável local, tipo (Lista/Mapa/Script/String/Expressão) e valor.
            </p>
          </div>
        </section>
      `;

      const cardExecListeners = `
        <section class="moz-card collapsed">
          <button class="moz-card-header" type="button">
            <span>Ouvintes de Execução (Execution Listeners)</span>
            <span class="moz-chevron">▸</span>
          </button>
          <div class="moz-card-body">
            <div id="ee-listeners-container"></div>
            <button type="button" class="btn btn-xs" id="ee-add-listener">Adicionar ouvinte</button>
          </div>
        </section>
      `;

      const cardExecProps = `
        <section class="moz-card collapsed">
          <button class="moz-card-header" type="button">
            <span>Propriedades de Execução</span>
            <span class="moz-chevron">▸</span>
          </button>
          <div class="moz-card-body">
            <div id="ee-exec-props-container"></div>
            <button type="button" class="btn btn-xs" id="ee-add-exec-prop">Adicionar propriedade</button>
          </div>
        </section>
      `;

      const cardExtProps = `
        <section class="moz-card collapsed">
          <button class="moz-card-header" type="button">
            <span>Propriedades de Extensão</span>
            <span class="moz-chevron">▸</span>
          </button>
          <div class="moz-card-body">
            <div id="ee-ext-props-container"></div>
            <button type="button" class="btn btn-xs" id="ee-add-ext-prop">Adicionar propriedade</button>
          </div>
        </section>
      `;

      const cardConnectors = `
        <section class="moz-card collapsed">
          <button class="moz-card-header" type="button">
            <span>Conectores</span>
            <span class="moz-chevron">▸</span>
          </button>
          <div class="moz-card-body">
            <div class="moz-field">
              <label>Configuração</label>
              <textarea rows="3" id="ee-connectors"></textarea>
            </div>
          </div>
        </section>
      `;

      const cardXml = `
        <section class="moz-card collapsed">
          <button class="moz-card-header" type="button">
            <span>XML</span>
            <span class="moz-chevron">▸</span>
          </button>
          <div class="moz-card-body">
            <div class="moz-field">
              <label>mozart:config</label>
              <textarea rows="8" id="ee-json">${JSON.stringify(cfg, null, 2)}</textarea>
            </div>
            <button type="button" class="btn btn-xs" id="ee-json-apply">Aplicar JSON no elemento</button>
          </div>
        </section>
      `;

      // Botões & Fluxos (só para alguns subtipos)
      const cardButtonsFlows = `
        <section class="moz-card collapsed">
          <button class="moz-card-header" type="button">
            <span>Botões &amp; Fluxos</span>
            <span class="moz-chevron">▸</span>
          </button>
          <div class="moz-card-body">
            <div class="moz-field">
              <label>Botões</label>
              <textarea rows="3" id="ee-buttons" placeholder="Ex.: Concluir;Cancelar"></textarea>
            </div>
            <div class="moz-field">
              <label>Mapa de Botões</label>
              <textarea rows="3" id="ee-buttons-map" placeholder="Concluir:Flow_Concluir&#10;Cancelar:Flow_Cancelar"></textarea>
            </div>
          </div>
        </section>
      `;

      // Implementação (Message End Event)
      const cardImpl = `
        <section class="moz-card collapsed">
          <button class="moz-card-header" type="button">
            <span>Implementação</span>
            <span class="moz-chevron">▸</span>
          </button>
          <div class="moz-card-body">
            <div class="moz-field">
              <label>Tipo</label>
              <select id="ee-impl-type">
                ${asOpt('external','Externo')}
                ${asOpt('javaClass','Classe Java')}
                ${asOpt('expression','Expressão')}
                ${asOpt('delegateExpression','Expressão delegada')}
                ${asOpt('connector','Conector')}
              </select>
            </div>
          </div>
        </section>
      `;

      // Field Injections (para alguns subtipos)
      const cardFieldInjections = `
        <section class="moz-card collapsed">
          <button class="moz-card-header" type="button">
            <span>Injeção de Campos (Field injections)</span>
            <span class="moz-chevron">▸</span>
          </button>
          <div class="moz-card-body">
            <div id="ee-fields-container"></div>
            <button type="button" class="btn btn-xs" id="ee-add-field">Adicionar campo</button>
          </div>
        </section>
      `;

      // Subcards específicos
      const cardMessage = `
        <section class="moz-card expanded">
          <button class="moz-card-header" type="button">
            <span>Message</span>
            <span class="moz-chevron">▾</span>
          </button>
          <div class="moz-card-body">
            <div class="moz-field">
              <label>Global message reference</label>
              <input type="text" id="ee-msg-ref">
            </div>
            <div class="moz-field">
              <label>Name</label>
              <input type="text" id="ee-msg-name">
            </div>
          </div>
        </section>
      `;

      const cardEscalation = `
        <section class="moz-card expanded">
          <button class="moz-card-header" type="button">
            <span>Escalação</span>
            <span class="moz-chevron">▾</span>
          </button>
          <div class="moz-card-body">
            <div class="moz-field">
              <label>Global escalation reference</label>
              <input type="text" id="ee-esc-ref">
            </div>
            <div class="moz-field">
              <label>Name</label>
              <input type="text" id="ee-esc-name">
            </div>
            <div class="moz-field">
              <label>Código</label>
              <input type="text" id="ee-esc-code">
            </div>
          </div>
        </section>
      `;

      const cardError = `
        <section class="moz-card expanded">
          <button class="moz-card-header" type="button">
            <span>Error</span>
            <span class="moz-chevron">▾</span>
          </button>
          <div class="moz-card-body">
            <div class="moz-field">
              <label>Global error reference</label>
              <input type="text" id="ee-err-ref">
            </div>
            <div class="moz-field">
              <label>Name</label>
              <input type="text" id="ee-err-name">
            </div>
            <div class="moz-field">
              <label>Código</label>
              <input type="text" id="ee-err-code">
            </div>
            <div class="moz-field">
              <label>Mensagem</label>
              <input type="text" id="ee-err-message">
            </div>
          </div>
        </section>
      `;

      const cardSignal = `
        <section class="moz-card expanded">
          <button class="moz-card-header" type="button">
            <span>Signal</span>
            <span class="moz-chevron">▾</span>
          </button>
          <div class="moz-card-body">
            <div class="moz-field">
              <label>Global signal reference</label>
              <input type="text" id="ee-signal-ref">
            </div>
            <div class="moz-field">
              <label>Name</label>
              <input type="text" id="ee-signal-name">
            </div>
          </div>
        </section>
      `;

      const cardCompensate = `
        <section class="moz-card expanded">
          <button class="moz-card-header" type="button">
            <span>Compensação</span>
            <span class="moz-chevron">▾</span>
          </button>
          <div class="moz-card-body">
            <label style="display:flex;align-items:center;gap:6px;margin-bottom:8px;">
              <input type="checkbox" id="ee-comp-wait">
              <span>Espere para completar</span>
            </label>
            <div class="moz-field">
              <label>Selecionar</label>
              <select id="ee-comp-select">
                <option value="">— selecionar —</option>
              </select>
            </div>
          </div>
        </section>
      `;

      // ===== define subtipo do End Event =====
      switch (t.subtipo) {
        case 'mensagem':
          endLabel  = 'Mensagem (Message End Event)';
          extraCard = cardImpl + cardButtonsFlows + cardMessage + cardFieldInjections;
          break;
        case 'escalacao':
          endLabel  = 'Escalação (Escalation End Event)';
          extraCard = cardButtonsFlows + cardEscalation + cardFieldInjections;
          break;
        case 'erro':
          endLabel  = 'Erro (Error End Event)';
          extraCard = cardButtonsFlows + cardError + cardFieldInjections;
          break;
        case 'compensacao':
          endLabel  = 'Compensação (Compensate End Event)';
          extraCard = cardButtonsFlows + cardCompensate + cardFieldInjections;
          break;
        case 'sinal':
          endLabel  = 'Sinal (Signal End Event)';
          extraCard = cardButtonsFlows + cardSignal + cardFieldInjections;
          break;
        default:
          // terminate não vem via defs[0]; é um EndEventDefinition específico.
          // Se não for detectado, fica como None.
          endLabel  = 'Padrão (None / Terminate End Event)';
          extraCard = '';
      }

      title.textContent = 'End Event';
      subtitle.textContent = `${endLabel} — ID: ${bo.id || '(sem id)'}`;

      body.innerHTML = `
        <div class="moz-panel moz-panel-b">
          ${cardGeral}
          ${cardDoc}
          ${cardSlaVis}
          ${extraCard}
          ${cardAsync}
          ${cardInputs}
          ${cardExecListeners}
          ${cardExecProps}
          ${cardExtProps}
          ${cardConnectors}
          ${cardXml}
        </div>
      `;

      // colapsáveis
      initMozartCollapsibles(body);

      // ===== comportamento visual (somente UI) =====

      // atualizar nome no diagrama (igual Start)
      const eeName = body.querySelector('#ee-name');
      if (eeName) {
        eeName.addEventListener('change', () => {
          const novoNome = eeName.value;
          modeling.updateProperties(element, { name: novoNome });

          const cfgAtual = getMozartConfigFromBO(bo) || {};
          if (!cfgAtual.geral) cfgAtual.geral = {};
          cfgAtual.geral.nome = novoNome;
          saveMozartConfig(modeler, element, cfgAtual);
        });
      }

      // async: mostrar job config quando before/after
      const eeAsyncMode = body.querySelector('#ee-async-mode');
      const eeAsyncJob  = body.querySelector('#ee-async-job');
      if (eeAsyncMode && eeAsyncJob) {
        const refresh = () => {
          const v = eeAsyncMode.value;
          eeAsyncJob.style.display = (v && v !== 'none') ? '' : 'none';
        };
        eeAsyncMode.addEventListener('change', refresh);
        refresh();
      }

      // Inputs (lista visual)
      function addInputRow() {
        const wrap = body.querySelector('#ee-inputs-container');
        if (!wrap) return;

        const row = document.createElement('div');
        row.style.border = '1px dashed #e5e7eb';
        row.style.borderRadius = '8px';
        row.style.padding = '8px';
        row.style.marginBottom = '8px';

        row.innerHTML = `
          <div class="moz-row-2">
            <div class="moz-field">
              <label>Nome da variável local</label>
              <input type="text" class="ee-in-name" placeholder="ex: statusFinal">
            </div>
            <div class="moz-field">
              <label>Tipo</label>
              <select class="ee-in-type">
                ${asOpt('list','Lista')}
                ${asOpt('map','Mapa')}
                ${asOpt('script','Script')}
                ${asOpt('string','String')}
                ${asOpt('expression','Expressão')}
              </select>
            </div>
          </div>
          <div class="moz-field">
            <label>Valor</label>
            <textarea rows="2" class="ee-in-val" placeholder="valor / expressão / script..."></textarea>
          </div>
          <button type="button" class="btn btn-xs ee-in-del">Remover</button>
        `;

        row.querySelector('.ee-in-del')?.addEventListener('click', () => row.remove());
        wrap.appendChild(row);
      }

      body.querySelector('#ee-add-input')?.addEventListener('click', addInputRow);

      // Execution listeners (lista visual)
      function addListenerRow() {
        const wrap = body.querySelector('#ee-listeners-container');
        if (!wrap) return;

        const card = document.createElement('section');
        card.className = 'moz-card expanded';

        card.innerHTML = `
          <button class="moz-card-header" type="button">
            <span>Iniciar Classe</span>
            <span class="moz-chevron">▾</span>
          </button>
          <div class="moz-card-body">
            <div class="moz-field">
              <label>Iniciar classe Java</label>
              <input type="text" class="ee-l-java" placeholder="com.seu.Listener">
            </div>
            <div class="moz-row-2">
              <div class="moz-field">
                <label>Tipo de evento</label>
                <select class="ee-l-event">
                  ${asOpt('start','Inicio')}
                  ${asOpt('end','Fim')}
                </select>
              </div>
              <div class="moz-field">
                <label>Tipo</label>
                <select class="ee-l-type">
                  ${asOpt('javaClass','Classe Java')}
                  ${asOpt('expression','Expressão')}
                  ${asOpt('delegateExpression','Expressão Delegada')}
                  ${asOpt('script','Script')}
                </select>
              </div>
            </div>
            <div class="moz-field">
              <label>Classe java</label>
              <input type="text" class="ee-l-class" placeholder="com.seu.DelegateOuClasse">
            </div>
            <button type="button" class="btn btn-xs ee-l-del">Remover</button>
          </div>
        `;

        // colapsável por card
        initMozartCollapsibles(card);

        card.querySelector('.ee-l-del')?.addEventListener('click', () => card.remove());
        wrap.appendChild(card);
      }

      body.querySelector('#ee-add-listener')?.addEventListener('click', addListenerRow);

      // Exec props (lista visual)
      function addPropRow(containerSel) {
        const wrap = body.querySelector(containerSel);
        if (!wrap) return;

        const row = document.createElement('div');
        row.style.border = '1px dashed #e5e7eb';
        row.style.borderRadius = '8px';
        row.style.padding = '8px';
        row.style.marginBottom = '8px';

        row.innerHTML = `
          <div class="moz-row-2">
            <div class="moz-field">
              <label>Nome</label>
              <input type="text" class="ee-p-name" placeholder="ex: key">
            </div>
            <div class="moz-field">
              <label>Valor</label>
              <input type="text" class="ee-p-val" placeholder="ex: value">
            </div>
          </div>
          <button type="button" class="btn btn-xs ee-p-del">Remover</button>
        `;

        row.querySelector('.ee-p-del')?.addEventListener('click', () => row.remove());
        wrap.appendChild(row);
      }

      body.querySelector('#ee-add-exec-prop')?.addEventListener('click', () => addPropRow('#ee-exec-props-container'));
      body.querySelector('#ee-add-ext-prop') ?.addEventListener('click', () => addPropRow('#ee-ext-props-container'));

      // Field injections (lista visual) - quando existir
      function addFieldRow() {
        const wrap = body.querySelector('#ee-fields-container');
        if (!wrap) return;

        const row = document.createElement('div');
        row.style.border = '1px dashed #e5e7eb';
        row.style.borderRadius = '8px';
        row.style.padding = '8px';
        row.style.marginBottom = '8px';

        row.innerHTML = `
          <div class="moz-row-2">
            <div class="moz-field">
              <label>Nome</label>
              <input type="text" class="ee-f-name" placeholder="ex: url">
            </div>
            <div class="moz-field">
              <label>Tipo</label>
              <select class="ee-f-type">
                ${asOpt('string','String')}
                ${asOpt('expression','Expressão')}
              </select>
            </div>
          </div>
          <div class="moz-field">
            <label>Valor</label>
            <input type="text" class="ee-f-val" placeholder="ex: https://... ou ${variavel}">
          </div>
          <button type="button" class="btn btn-xs ee-f-del">Remover</button>
        `;

        row.querySelector('.ee-f-del')?.addEventListener('click', () => row.remove());
        wrap.appendChild(row);
      }

      body.querySelector('#ee-add-field')?.addEventListener('click', addFieldRow);

      // aplicar JSON manual (igual Start)
      const jsonBtn  = body.querySelector('#ee-json-apply');
      const jsonArea = body.querySelector('#ee-json');
      if (jsonBtn && jsonArea) {
        jsonBtn.addEventListener('click', () => {
          try {
            const novoCfg = JSON.parse(jsonArea.value || '{}');
            saveMozartConfig(modeler, element, novoCfg);
            if (novoCfg.geral && novoCfg.geral.nome) {
              modeling.updateProperties(element, { name: novoCfg.geral.nome });
            }
            renderMozartPanel(modeler, element);
            alert('Config Mozart aplicada ao End Event.');
          } catch (e) {
            console.error(e);
            alert('JSON inválido. Verifique a sintaxe.');
          }
        });
      }

      return;
    }
    // fim dos end event

    // inicio dos intermediate event
if (t.grupo === 'intermediateEvent') {

  const modo = t.modo || cfg.modo || 'catch';  // catch | throw
  const subtipo = t.subtipo || cfg.subtipo || 'none';

  // label bonitinho
  let label = `Intermediate ${modo === 'throw' ? 'Throw' : 'Catch'} (None)`;
  let extraCard = '';
  let implCard  = '';

  // cards específicos por subtipo
  switch (subtipo) {
    case 'mensagem':
      label = `Message Intermediate ${modo === 'throw' ? 'Throw' : 'Catch'} Event`;
      extraCard = `
        <section class="moz-card expanded">
          <button class="moz-card-header" type="button">
            <span>Message</span>
            <span class="moz-chevron">▾</span>
          </button>
          <div class="moz-card-body">
            <div class="moz-field">
              <label>Global message reference</label>
              <input type="text" id="ie-msg-ref">
            </div>
          </div>
        </section>
      `;

      if (modo === 'throw') {
        implCard = `
          <section class="moz-card collapsed">
            <button class="moz-card-header" type="button">
              <span>Implementation</span>
              <span class="moz-chevron">▸</span>
            </button>
            <div class="moz-card-body">
              <div class="moz-field">
                <label>Tipo</label>
                <select id="ie-impl-type">
                  <option value="none">&lt;none&gt;</option>
                  <option value="external">Externo</option>
                  <option value="javaClass">Classe Java</option>
                  <option value="expression">Expressão</option>
                  <option value="delegateExpression">Expressão delegada</option>
                  <option value="connector">Conector</option>
                </select>
              </div>

              <div id="ie-impl-fields" style="margin-top:6px;"></div>
            </div>
          </section>
        `;
      }
      break;

    case 'timer':
      label = 'Timer Intermediate Catch Event';
      extraCard = `
        <section class="moz-card expanded">
          <button class="moz-card-header" type="button">
            <span>Timer</span>
            <span class="moz-chevron">▾</span>
          </button>
          <div class="moz-card-body">
            <div class="moz-field">
              <label>Tipo</label>
              <select id="ie-timer-type">
                <option value="date">Data</option>
                <option value="duration">Duração</option>
                <option value="cycle">Ciclo</option>
              </select>
            </div>
            <div class="moz-field">
              <label>Valor</label>
              <input type="text" id="ie-timer-val" placeholder="Ex.: 2025-12-31T23:59:59 ou PT10M ou R3/PT1H">
            </div>
          </div>
        </section>

        <section class="moz-card collapsed">
          <button class="moz-card-header" type="button">
            <span>Job Execution</span>
            <span class="moz-chevron">▸</span>
          </button>
          <div class="moz-card-body">
            <div class="moz-row-2">
              <div class="moz-field">
                <label>Retry time cycle</label>
                <input type="text" id="ie-job-retry" placeholder="Ex.: R3/PT10M">
              </div>
              <div class="moz-field">
                <label>Priority</label>
                <input type="number" id="ie-job-priority">
              </div>
            </div>
          </div>
        </section>
      `;
      break;

    case 'escalacao':
      label = `Escalation Intermediate ${modo === 'throw' ? 'Throw' : 'Catch'} Event`;
      extraCard = `
        <section class="moz-card expanded">
          <button class="moz-card-header" type="button">
            <span>Escalation</span>
            <span class="moz-chevron">▾</span>
          </button>
          <div class="moz-card-body">
            <div class="moz-field">
              <label>Global escalation reference</label>
              <input type="text" id="ie-esc-ref">
            </div>
            <div class="moz-row-2">
              <div class="moz-field">
                <label>Name</label>
                <input type="text" id="ie-esc-name">
              </div>
              <div class="moz-field">
                <label>Code</label>
                <input type="text" id="ie-esc-code">
              </div>
            </div>
          </div>
        </section>
      `;
      break;

    case 'condicional':
      label = `Conditional Intermediate ${modo === 'throw' ? 'Throw' : 'Catch'} Event`;
      extraCard = `
        <section class="moz-card expanded">
          <button class="moz-card-header" type="button">
            <span>Condition</span>
            <span class="moz-chevron">▾</span>
          </button>
          <div class="moz-card-body">
            <div class="moz-field">
              <label>Variable name</label>
              <input type="text" id="ie-cond-var">
            </div>
            <div class="moz-field">
              <label>Variable events</label>
              <input type="text" id="ie-cond-events" placeholder="Ex.: create,update,delete">
            </div>
            <div class="moz-field">
              <label>Tipo</label>
              <select id="ie-cond-type">
                <option value="none">&lt;none&gt;</option>
                <option value="script">Script</option>
                <option value="expression">Expressão</option>
              </select>
            </div>
            <div class="moz-field">
              <label>Expressão / Script</label>
              <textarea rows="3" id="ie-cond-expr"></textarea>
            </div>
          </div>
        </section>
      `;
      break;

    case 'link':
      label = `Link Intermediate ${modo === 'throw' ? 'Throw' : 'Catch'} Event`;
      extraCard = `
        <section class="moz-card expanded">
          <button class="moz-card-header" type="button">
            <span>Link</span>
            <span class="moz-chevron">▾</span>
          </button>
          <div class="moz-card-body">
            <div class="moz-field">
              <label>Name</label>
              <input type="text" id="ie-link-name">
            </div>
          </div>
        </section>
      `;
      break;

    case 'sinal':
      label = `Signal Intermediate ${modo === 'throw' ? 'Throw' : 'Catch'} Event`;
      extraCard = `
        <section class="moz-card expanded">
          <button class="moz-card-header" type="button">
            <span>Signal</span>
            <span class="moz-chevron">▾</span>
          </button>
          <div class="moz-card-body">
            <div class="moz-field">
              <label>Global signal reference</label>
              <input type="text" id="ie-signal-ref">
            </div>
          </div>
        </section>
      `;
      break;

    case 'compensacao':
      label = 'Compensate Intermediate Throw Event';
      extraCard = `
        <section class="moz-card expanded">
          <button class="moz-card-header" type="button">
            <span>Compensation</span>
            <span class="moz-chevron">▾</span>
          </button>
          <div class="moz-card-body">
            <label style="display:flex;align-items:center;gap:6px;margin-bottom:8px;">
              <input type="checkbox" id="ie-comp-wait">
              <span>Wait for completion</span>
            </label>
            <div class="moz-field">
              <label>Activity reference</label>
              <select id="ie-comp-activity">
                <option value="">&lt;none&gt;</option>
              </select>
            </div>
          </div>
        </section>
      `;
      break;

    default:
      // none
      label = `Intermediate ${modo === 'throw' ? 'Throw' : 'Catch'} (None)`;
      extraCard = '';
      implCard = '';
  }

  title.textContent = 'Intermediate Event';
  subtitle.textContent = `${label} — ID: ${bo.id || '(sem id)'}`;

  body.innerHTML = `
    <div class="moz-panel moz-panel-b">

      <!-- GERAL -->
      <section class="moz-card expanded">
        <button class="moz-card-header" type="button">
          <span>Geral</span>
          <span class="moz-chevron">▾</span>
        </button>
        <div class="moz-card-body">
          <div class="moz-field">
            <label>Name</label>
            <input type="text" id="ie-name" value="${(bo.name || '').replace(/"/g,'&quot;')}">
          </div>
          <div class="moz-field">
            <label>ID (criado pelo sistema)</label>
            <input type="text" id="ie-id" value="${bo.id || ''}" readonly>
          </div>
          <div class="moz-field">
            <label>Tipo</label>
            <input type="text" value="${label}" readonly>
          </div>
        </div>
      </section>

      <!-- DOCUMENTAÇÃO -->
      <section class="moz-card collapsed">
        <button class="moz-card-header" type="button">
          <span>Documentação</span>
          <span class="moz-chevron">▸</span>
        </button>
        <div class="moz-card-body">
          <div class="moz-field">
            <label>Element documentation</label>
            <textarea rows="2" id="ie-doc"></textarea>
          </div>
          <div class="moz-field">
            <label>Descrição / instruções</label>
            <textarea rows="2" id="ie-desc"></textarea>
          </div>
        </div>
      </section>

      <!-- FORMULÁRIO -->
      <section class="moz-card collapsed">
        <button class="moz-card-header" type="button">
          <span>Formulário</span>
          <span class="moz-chevron">▸</span>
        </button>
        <div class="moz-card-body">
          <div class="moz-field">
            <label>Formulário</label>
            <select id="ie-form">
              <option value="">Selecione um formulário...</option>
            </select>
          </div>
          <div class="moz-field">
            <label>Modo</label>
            <select id="ie-form-mode">
              <option value="view">Visualizar</option>
              <option value="edit">Editar</option>
              <option value="readonly">Somente leitura</option>
            </select>
          </div>
        </div>
      </section>

      <!-- SLA & VISIBILIDADE -->
      <section class="moz-card collapsed">
        <button class="moz-card-header" type="button">
          <span>SLA &amp; Visibilidade</span>
          <span class="moz-chevron">▸</span>
        </button>
        <div class="moz-card-body">
          <div class="moz-row-2">
            <div class="moz-field">
              <label>Prazo (horas)</label>
              <input type="number" min="0" id="ie-sla-hours">
            </div>
            <div class="moz-field">
              <label>Escalonar para</label>
              <select id="ie-escalate-type">
                <option value="none">Nenhum</option>
                <option value="group">Grupo</option>
                <option value="user">Usuário</option>
              </select>
            </div>
          </div>
          <div class="moz-field">
            <label>Destino escalonamento</label>
            <select id="ie-escalate-ref">
              <option value="">Selecione...</option>
            </select>
          </div>
          <div class="moz-field">
            <label>Perfis / grupos com acesso</label>
            <select id="ie-visible-to" multiple size="4">
              <option value="compras">compras</option>
              <option value="ti">ti</option>
              <option value="rh">rh</option>
              <option value="diretoria">diretoria</option>
            </select>
            <small style="font-size:11px;color:#6b7280;">(multi-select, depois ligamos no banco)</small>
          </div>
          <div class="moz-field">
            <label>Permitir reabrir?</label>
            <select id="ie-reopen">
              <option value="0">Não</option>
              <option value="1">Sim</option>
            </select>
          </div>
        </div>
      </section>

      <!-- BOTÕES & FLUXOS -->
      <section class="moz-card collapsed">
        <button class="moz-card-header" type="button">
          <span>Botões &amp; Fluxos</span>
          <span class="moz-chevron">▸</span>
        </button>
        <div class="moz-card-body">
          <div class="moz-field">
            <label>Botões</label>
            <textarea rows="3" id="ie-buttons" placeholder="Ex.: Concluir;Cancelar"></textarea>
          </div>
          <div class="moz-field">
            <label>Mapa de Botões</label>
            <textarea rows="3" id="ie-buttons-map" placeholder="Concluir:Flow_OK&#10;Cancelar:Flow_Cancel"></textarea>
          </div>
        </div>
      </section>

      ${implCard}
      ${extraCard}

      <!-- CONTINUAÇÕES ASSÍNCRONAS -->
      <section class="moz-card collapsed">
        <button class="moz-card-header" type="button">
          <span>Continuações assíncronas</span>
          <span class="moz-chevron">▸</span>
        </button>
        <div class="moz-card-body">
          <div class="moz-field">
            <label>Modo</label>
            <select id="ie-async-mode">
              <option value="none">Nenhum</option>
              <option value="before">Antes</option>
              <option value="after">Depois</option>
            </select>
          </div>
        </div>
      </section>

      <!-- ENTRADAS (INPUTS) -->
      <section class="moz-card collapsed">
        <button class="moz-card-header" type="button">
          <span>Entradas (Inputs)</span>
          <span class="moz-chevron">▸</span>
        </button>
        <div class="moz-card-body">
          <div id="ie-inputs"></div>
          <button type="button" class="btn btn-xs" id="ie-add-input">Adicionar entrada</button>
          <p style="font-size:11px;color:#6b7280;margin-top:6px;">
            Sugestão: comece a digitar "${'${}'}" para criar uma expressão.
          </p>
        </div>
      </section>

      <!-- SAÍDAS (OUTPUTS) -->
      <section class="moz-card collapsed">
        <button class="moz-card-header" type="button">
          <span>Saídas (Outputs)</span>
          <span class="moz-chevron">▸</span>
        </button>
        <div class="moz-card-body">
          <div id="ie-outputs"></div>
          <button type="button" class="btn btn-xs" id="ie-add-output">Adicionar saída</button>
          <p style="font-size:11px;color:#6b7280;margin-top:6px;">
            Sugestão: comece a digitar "${'${}'}" para criar uma expressão.
          </p>
        </div>
      </section>

      <!-- EXECUTION LISTENERS -->
      <section class="moz-card collapsed">
        <button class="moz-card-header" type="button">
          <span>Ouvintes de Execução (Execution Listeners)</span>
          <span class="moz-chevron">▸</span>
        </button>
        <div class="moz-card-body">
          <div id="ie-listeners"></div>
          <button type="button" class="btn btn-xs" id="ie-add-listener">Adicionar ouvinte</button>
        </div>
      </section>

      <!-- EXECUTION PROPERTIES -->
      <section class="moz-card collapsed">
        <button class="moz-card-header" type="button">
          <span>Propriedades de Execução</span>
          <span class="moz-chevron">▸</span>
        </button>
        <div class="moz-card-body">
          <div id="ie-exec-props"></div>
          <button type="button" class="btn btn-xs" id="ie-add-exec-prop">Adicionar propriedade</button>
        </div>
      </section>

      <!-- EXTENSION PROPERTIES -->
      <section class="moz-card collapsed">
        <button class="moz-card-header" type="button">
          <span>Propriedades de Extensão</span>
          <span class="moz-chevron">▸</span>
        </button>
        <div class="moz-card-body">
          <div id="ie-ext-props"></div>
          <button type="button" class="btn btn-xs" id="ie-add-ext-prop">Adicionar propriedade</button>
        </div>
      </section>

      <!-- FIELD INJECTIONS -->
      <section class="moz-card collapsed">
        <button class="moz-card-header" type="button">
          <span>Injeção de Campos (Field injections)</span>
          <span class="moz-chevron">▸</span>
        </button>
        <div class="moz-card-body">
          <div id="ie-fields"></div>
          <button type="button" class="btn btn-xs" id="ie-add-field">Adicionar campo</button>
        </div>
      </section>

      <!-- CONECTORES -->
      <section class="moz-card collapsed">
        <button class="moz-card-header" type="button">
          <span>Conectores</span>
          <span class="moz-chevron">▸</span>
        </button>
        <div class="moz-card-body">
          <div class="moz-field">
            <label>Configuração</label>
            <textarea rows="3" id="ie-connectors"></textarea>
          </div>
        </div>
      </section>

      <!-- XML -->
      <section class="moz-card collapsed">
        <button class="moz-card-header" type="button">
          <span>XML</span>
          <span class="moz-chevron">▸</span>
        </button>
        <div class="moz-card-body">
          <div class="moz-field">
            <label>XML (visual)</label>
            <textarea rows="6" id="ie-xml" placeholder="Depois puxamos do moddle / saveXML()"></textarea>
          </div>
        </div>
      </section>

      <!-- JSON AVANÇADO -->
      <section class="moz-card collapsed">
        <button class="moz-card-header" type="button">
          <span>Config Mozart (JSON avançado)</span>
          <span class="moz-chevron">▸</span>
        </button>
        <div class="moz-card-body">
          <div class="moz-field">
            <label>mozart:config</label>
            <textarea rows="8" id="ie-json">${JSON.stringify(cfg, null, 2)}</textarea>
          </div>
          <button type="button" class="btn btn-xs" id="ie-json-apply">Aplicar JSON no elemento</button>
        </div>
      </section>

    </div>
  `;

  // colapsáveis
  initMozartCollapsibles(body);

  // ---- mini helpers (visual) ----
  const addRow = (containerId, html) => {
    const c = body.querySelector(containerId);
    if (!c) return;
    const wrap = document.createElement('div');
    wrap.innerHTML = html.trim();
    const el = wrap.firstElementChild;
    c.appendChild(el);
    const rm = el.querySelector('[data-remove]');
    if (rm) rm.onclick = () => el.remove();
  };

  const inputRow = () => `
    <div class="moz-card" style="margin-top:8px;">
      <div class="moz-card-body">
        <div class="moz-row-2">
          <div class="moz-field">
            <label>Local variable name</label>
            <input type="text" placeholder="ex: varName">
          </div>
          <div class="moz-field">
            <label>Tipo</label>
            <select>
              <option value="list">Lista</option>
              <option value="map">Mapa</option>
              <option value="script">Script</option>
              <option value="string">String</option>
              <option value="expression">Expressão</option>
            </select>
          </div>
        </div>
        <div class="moz-field">
          <label>Valor (Value)</label>
          <input type="text" placeholder="Sugestão: ${'${}'}">
        </div>
        <button type="button" class="btn btn-xs" data-remove>Remover</button>
      </div>
    </div>
  `;

  const kvRow = (titleEmpty=true) => `
    <div class="moz-card" style="margin-top:8px;">
      <div class="moz-card-body">
        ${titleEmpty ? '<div style="font-size:11px;color:#6b7280;margin-bottom:6px;">&lt;empty&gt;</div>' : ''}
        <div class="moz-row-2">
          <div class="moz-field">
            <label>Nome</label>
            <input type="text">
          </div>
          <div class="moz-field">
            <label>Valor</label>
            <input type="text">
          </div>
        </div>
        <button type="button" class="btn btn-xs" data-remove>Remover</button>
      </div>
    </div>
  `;

  const listenerRow = () => `
    <div class="moz-card" style="margin-top:8px;">
      <div class="moz-card-body">
        <div style="font-size:11px;color:#6b7280;margin-bottom:6px;">Iniciar Classe</div>
        <div class="moz-field">
          <label>Tipo de evento</label>
          <select>
            <option value="start">Inicio</option>
            <option value="end">Fim</option>
          </select>
        </div>
        <div class="moz-field">
          <label>Tipo</label>
          <select>
            <option value="javaClass">Classe Java</option>
            <option value="expression">Expressão</option>
            <option value="delegateExpression">Expressão Delegada</option>
            <option value="script">Script</option>
          </select>
        </div>
        <div class="moz-field">
          <label>Classe java</label>
          <input type="text" placeholder="com.exemplo.MeuListener">
        </div>
        <button type="button" class="btn btn-xs" data-remove>Remover</button>
      </div>
    </div>
  `;

  const fieldRow = () => `
    <div class="moz-card" style="margin-top:8px;">
      <div class="moz-card-body">
        <div style="font-size:11px;color:#6b7280;margin-bottom:6px;">&lt;empty&gt;</div>
        <div class="moz-row-2">
          <div class="moz-field">
            <label>Name</label>
            <input type="text">
          </div>
          <div class="moz-field">
            <label>Type</label>
            <select>
              <option value="string">String</option>
              <option value="expression">Expression</option>
            </select>
          </div>
        </div>
        <div class="moz-field">
          <label>Value</label>
          <input type="text" placeholder="ex: ${'${minhaVar}'}">
        </div>
        <button type="button" class="btn btn-xs" data-remove>Remover</button>
      </div>
    </div>
  `;

  // botões add (inputs/outputs/etc.)
  const bIn  = body.querySelector('#ie-add-input');
  const bOut = body.querySelector('#ie-add-output');
  const bLis = body.querySelector('#ie-add-listener');
  const bEP  = body.querySelector('#ie-add-exec-prop');
  const bXP  = body.querySelector('#ie-add-ext-prop');
  const bFld = body.querySelector('#ie-add-field');

  if (bIn)  bIn.onclick  = () => addRow('#ie-inputs',  inputRow());
  if (bOut) bOut.onclick = () => addRow('#ie-outputs', inputRow());
  if (bLis) bLis.onclick = () => addRow('#ie-listeners', listenerRow());
  if (bEP)  bEP.onclick  = () => addRow('#ie-exec-props', kvRow(true));
  if (bXP)  bXP.onclick  = () => addRow('#ie-ext-props',  kvRow(true));
  if (bFld) bFld.onclick = () => addRow('#ie-fields',     fieldRow());

  // 1 linha inicial em Inputs/Outputs (pra ficar bonito)
  addRow('#ie-inputs', inputRow());
  addRow('#ie-outputs', inputRow());

  // implementation fields (somente message throw)
  const implType = body.querySelector('#ie-impl-type');
  const implBox  = body.querySelector('#ie-impl-fields');
  if (implType && implBox) {
    const renderImpl = () => {
      const v = implType.value;
      let h = '';
      if (v === 'external') {
        h = `
          <div class="moz-field">
            <label>Endpoint / URL</label>
            <input type="text" placeholder="https://...">
          </div>
        `;
      } else if (v === 'javaClass') {
        h = `<div class="moz-field"><label>Classe Java</label><input type="text" placeholder="com.exemplo.Classe"></div>`;
      } else if (v === 'expression') {
        h = `<div class="moz-field"><label>Expressão</label><input type="text" placeholder="${'${minhaExpressao}'}"></div>`;
      } else if (v === 'delegateExpression') {
        h = `<div class="moz-field"><label>Expressão delegada</label><input type="text" placeholder="${'${meuDelegate}'}"></div>`;
      } else if (v === 'connector') {
        h = `<div class="moz-field"><label>Conector</label><input type="text" placeholder="idDoConector"></div>`;
      } else {
        h = `<small style="font-size:11px;color:#6b7280;">Nenhuma implementação.</small>`;
      }
      implBox.innerHTML = h;
    };
    implType.onchange = renderImpl;
    renderImpl();
  }

  // atualizar nome no diagrama (igual start)
  const nameInput = body.querySelector('#ie-name');
  if (nameInput) {
    nameInput.addEventListener('change', () => {
      const novoNome = nameInput.value;
      modeling.updateProperties(element, { name: novoNome });

      const cfgAtual = getMozartConfigFromBO(bo) || {};
      if (!cfgAtual.geral) cfgAtual.geral = {};
      cfgAtual.geral.nome = novoNome;
      saveMozartConfig(modeler, element, cfgAtual);
    });
  }

  // aplicar JSON manual
  const jsonBtn  = body.querySelector('#ie-json-apply');
  const jsonArea = body.querySelector('#ie-json');
  if (jsonBtn && jsonArea) {
    jsonBtn.addEventListener('click', () => {
      try {
        const novoCfg = JSON.parse(jsonArea.value || '{}');
        saveMozartConfig(modeler, element, novoCfg);
        if (novoCfg.geral && novoCfg.geral.nome) {
          modeling.updateProperties(element, { name: novoCfg.geral.nome });
        }
        renderMozartPanel(modeler, element);
        alert('Config Mozart aplicada ao Intermediate Event.');
      } catch (e) {
        console.error(e);
        alert('JSON inválido. Verifique a sintaxe.');
      }
    });
  }

  return;
}
// fim dos intermediate event

// ================== GATEWAYS + DATA REFERENCES (visual apenas) ==================
if (
  bo.$type === 'bpmn:ExclusiveGateway' ||
  bo.$type === 'bpmn:InclusiveGateway' ||
  bo.$type === 'bpmn:ComplexGateway' ||
  bo.$type === 'bpmn:EventBasedGateway' ||
  bo.$type === 'bpmn:DataStoreReference' ||
  bo.$type === 'bpmn:DataObjectReference'
) {

  const modeling = modeler.get('modeling');
  const isGateway =
    bo.$type === 'bpmn:ExclusiveGateway' ||
    bo.$type === 'bpmn:InclusiveGateway' ||
    bo.$type === 'bpmn:ComplexGateway' ||
    bo.$type === 'bpmn:EventBasedGateway';

  const isDataRef =
    bo.$type === 'bpmn:DataStoreReference' ||
    bo.$type === 'bpmn:DataObjectReference';

  const typeLabelMap = {
    'bpmn:ExclusiveGateway':   'Exclusive Gateway',
    'bpmn:InclusiveGateway':   'Inclusive Gateway',
    'bpmn:ComplexGateway':     'Complex Gateway',
    'bpmn:EventBasedGateway':  'Event Based Gateway',
    'bpmn:DataStoreReference': 'Data Store Reference',
    'bpmn:DataObjectReference':'Data Object Reference'
  };

  const label = typeLabelMap[bo.$type] || 'Elemento';

  title.textContent = label;
  subtitle.textContent = `${label} — ID: ${bo.id || '(sem id)'}`;

  // --- mini helpers (visual) ---
  const escapeHtml = (s) => String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  const addRow = (containerSel, html) => {
    const c = body.querySelector(containerSel);
    if (!c) return;
    const wrap = document.createElement('div');
    wrap.innerHTML = html.trim();
    const el = wrap.firstElementChild;
    c.appendChild(el);
    const rm = el.querySelector('[data-remove]');
    if (rm) rm.onclick = () => el.remove();
  };

  const listenerRow = () => `
    <div class="moz-card" style="margin-top:8px;">
      <div class="moz-card-body">
        <div style="font-size:11px;color:#6b7280;margin-bottom:6px;">Iniciar Classe</div>
        <div class="moz-field">
          <label>Tipo de evento</label>
          <select>
            <option value="start">Inicio</option>
            <option value="end">Fim</option>
          </select>
        </div>
        <div class="moz-field">
          <label>Tipo</label>
          <select>
            <option value="javaClass">Classe Java</option>
            <option value="expression">Expressão</option>
            <option value="delegateExpression">Expressão Delegada</option>
            <option value="script">Script</option>
          </select>
        </div>
        <div class="moz-field">
          <label>Classe java</label>
          <input type="text" placeholder="com.exemplo.MeuListener">
        </div>
        <button type="button" class="btn btn-xs" data-remove>Remover</button>
      </div>
    </div>
  `;

  const kvRowEmptyTitle = () => `
    <div class="moz-card" style="margin-top:8px;">
      <div class="moz-card-body">
        <div style="font-size:11px;color:#6b7280;margin-bottom:6px;">&lt;empty&gt;</div>
        <div class="moz-row-2">
          <div class="moz-field">
            <label>Nome</label>
            <input type="text">
          </div>
          <div class="moz-field">
            <label>Valor</label>
            <input type="text">
          </div>
        </div>
        <button type="button" class="btn btn-xs" data-remove>Remover</button>
      </div>
    </div>
  `;

  // Card Templates (gateway)
  const templatesCard = isGateway ? `
    <section class="moz-card collapsed">
      <button class="moz-card-header" type="button">
        <span>Templates</span>
        <span class="moz-chevron">▸</span>
      </button>
      <div class="moz-card-body">
        <div class="moz-field">
          <label>Template</label>
          <select id="gw-template">
            <option value="">— selecionar —</option>
            <option value="expr" selected>Condição por expressão</option>
            <option value="rules">Decisão por regras</option>
            <option value="default">Fluxo padrão</option>
          </select>
          <small style="font-size:11px;color:#6b7280;">(depois ligamos nos element templates de verdade)</small>
        </div>
      </div>
    </section>
  ` : '';

  // Card Formulário (somente gateways)
  const formCard = isGateway ? `
    <section class="moz-card collapsed">
      <button class="moz-card-header" type="button">
        <span>Formulário</span>
        <span class="moz-chevron">▸</span>
      </button>
      <div class="moz-card-body">
        <div class="moz-field">
          <label>Formulário</label>
          <select id="gw-form">
            <option value="">Selecione um formulário...</option>
          </select>
        </div>
        <div class="moz-field">
          <label>Modo</label>
          <select id="gw-form-mode">
            <option value="view">Visualizar</option>
            <option value="edit">Editar</option>
            <option value="readonly">Somente leitura</option>
          </select>
        </div>
      </div>
    </section>
  ` : '';

  // Card Botões & Fluxos (somente gateways)
  const buttonsCard = isGateway ? `
    <section class="moz-card collapsed">
      <button class="moz-card-header" type="button">
        <span>Botões &amp; Fluxos</span>
        <span class="moz-chevron">▸</span>
      </button>
      <div class="moz-card-body">
        <div class="moz-field">
          <label>Botões</label>
          <textarea rows="3" id="gw-buttons" placeholder="Ex.: Aprovar;Reprovar;Cancelar"></textarea>
        </div>
        <div class="moz-field">
          <label>Mapa de Botões</label>
          <textarea rows="3" id="gw-buttons-map" placeholder="Aprovar:Flow_OK&#10;Reprovar:Flow_NO"></textarea>
        </div>
      </div>
    </section>
  ` : '';

  // Card Async (somente gateways)
  const asyncCard = isGateway ? `
    <section class="moz-card collapsed">
      <button class="moz-card-header" type="button">
        <span>Continuações assíncronas</span>
        <span class="moz-chevron">▸</span>
      </button>
      <div class="moz-card-body">
        <div class="moz-field">
          <label>Modo</label>
          <select id="gw-async-mode">
            <option value="none">Nenhum</option>
            <option value="before">Antes</option>
            <option value="after">Depois</option>
          </select>
        </div>
      </div>
    </section>
  ` : '';

  // Card Execution listeners (gateways)
  const listenersCard = isGateway ? `
    <section class="moz-card collapsed">
      <button class="moz-card-header" type="button">
        <span>Ouvintes de Execução (Execution Listeners)</span>
        <span class="moz-chevron">▸</span>
      </button>
      <div class="moz-card-body">
        <div id="gw-listeners"></div>
        <button type="button" class="btn btn-xs" id="gw-add-listener">Adicionar ouvinte</button>
      </div>
    </section>
  ` : '';

  // Card Exec props (gateways)
  const execPropsCard = isGateway ? `
    <section class="moz-card collapsed">
      <button class="moz-card-header" type="button">
        <span>Propriedades de Execução (Execution properties)</span>
        <span class="moz-chevron">▸</span>
      </button>
      <div class="moz-card-body">
        <div id="gw-exec-props"></div>
        <button type="button" class="btn btn-xs" id="gw-add-exec-prop">Adicionar propriedade</button>
      </div>
    </section>
  ` : '';

  // Card Ext props (gateways e data refs)
  const extPropsCard = `
    <section class="moz-card collapsed">
      <button class="moz-card-header" type="button">
        <span>Propriedades de Extensão (Extension properties)</span>
        <span class="moz-chevron">▸</span>
      </button>
      <div class="moz-card-body">
        <div id="${isGateway ? 'gw-ext-props' : 'dr-ext-props'}"></div>
        <button type="button" class="btn btn-xs" id="${isGateway ? 'gw-add-ext-prop' : 'dr-add-ext-prop'}">
          Adicionar propriedade
        </button>
      </div>
    </section>
  `;

  // Card SLA & Visibilidade (gateways e data refs)
  const slaCard = `
    <section class="moz-card collapsed">
      <button class="moz-card-header" type="button">
        <span>SLA &amp; Visibilidade</span>
        <span class="moz-chevron">▸</span>
      </button>
      <div class="moz-card-body">
        <div class="moz-row-2">
          <div class="moz-field">
            <label>Prazo (horas)</label>
            <input type="number" min="0" id="${isGateway ? 'gw-sla-hours' : 'dr-sla-hours'}">
          </div>
          <div class="moz-field">
            <label>Escalonar para</label>
            <select id="${isGateway ? 'gw-escalate-type' : 'dr-escalate-type'}">
              <option value="none">Nenhum</option>
              <option value="group">Grupo</option>
              <option value="user">Usuário</option>
            </select>
          </div>
        </div>
        <div class="moz-field">
          <label>Destino escalonamento</label>
          <select id="${isGateway ? 'gw-escalate-ref' : 'dr-escalate-ref'}">
            <option value="">Selecione...</option>
          </select>
        </div>
        <div class="moz-field">
          <label>Perfis / grupos com acesso</label>
          <select id="${isGateway ? 'gw-visible-to' : 'dr-visible-to'}" multiple size="4">
            <option value="compras">compras</option>
            <option value="ti">ti</option>
            <option value="rh">rh</option>
            <option value="diretoria">diretoria</option>
          </select>
        </div>
        <div class="moz-field">
          <label>Permitir reabrir?</label>
          <select id="${isGateway ? 'gw-reopen' : 'dr-reopen'}">
            <option value="0">Não</option>
            <option value="1">Sim</option>
          </select>
        </div>
      </div>
    </section>
  `;

  // montar UI
  body.innerHTML = `
    <div class="moz-panel moz-panel-b">

      <!-- GERAL -->
      <section class="moz-card expanded">
        <button class="moz-card-header" type="button">
          <span>Geral</span>
          <span class="moz-chevron">▾</span>
        </button>
        <div class="moz-card-body">
          <div class="moz-field">
            <label>Name</label>
            <input type="text" id="${isGateway ? 'gw-name' : 'dr-name'}" value="${escapeHtml(bo.name || '')}">
          </div>
          <div class="moz-field">
            <label>ID (criado pelo sistema)</label>
            <input type="text" id="${isGateway ? 'gw-id' : 'dr-id'}" value="${escapeHtml(bo.id || '')}" readonly>
          </div>
        </div>
      </section>

      <!-- DOCUMENTAÇÃO -->
      <section class="moz-card collapsed">
        <button class="moz-card-header" type="button">
          <span>Documentação</span>
          <span class="moz-chevron">▸</span>
        </button>
        <div class="moz-card-body">
          <div class="moz-field">
            <label>Element documentation</label>
            <textarea rows="2" id="${isGateway ? 'gw-doc' : 'dr-doc'}"></textarea>
          </div>
          <div class="moz-field">
            <label>Descrição / instruções</label>
            <textarea rows="2" id="${isGateway ? 'gw-desc' : 'dr-desc'}"></textarea>
          </div>
        </div>
      </section>

      ${formCard}
      ${slaCard}
      ${buttonsCard}
      ${templatesCard}
      ${asyncCard}
      ${listenersCard}
      ${execPropsCard}
      ${extPropsCard}

      <!-- CONECTORES -->
      <section class="moz-card collapsed">
        <button class="moz-card-header" type="button">
          <span>Conectores</span>
          <span class="moz-chevron">▸</span>
        </button>
        <div class="moz-card-body">
          <div class="moz-field">
            <label>Configuração</label>
            <textarea rows="3" id="${isGateway ? 'gw-connectors' : 'dr-connectors'}"></textarea>
          </div>
        </div>
      </section>

      <!-- XML -->
      <section class="moz-card collapsed">
        <button class="moz-card-header" type="button">
          <span>XML</span>
          <span class="moz-chevron">▸</span>
        </button>
        <div class="moz-card-body">
          <div class="moz-field">
            <label>XML (visual)</label>
            <textarea rows="6" id="${isGateway ? 'gw-xml' : 'dr-xml'}" placeholder="Depois puxamos do moddle / saveXML()"></textarea>
          </div>
        </div>
      </section>

    </div>
  `;

  initMozartCollapsibles(body);
  bindGatewayTemplateBridge(body);

  // Atualizar name no diagrama (mesma lógica dos outros)
  const nameInput = body.querySelector(isGateway ? '#gw-name' : '#dr-name');
  if (nameInput) {
    nameInput.addEventListener('change', () => {
      modeling.updateProperties(element, { name: nameInput.value });
    });
  }

  // dinamizar listas (somente gateways)
  if (isGateway) {
    const bLis = body.querySelector('#gw-add-listener');
    const bEP  = body.querySelector('#gw-add-exec-prop');
    const bXP  = body.querySelector('#gw-add-ext-prop');

    if (bLis) bLis.onclick = () => addRow('#gw-listeners', listenerRow());
    if (bEP)  bEP.onclick  = () => addRow('#gw-exec-props', kvRowEmptyTitle());
    if (bXP)  bXP.onclick  = () => addRow('#gw-ext-props',  kvRowEmptyTitle());

    // 1 linha inicial p/ ficar bonito
    addRow('#gw-listeners', listenerRow());
    addRow('#gw-exec-props', kvRowEmptyTitle());
    addRow('#gw-ext-props',  kvRowEmptyTitle());
  } else {
    // data refs: extension props apenas
    const bXP = body.querySelector('#dr-add-ext-prop');
    if (bXP) bXP.onclick = () => addRow('#dr-ext-props', kvRowEmptyTitle());
    addRow('#dr-ext-props', kvRowEmptyTitle());
  }

  return;
}
// ================== /GATEWAYS + DATA REFERENCES ==================


    // ---------- QUALQUER OUTRO ELEMENTO: painel genérico antigo ----------
    title.textContent = bo.name || '(sem nome)';
    subtitle.textContent = `${t.label} — ID: ${bo.id || '(sem id)'}`;

    let html = '';
    html += `
      <div class="mozart-field">
        <label>Nome</label>
        <input id="mz-nome" type="text" value="${(bo.name || '').replace(/"/g,'&quot;')}">
      </div>
      <div class="mozart-field">
        <label>ID (somente leitura)</label>
        <input type="text" value="${(bo.id || '')}" readonly>
      </div>
      <div class="mozart-field">
        <label>Tipo Mozart</label>
        <input type="text" value="${cfg.tipo || t.grupo || 'generico'} / ${cfg.subtipo || t.subtipo || ''}" readonly>
      </div>
      <div class="moz-section-title">Config Mozart (JSON avançado)</div>
      <div class="mozart-field">
        <label>JSON</label>
        <textarea id="mz-json" rows="10">${JSON.stringify(cfg, null, 2)}</textarea>
      </div>
      <button type="button" class="btn btn-xs" id="mz-json-apply">Aplicar JSON no elemento</button>
    `;

    body.innerHTML = html;

    const nomeInput = $('#mz-nome', body);
    if (nomeInput) {
      nomeInput.addEventListener('change', () => {
        const novoNome = nomeInput.value;
        modeling.updateProperties(element, { name: novoNome });
        const cfgAtual = getMozartConfigFromBO(bo) || {};
        if (!cfgAtual.geral) cfgAtual.geral = {};
        cfgAtual.geral.nome = novoNome;
        saveMozartConfig(modeler, element, cfgAtual);
        renderMozartPanel(modeler, element);
      });
    }

    const jsonApplyBtn = $('#mz-json-apply', body);
    const jsonTextarea = $('#mz-json', body);
    if (jsonApplyBtn && jsonTextarea) {
      jsonApplyBtn.addEventListener('click', () => {
        try {
          const novoCfg = JSON.parse(jsonTextarea.value || '{}');
          saveMozartConfig(modeler, element, novoCfg);
          if (novoCfg.geral && novoCfg.geral.nome) {
            modeling.updateProperties(element, { name: novoCfg.geral.nome });
          }
          renderMozartPanel(modeler, element);
          alert('Config Mozart aplicada ao elemento.');
        } catch (e) {
          console.error(e);
          alert('JSON inválido. Verifique a sintaxe.');
        }
      });
    }
  }


})();
</script>

<?php
// fecha </body></html>
include_once ROOT_PATH . 'system/includes/footer.php';
