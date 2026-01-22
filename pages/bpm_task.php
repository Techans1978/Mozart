<?php
// pages/bpm_task.php — Tarefa BPM (visão usuário): abrir formulário e concluir com ação
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config.php';
if (session_status()===PHP_SESSION_NONE) session_start();
require_once ROOT_PATH . '/system/config/autenticacao.php';
require_once ROOT_PATH . '/system/config/connect.php';
proteger_pagina();

if (!function_exists('h')) {
  function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}

$taskId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($taskId<=0) { http_response_code(400); die('Tarefa inválida.'); }

include_once ROOT_PATH . '/system/includes/user_head.php';
?>

<body>
  <?php include_once ROOT_PATH . '/system/includes/user_navbar.php'; ?>

  <main class="feed" aria-live="polite">
    <article class="card">
      <div class="head">
        <div class="title"><span>✅ Tarefa do Processo</span></div>
        <div class="meta"><span class="badge">#<?= (int)$taskId ?></span></div>
      </div>
      <div class="body" id="task_wrap">
        <span class="muted">Carregando...</span>
      </div>
    </article>
  </main>

  <?php include_once ROOT_PATH . '/system/includes/user_navbar_right.php'; ?>
  <?php include_once ROOT_PATH . '/system/includes/user_code_footer.php'; ?>
  <?php include_once ROOT_PATH . '/system/includes/user_footer.php'; ?>

<script>
(function(){
  const TASK_ID = <?= (int)$taskId ?>;
  const wrap = document.getElementById('task_wrap');

  function esc(s){return (''+(s??'')).replace(/[&<>"']/g, m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]))}
  async function fetchJSON(url){ const r = await fetch(url, {credentials:'same-origin'}); return await r.json(); }

  function collectFormPayload(container){
    const payload = {};
    const els = container.querySelectorAll('input[name], select[name], textarea[name]');
    els.forEach(el=>{
      if (!el.name) return;
      const n = el.name;
      if (el.type==='checkbox') {
        payload[n] = !!el.checked;
      } else if (el.type==='radio') {
        if (el.checked) payload[n] = el.value;
      } else {
        payload[n] = el.value;
      }
    });
    return payload;
  }

  async function complete(action){
    const formBox = document.getElementById('form_box');
    const payload = formBox ? collectFormPayload(formBox) : {};
    payload.__action = action; // reservado (engine pode usar em condições)

    const r = await fetch('<?= BASE_URL ?>/modules/bpm/api/task_complete.php',{
      method:'POST',
      credentials:'same-origin',
      headers:{'Content-Type':'application/json'},
      body: JSON.stringify({ id: TASK_ID, payload })
    });
    const d = await r.json();
    if(!d.ok){ alert(d.error||'Erro ao concluir'); return; }
    window.location.href = '<?= BASE_URL ?>/pages/processos.php';
  }

  async function load(){
    const td = await fetchJSON('<?= BASE_URL ?>/modules/bpm/api/task_get.php?id='+encodeURIComponent(TASK_ID));
    if (!td || !td.ok){ wrap.innerHTML = `<span class="muted">${esc(td?.error||'Falha ao carregar tarefa.')}</span>`; return; }
    const t = td.task || {};
    const proc = t.process_name || 'BPM';
    const step = t.step_name || t.node_id || '-';
    const inst = t.instance_id || '-';
    const opened = t.started_at || '';
    const parts = [];
    if (t.assignee_user_id) parts.push('Usuário #'+t.assignee_user_id);
    if (t.candidate_group) parts.push('Grupo: '+t.candidate_group);
    const participantes = parts.length ? parts.join(' · ') : 'Disponível (sem responsável)';

    let formHtml = '';
    if (t.form_slug){
      const fg = await fetchJSON('<?= BASE_URL ?>/modules/bpm/api/form_get.php?slug='+encodeURIComponent(t.form_slug)+'&version='+(t.form_version?encodeURIComponent(t.form_version):''));
      if (fg && fg.ok && fg.form){
        // Preferência: HTML salvo (mais fiel ao designer de Forms)
        if (fg.form.html && String(fg.form.html).trim()!=='') {
          formHtml = fg.form.html;
        } else if (fg.form.json && String(fg.form.json).trim()!=='') {
          // Fallback: mostra o JSON bruto (por enquanto)
          formHtml = `<pre style="white-space:pre-wrap;">${esc(fg.form.json)}</pre>`;
        } else {
          formHtml = `<div class="muted">Form encontrado, mas sem HTML/JSON.</div>`;
        }
      } else {
        formHtml = `<div class="muted">Form não encontrado (${esc(t.form_slug)}).</div>`;
      }
    } else {
      formHtml = `<div class="muted">Esta tarefa não possui formulário configurado.</div>`;
    }

    wrap.innerHTML = `
      <div class="meta" style="display:flex; gap:8px; flex-wrap:wrap; margin-bottom:10px;">
        <span class="chip">Número do BPM: <b>#${esc(inst)}</b></span>
        <span class="chip">Data de abertura: ${esc(opened)}</span>
        <span class="chip">Nome do BPM: <b>${esc(proc)}</b></span>
        <span class="chip">Nome da etapa: <b>${esc(step)}</b></span>
        <span class="chip">Participantes: ${esc(participantes)}</span>
      </div>

      <div id="form_box" class="card" style="border:1px dashed #e5e7eb;">
        <div class="body">${formHtml}</div>
      </div>

      <div class="actions" style="margin-top:10px; display:flex; gap:8px; flex-wrap:wrap;">
        <button class="btn" type="button" onclick="window.__bpm_complete('approve')">Aprovar</button>
        <button class="btn ghost" type="button" onclick="window.__bpm_complete('revise')">Correção</button>
        <button class="btn ghost" type="button" onclick="window.__bpm_complete('reject')">Reprovar</button>
        <a class="btn ghost" href="<?= BASE_URL ?>/modules/bpm/instancia-detalhes.php?id=${encodeURIComponent(inst)}">Detalhes</a>
        <a class="btn ghost" href="<?= BASE_URL ?>/pages/processos.php">Voltar</a>
      </div>

      <div class="muted" style="font-size:12px; margin-top:10px;">
        Observação: os botões gravam <code>__action</code> no payload. No ajuste final ligamos isso aos gateways/decisões do BPM e trocamos os labels pelos do Step 3.
      </div>
    `;
  }

  window.__bpm_complete = complete;
  load();
})();
</script>

</body>
