<script type="module">
  import { Form }       from 'https://esm.sh/@bpmn-io/form-js-viewer@1.17.0';
  import { FormEditor } from 'https://esm.sh/@bpmn-io/form-js-editor@1.17.0';

  const BASE_URL = '<?= addslashes(BASE_URL) ?>';
  const $ = (s, c=document)=>c.querySelector(s);

  // ===== Schema base =====
  const DEFAULT_SCHEMA = {
    schemaVersion: 1,
    type: "default",
    components: [
      { type: "textfield", key: "nome",   label: "Nome",  validate: { required: true } },
      {
        type: "textfield",
        key: "email",
        label: "E-mail",
        properties: {
          inputType: "email",
          placeholder: "voce@exemplo.com"
        },
        validate: { required: true }
      },
      {
        type: "select",
        key: "plano",
        label: "Plano",
        values: [
          { label: "Básico",     value: "basic" },
          { label: "Pro",        value: "pro"   },
          { label: "Enterprise", value: "enterprise" }
        ]
      },
      {
        type: "checkbox",
        key: "aceite",
        label: "Aceito os termos",
        validate: { required: true }
      }
    ],
    data: {}
  };

  let currentSchema = structuredClone(DEFAULT_SCHEMA);
  let currentData   = {};
  let currentMode   = 'visual';

  const editor = new FormEditor({ container: $('#editorHost') });
  const viewer = new Form({ container: $('#preview') });

  const formIdInput = document.getElementById('formId');

  // ===== helpers =====
  async function getSchemaFromEditor(){
    // API correta do form-js editor
    const { schema } = await editor.saveSchema();
    return schema;
  }

  async function renderPreview(schema, data){
    const title = (document.getElementById('formTitle')?.value || 'Formulário');

    await viewer.importSchema(schema);

    if (typeof viewer.importData === 'function') {
      await viewer.importData(data || {});
    } else if (typeof viewer.setData === 'function') {
      viewer.setData(data || {});
    }

    const host = document.getElementById('preview');
    let t = host.querySelector('.mozart-title');
    if (!t){
      t = document.createElement('div');
      t.className='mozart-title';
      t.style.cssText='font-weight:600;margin:0 0 8px 0';
      host.prepend(t);
    }
    t.textContent = title;
  }

  function escapeHtml(s){
    return String(s).replace(/[&<>"']/g, m=>({
      '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'
    }[m]));
  }

  const saveAs = (blob, filename) => {
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    setTimeout(()=>{
      URL.revokeObjectURL(a.href);
      a.remove();
    }, 700);
  };

  function safeCopy(text) {
    return navigator.clipboard?.writeText(text).catch(() => {
      const ta = document.createElement('textarea');
      ta.value = text;
      ta.style.position = 'fixed';
      ta.style.left = '-9999px';
      document.body.appendChild(ta);
      ta.select();
      try { document.execCommand('copy'); } finally { ta.remove(); }
    });
  }

  function buildStandaloneHTML(schema) {
    const cssBase = './vendor/form-js@1.17.0/dist/assets'; // relativo à página
    const title = escapeHtml(document.getElementById('formTitle')?.value || 'Formulário');

    return `<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>${title}</title>
<link rel="stylesheet" href="${cssBase}/form-js.css"></head>
<body style="margin:16px;font:14px/1.45 system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial">
<h1 style="font-size:18px;margin:0 0 12px 0">${title}</h1>
<div id="preview"></div>
<script type="module">
  import { Form } from 'https://esm.sh/@bpmn-io/form-js-viewer@1.17.0';
  const schema = ${JSON.stringify(schema, null, 2)};
  const viewer = new Form({ container: document.getElementById('preview') });
  await viewer.importSchema(schema);
  if (typeof viewer.importData==='function') await viewer.importData({});
  else if (typeof viewer.setData==='function') viewer.setData({});
<\/script></body></html>`;
  }

  // ===== Boot =====
  try {
    await editor.importSchema(currentSchema);
    await viewer.importSchema(currentSchema);

    if (typeof viewer.importData === 'function') {
      await viewer.importData(currentData);
    } else if (typeof viewer.setData === 'function') {
      viewer.setData(currentData);
    }

    const fatal = document.getElementById('fatalEditorErr');
    if (fatal) fatal.style.display = 'none';
  } catch (e) {
    console.error('[form-js] erro:', e);
    alert('Falha ao carregar form-js (rede/CDN). Veja o console (F12).');
    const fatal = document.getElementById('fatalEditorErr');
    if (fatal) fatal.style.display = 'flex';
  }

  // Preview submit (apenas debug)
  viewer.on('submit', (ev) => {
    currentData = ev.data || {};
    alert('Dados enviados (preview):\n\n' + JSON.stringify(currentData, null, 2));
  });

  // ===== Alterna modos =====
  const btnVisual = document.getElementById('btnVisual');
  if (btnVisual) {
    btnVisual.onclick = async ()=>{
      if (currentMode==='visual') return;
      try {
        currentSchema = JSON.parse($('#schemaArea').value || '{}');
        await editor.importSchema(currentSchema);
        await renderPreview(currentSchema, currentData);
        $('#editorHost').style.display = '';
        $('#codeHost').style.display   = 'none';
        currentMode = 'visual';
        $('#modeLabel').textContent = 'Modo: Visual';
      } catch {
        alert('JSON inválido.');
      }
    };
  }

  const btnCode = document.getElementById('btnCode');
  if (btnCode) {
    btnCode.onclick = async ()=>{
      currentSchema = await getSchemaFromEditor();
      const area = document.getElementById('schemaArea');
      if (area) area.value = JSON.stringify(currentSchema, null, 2);
      $('#editorHost').style.display = 'none';
      $('#codeHost').style.display   = '';
      currentMode = 'code';
      $('#modeLabel').textContent = 'Modo: Código (JSON)';
    };
  }

  // ===== Toolbar padrão =====
  const btnNew = document.getElementById('btnNew');
  if (btnNew) {
    btnNew.onclick = async ()=>{
      currentSchema = structuredClone(DEFAULT_SCHEMA);
      currentData = {};
      if (currentMode==='visual') {
        await editor.importSchema(currentSchema);
      } else {
        const area = document.getElementById('schemaArea');
        if (area) area.value = JSON.stringify(currentSchema, null, 2);
      }
      await renderPreview(currentSchema, currentData);
      const t = document.getElementById('formTitle');
      if (t) t.value = 'Formulário';
      if (formIdInput) formIdInput.value = '0';
    };
  }

  const btnOpen = document.getElementById('btnOpen');
  const fileOpen = document.getElementById('fileOpen');
  if (btnOpen && fileOpen) {
    btnOpen.onclick = ()=> fileOpen.click();

    fileOpen.addEventListener('change', async (ev)=>{
      const f = ev.target.files?.[0]; if (!f) return;
      try{
        const js = JSON.parse(await f.text());
        currentSchema = js;
        if (currentMode==='visual') {
          await editor.importSchema(currentSchema);
        } else {
          const area = document.getElementById('schemaArea');
          if (area) area.value = JSON.stringify(currentSchema, null, 2);
        }
        await renderPreview(currentSchema, currentData);
      } catch {
        alert('Arquivo JSON inválido.');
      }
      ev.target.value = '';
    });
  }

  const btnSaveJSON = document.getElementById('btnSaveJSON');
  if (btnSaveJSON) {
    btnSaveJSON.onclick = async ()=>{
      if (currentMode==='visual') {
        currentSchema = await getSchemaFromEditor();
      } else {
        try {
          const area = document.getElementById('schemaArea');
          currentSchema = JSON.parse(area?.value || '{}');
        } catch {
          return alert('JSON inválido.');
        }
      }
      const name = (currentSchema.title || 'form') + '.form.json';
      saveAs(new Blob([JSON.stringify(currentSchema, null, 2)], { type:'application/json' }), name);
    };
  }

  const btnRender = document.getElementById('btnRender');
  if (btnRender) {
    btnRender.onclick = async ()=>{
      if (currentMode==='visual') {
        currentSchema = await getSchemaFromEditor();
      } else {
        try {
          const area = document.getElementById('schemaArea');
          currentSchema = JSON.parse(area?.value || '{}');
        } catch {
          return alert('JSON inválido.');
        }
      }
      await renderPreview(currentSchema, currentData);
    };
  }

  const btnCopyJSON = document.getElementById('btnCopyJSON');
  if (btnCopyJSON) {
    btnCopyJSON.onclick = async ()=>{
      const area = document.getElementById('schemaArea');
      const txt = currentMode==='visual'
        ? JSON.stringify(await getSchemaFromEditor(), null, 2)
        : (area?.value || '{}');
      await safeCopy(txt);
    };
  }

  const btnCopyHTML = document.getElementById('btnCopyHTML');
  if (btnCopyHTML) {
    btnCopyHTML.onclick = async ()=>{
      const area = document.getElementById('schemaArea');
      const schema = currentMode==='visual'
        ? await getSchemaFromEditor()
        : JSON.parse(area?.value || '{}');

      const html = buildStandaloneHTML(schema);
      await safeCopy(html);
    };
  }

  const btnSaveHTML = document.getElementById('btnSaveHTML');
  if (btnSaveHTML) {
    btnSaveHTML.onclick = async ()=>{
      const area = document.getElementById('schemaArea');
      const schema = currentMode==='visual'
        ? await getSchemaFromEditor()
        : JSON.parse(area?.value || '{}');

      const html = buildStandaloneHTML(schema);
      const name = (schema.title || 'form') + '.html';
      saveAs(new Blob([html], { type:'text/html' }), name);
    };
  }

  const btnClearData = document.getElementById('btnClearData');
  if (btnClearData) {
    btnClearData.onclick = async ()=>{
      currentData = {};
      if (typeof viewer.importData==='function') {
        await viewer.importData({});
      } else if (typeof viewer.setData==='function') {
        viewer.setData({});
      }
    };
  }

  // ===== Criar por IA (texto) =====
  const btnAI = document.getElementById('btnAI');
  if (btnAI) {
    btnAI.onclick = async () => {
      const prompt = window.prompt('Descreva o formulário que deseja criar:');
      if (!prompt) return;

      try {
        const resp = await fetch(BASE_URL + '/modules/forms/actions/forms_ai_generate.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
          body: 'prompt=' + encodeURIComponent(prompt)
        });
        const data = await resp.json();
        if (data.error) {
          alert('Falha ao gerar formulário por IA: ' + data.error);
          return;
        }

        const schema = data.schema || DEFAULT_SCHEMA;
        currentSchema = schema;

        if (currentMode === 'visual') {
          await editor.importSchema(currentSchema);
        } else {
          const area = document.getElementById('schemaArea');
          if (area) area.value = JSON.stringify(currentSchema, null, 2);
        }

        if (data.title && document.getElementById('formTitle')) {
          document.getElementById('formTitle').value = data.title;
        }

        await renderPreview(currentSchema, {});
      } catch (e) {
        console.error(e);
        alert('Erro ao chamar a IA. Veja o console (F12).');
      }
    };
  }

  // ===== Salvar no banco (moz_forms) =====
  const btnSaveForm = document.getElementById('btnSaveForm');
  if (btnSaveForm) {
    btnSaveForm.onclick = async () => {
      try {
        if (currentMode === 'visual') {
          currentSchema = await getSchemaFromEditor();
        } else {
          const area = document.getElementById('schemaArea');
          currentSchema = JSON.parse(area?.value || '{}');
        }
      } catch {
        alert('JSON inválido. Corrija antes de salvar.');
        return;
      }

      const html  = buildStandaloneHTML(currentSchema);
      const title = document.getElementById('formTitle')?.value || 'Formulário';
      const id    = formIdInput ? parseInt(formIdInput.value || '0', 10) : 0;

      const body = new URLSearchParams();
      body.set('id', id);
      body.set('titulo', title);
      body.set('schema_json', JSON.stringify(currentSchema));
      body.set('html', html);
      body.set('tipo', 'bpm');      // por enquanto fixo; depois podemos deixar dinâmico

      try {
        const resp = await fetch(BASE_URL + '/modules/forms/actions/forms_save.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
          body: body.toString()
        });
        const data = await resp.json();
        if (data.error) {
          alert('Erro ao salvar formulário: ' + data.error);
          return;
        }
        if (data.id && formIdInput) {
          formIdInput.value = data.id;
        }
        alert('Formulário salvo com sucesso.');
      } catch (e) {
        console.error(e);
        alert('Falha ao salvar. Veja o console (F12).');
      }
    };
  }
</script>
