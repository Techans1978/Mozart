<?php
// modules/bpm/bpm_designer2.php
// Mozart BPM — Designer (bpmn-js puro, sem painel lateral)

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../config.php';
require_once ROOT_PATH . '/system/config/autenticacao.php';
require_once ROOT_PATH . '/system/config/connect.php';

include_once ROOT_PATH . 'system/includes/head.php';

// se seus arquivos de header/style só tiverem CSS/HTML, pode manter:
include_once ROOT_PATH . 'modules/bpm/includes/content_header.php';
include_once ROOT_PATH . 'modules/bpm/includes/content_style.php';

include_once ROOT_PATH . 'system/includes/navbar.php';
?>

<style>
  :root { --toolbar-h:56px; --gap:10px; }
  #page-wrapper { background:#f6f7f9; }
  .shell {
    display:flex;
    flex-direction:column;
    height: calc(100vh - 70px);
  }
  .toolbar {
    height:var(--toolbar-h);
    display:flex;
    gap:8px;
    align-items:center;
    padding:8px 12px;
    background:#fff;
    border:1px solid #e5e7eb;
    border-radius:10px;
    margin-bottom:10px;
  }
  .toolbar h2 {
    font-size:16px;
    margin:0 12px 0 0;
    font-weight:600;
    color:#111827;
  }
  .toolbar .spacer { flex:1; }
  .btn {
    border:1px solid #d1d5db;
    background:#fff;
    padding:8px 12px;
    border-radius:10px;
    cursor:pointer;
    transition:.15s;
    font-weight:600;
  }
  .btn:hover { background:#f3f4f6; }
  .btn.primary { background:#111827; color:#fff; border-color:#111827; }
  .btn.primary:hover { background:#0b1220; }
  input[type="file"] { display:none; }

  .work {
    display:flex;
    gap:var(--gap);
    height: calc(100% - var(--toolbar-h) - 10px);
  }
  #canvas {
    flex:1;
    background:#fff;
    border:1px solid #e5e7eb;
    border-radius:12px;
    overflow:hidden;
    min-height:520px;
  }
  /* não vamos usar painel lateral aqui */
  #properties { display:none; }
</style>

<div id="page-wrapper">
  <div class="container-fluid">
    <div class="row">
      <div class="col-lg-12"><h1 class="page-header"><?= APP_NAME ?></h1></div>
    </div>

    <div class="row">
      <div class="col-lg-12">

        <div class="shell">
          <div class="toolbar">
            <h2>Mozart BPM — Designer (bpmn-js)</h2>

            <button class="btn" id="btnNew">Novo</button>

            <input type="file" id="fileOpen" accept=".bpmn,.xml" />
            <button class="btn" id="btnOpen" title="Ctrl+O">Abrir</button>

            <button class="btn" id="btnSave"    title="Ctrl+S">Salvar</button>
            <button class="btn" id="btnSaveAs"  title="Ctrl+Shift+S">Salvar como…</button>

            <button class="btn" id="btnExportXML">Baixar XML</button>
            <button class="btn" id="btnExportSVG">Baixar SVG</button>

            <div class="spacer"></div>

            <label for="tplSelect" class="mb-0" style="font-size:12px;color:#6b7280;">
              Template Mozart
            </label>
            <select id="tplSelect" class="form-control" style="min-width:220px;margin:0 6px;">
              <option value="">— selecionar —</option>
            </select>
            <button class="btn" id="btnApplyTpl">Aplicar</button>

            <button class="btn primary" id="btnPublish">Publicar</button>
          </div>

          <div class="work">
            <div id="canvas"></div>
            <div id="properties"></div>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>

<?php
// scripts globais (jQuery, bootstrap etc.)
include_once ROOT_PATH . 'system/includes/code_footer.php';

// *** IMPORTANTE: NÃO incluir o content_footer.php aqui,
// porque ele é quem carrega o camunda-platform-modeler ***
?>

<!-- bpmn-js puro (CDN de teste) -->
<script src="https://unpkg.com/bpmn-js@10.1.0/dist/bpmn-modeler.development.js"></script>

<script>
(function() {
  const $ = (sel, ctx=document) => ctx.querySelector(sel);

  // ===================== Templates Mozart (custom) =====================
  // Em vez de usar element-templates, usamos um esquema simples:
  // cada template é um objeto com id/name/appliesTo e uma função apply().
  const MOZART_TEMPLATES = [
    {
      id: 'mozart.usertask.basic',
      name: 'Mozart User Task (básico)',
      appliesTo: 'bpmn:UserTask',
      apply({ element, modeling, moddle }) {
        const bo = element.businessObject;

        const cfg = {
          type: 'userTask',
          mozart: {
            name: bo.name || 'Etapa sem nome',
            description: '',
            category: '',
            assignment: {
              type: 'group',   // user | group | role | profile | dynamic
              value: ''
            },
            executionMode: 'single', // single | parallel | majority | all
            form: {
              id: '',
              mode: 'edit'     // readonly | edit | partial
            },
            buttons: [
              { key: 'concluir', label: 'Concluir', flow: '' }
            ],
            sla: {
              hours: 48,
              escalateTo: ''
            },
            visibility: {
              roles: [],
              canReopen: false
            },
            onCompleteActionId: ''
          }
        };

        const docText = JSON.stringify(cfg, null, 2);

        // cria um bpmn:Documentation com o JSON (substitui o anterior)
        const documentation = [
          moddle.create('bpmn:Documentation', {
            text: docText
          })
        ];

        modeling.updateProperties(element, { documentation });

        alert('Template "Mozart User Task (básico)" aplicado.\nO JSON de configuração foi gravado em Documentation.');
      }
    },
    {
      id: 'mozart.http.get',
      name: 'HTTP Task (GET)',
      appliesTo: 'bpmn:ServiceTask',
      apply({ element, modeling, moddle }) {
        const cfg = {
          type: 'serviceTask',
          action: 'http',
          http: {
            method: 'GET',
            url: 'https://api.exemplo.com/recurso',
            headers: {
              'Accept': 'application/json'
            },
            query: {},
            saveTo: 'processo.httpResult'
          }
        };

        const documentation = [
          moddle.create('bpmn:Documentation', {
            text: JSON.stringify(cfg, null, 2)
          })
        ];

        modeling.updateProperties(element, { documentation });

        alert('Template "HTTP Task (GET)" aplicado.\nConfig salva em Documentation.');
      }
    },
    {
      id: 'mozart.gateway.expr',
      name: 'Gateway de Decisão (expressão)',
      appliesTo: 'bpmn:ExclusiveGateway',
      apply({ element, modeling, moddle }) {
        const cfg = {
          type: 'exclusiveGateway',
          rules: [
            {
              name: 'Regra 1',
              expression: 'processo.total > 1000',
              flowId: '' // id da sequência de saída
            }
          ],
          defaultFlowId: ''
        };

        const documentation = [
          moddle.create('bpmn:Documentation', {
            text: JSON.stringify(cfg, null, 2)
          })
        ];

        modeling.updateProperties(element, { documentation });

        alert('Template "Gateway de Decisão (expressão)" aplicado.\nRegras salvas em Documentation.');
      }
    }
  ];

  function populateTemplateSelect() {
    const sel = $('#tplSelect');
    sel.innerHTML = '<option value="">— selecionar —</option>';
    MOZART_TEMPLATES.forEach(t => {
      const opt = document.createElement('option');
      opt.value = t.id;
      opt.textContent = t.name;
      sel.appendChild(opt);
    });
  }

  // ===================== utilidades =====================
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

  // ====== bpmn-js Modeler ======
  const BpmnModeler = window.BpmnJS || window.BpmnModeler;
  if (!BpmnModeler) {
    alert('bpmn-js não carregou. Verifique o script da CDN.');
    return;
  }

  let modeler = new BpmnModeler({
    container: '#canvas'
  });

  let currentFileName = 'diagram.bpmn';

  async function newDiagram() {
    try {
      await modeler.createDiagram();
      const canvas = modeler.get('canvas');
      canvas.zoom('fit-viewport', 'auto');
    } catch (err) {
      console.error(err);
      alert('Erro ao criar novo diagrama: ' + (err.message || err));
    }
  }

  async function importXML(xml) {
    try {
      await modeler.importXML(xml);
      modeler.get('canvas').zoom('fit-viewport', 'auto');
    } catch (err) {
      console.error(err);
      alert('Falha ao importar BPMN: ' + (err.message || err));
    }
  }

  async function saveDiagram(forceAs) {
    try {
      const { xml } = await modeler.saveXML({ format: true });
      let name = currentFileName;
      if (forceAs) {
        const n = prompt('Nome do arquivo .bpmn:', currentFileName);
        if (!n) return;
        name = n;
      }
      if (!/\.bpmn$/i.test(name)) name += '.bpmn';
      currentFileName = name;
      saveAs(new Blob([xml], { type:'application/xml' }), currentFileName);
    } catch (err) {
      console.error(err);
      alert('Erro ao salvar: ' + (err.message || err));
    }
  }

  async function exportXML() {
    try {
      const { xml } = await modeler.saveXML({ format: true });
      const base = currentFileName.replace(/\.(bpmn|xml)$/i,'') || 'diagram';
      saveAs(new Blob([xml], { type:'application/xml' }), base + '.bpmn');
    } catch (err) {
      console.error(err);
      alert('Erro ao exportar XML: ' + (err.message || err));
    }
  }

  async function exportSVG() {
    try {
      const { svg } = await modeler.saveSVG();
      const base = currentFileName.replace(/\.(bpmn|xml)$/i,'') || 'diagram';
      saveAs(new Blob([svg], { type:'image/svg+xml' }), base + '.svg');
    } catch (err) {
      console.error(err);
      alert('Erro ao exportar SVG: ' + (err.message || err));
    }
  }

  function bindToolbar() {
    bind('btnNew',      () => newDiagram());
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

  function bind(id, fn) {
    const el = document.getElementById(id);
    if (el) el.onclick = fn;
  }

  function bindDnD() {
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

  function bindShortcuts() {
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
    if (!selId) {
      alert('Selecione um template Mozart.');
      return;
    }

    const template = MOZART_TEMPLATES.find(t => t.id === selId);
    if (!template) {
      alert('Template não encontrado.');
      return;
    }

    const selection = modeler.get('selection');
    const element = selection.get()[0];

    if (!element) {
      alert('Selecione um elemento do diagrama para aplicar o template.');
      return;
    }

    const boType = element.businessObject.$type; // ex: 'bpmn:UserTask'
    if (boType !== template.appliesTo) {
      alert('Este template só pode ser aplicado em: ' + template.appliesTo + '\nElemento selecionado: ' + boType);
      return;
    }

    const modeling = modeler.get('modeling');
    const moddle   = modeler.get('moddle');

    try {
      template.apply({ element, modeling, moddle });
    } catch (e) {
      console.error(e);
      alert('Erro ao aplicar template: ' + (e.message || e));
    }
  }

  async function publish() {
    try {
      const { xml } = await modeler.saveXML({ format:true });
      console.log('XML pronto para publicar (%s bytes)', xml.length);
      alert('✔ XML preparado para publicar.\nDepois plugamos aqui o endpoint de publicação.');
    } catch (e) {
      console.error(e);
      alert('Erro ao publicar: ' + (e?.message || e));
    }
  }

  // inicializa
  populateTemplateSelect();
  newDiagram();
  bindToolbar();
  bindDnD();
  bindShortcuts();
})();
</script>

<?php
include_once ROOT_PATH . 'system/includes/footer.php';
