<?php
// pages/processos.php — Visão do usuário: Processos (cards) + filtros
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

include_once ROOT_PATH . '/system/includes/user_head.php';
?>

<body>
  <?php include_once ROOT_PATH . '/system/includes/user_navbar.php'; ?>

  <main class="feed" aria-live="polite">
    <div class="card" style="margin-bottom:10px;">
      <div class="head">
        <div class="title"><span>🔄 Processos</span></div>
        <div class="meta"><span class="badge">Visão do usuário</span></div>
      </div>
      <div class="body">
        <div class="muted" style="margin-bottom:10px;">Filtros rápidos (não mudam o layout do Feed).</div>

        <div style="display:grid; grid-template-columns: 1.2fr 1fr 1fr; gap:10px;">
          <div>
            <label class="muted" style="font-size:12px;">Nome do BPM</label>
            <input id="f_q" class="input" placeholder="Ex: Aprovação de compras">
          </div>
          <div>
            <label class="muted" style="font-size:12px;">Participantes (texto)</label>
            <input id="f_part" class="input" placeholder="Ex: Gerentes, RH, Mayara">
          </div>
          <div>
            <label class="muted" style="font-size:12px;">Status</label>
            <select id="f_status" class="input">
              <option value="open">Abertos</option>
              <option value="completed">Concluídos</option>
              <option value="all">Todos</option>
            </select>
          </div>
        </div>

        <div style="display:grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap:10px; margin-top:10px;">
          <div>
            <label class="muted" style="font-size:12px;">Data início</label>
            <input id="f_dtini" class="input" type="date">
          </div>
          <div>
            <label class="muted" style="font-size:12px;">Data fim</label>
            <input id="f_dtfim" class="input" type="date">
          </div>
          <div>
            <label class="muted" style="font-size:12px;">Categoria (texto)</label>
            <input id="f_cat" class="input" placeholder="Ex: Financeiro">
          </div>
          <div style="display:flex; align-items:flex-end; gap:8px;">
            <button class="btn" id="btn_apply">Aplicar</button>
            <button class="btn ghost" id="btn_clear">Limpar</button>
          </div>
        </div>

        <div class="muted" style="font-size:12px; margin-top:10px;">
          * Aprovados/Reprovados/Alteração ficam para o ajuste final (depende do payload do botão e auditoria do fluxo).
        </div>
      </div>
    </div>

    <div id="proc_list"></div>

    <div id="more_wrap" style="text-align:center;margin:10px 0; display:none;">
      <button class="btn" id="btn_more">Carregar mais</button>
    </div>
  </main>

  <?php include_once ROOT_PATH . '/system/includes/user_navbar_right.php'; ?>
  <?php include_once ROOT_PATH . '/system/includes/user_code_footer.php'; ?>
  <?php include_once ROOT_PATH . '/system/includes/user_footer.php'; ?>

  <script>
  (function(){
    const listEl = document.getElementById('proc_list');
    const moreWrap = document.getElementById('more_wrap');
    const btnMore = document.getElementById('btn_more');

    let offset = 0;
    const limit = 20;

    function esc(s){return (''+(s??'')).replace(/[&<>"']/g, m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]))}

    function params(){
      const p = new URLSearchParams();
      p.set('limit', String(limit));
      p.set('offset', String(offset));
      const q = document.getElementById('f_q').value.trim();
      const part = document.getElementById('f_part').value.trim();
      const st = document.getElementById('f_status').value;
      const dtini = document.getElementById('f_dtini').value;
      const dtfim = document.getElementById('f_dtfim').value;
      const cat = document.getElementById('f_cat').value.trim();
      if (q) p.set('q', q);
      if (part) p.set('participant', part);
      if (st) p.set('status', st);
      if (dtini) p.set('dt_ini', dtini);
      if (dtfim) p.set('dt_fim', dtfim);
      if (cat) p.set('cat', cat);
      return p;
    }

    function cardHtml(it){
      const procName = it.process_name || 'BPM';
      const stepName = it.step_name || it.node_id || '-';
      const instId = it.instance_id;
      const started = it.started_at || '';
      const parts = [];
      if (it.assignee_user_id) parts.push('Usuário #'+it.assignee_user_id);
      if (it.candidate_group) parts.push('Grupo: '+it.candidate_group);
      const participantes = parts.length ? parts.join(' · ') : 'Disponível (sem responsável)';

      const badge = (it.task_status==='completed') ? '<span class="badge ok">Concluído</span>'
                   : (it.task_status==='error') ? '<span class="badge warn">Erro</span>'
                   : '<span class="badge">Aberto</span>';

      const link = '<?= BASE_URL ?>/pages/bpm_task.php?id='+encodeURIComponent(it.task_id);

      return `
        <article class="card">
          <div class="head">
            <div class="title"><span>🔄 ${esc(procName)}</span></div>
            <div class="meta">${badge}</div>
          </div>
          <div class="body">
            <div class="meta" style="display:flex; gap:8px; flex-wrap:wrap;">
              <span class="chip">Número: <b>#${esc(instId)}</b></span>
              <span class="chip">Abertura: ${esc(started)}</span>
              <span class="chip">Etapa: <b>${esc(stepName)}</b></span>
              <span class="chip">Participantes: ${esc(participantes)}</span>
            </div>
            <div class="actions" style="margin-top:10px;">
              <a class="btn" href="${link}">Abrir</a>
              <a class="btn ghost" href="<?= BASE_URL ?>/modules/bpm/instancia-detalhes.php?id=${encodeURIComponent(instId)}">Detalhes</a>
            </div>
          </div>
        </article>
      `;
    }

    async function fetchJSON(url){
      const r = await fetch(url, {credentials:'same-origin'});
      return await r.json();
    }

    async function load(reset){
      if (reset){ offset = 0; listEl.innerHTML = ''; }
      const url = '<?= BASE_URL ?>/modules/bpm/api/user_process_feed.php?' + params().toString();
      listEl.insertAdjacentHTML('beforeend', `<article class="card"><div class="body"><span class="muted">Carregando...</span></div></article>`);
      const loading = listEl.lastElementChild;

      const d = await fetchJSON(url);
      if (loading) loading.remove();

      if (!d || !d.ok){
        listEl.insertAdjacentHTML('beforeend', `<article class="card"><div class="body"><span class="muted">Falha ao carregar.</span></div></article>`);
        moreWrap.style.display='none';
        return;
      }

      const items = d.items || [];
      if (!items.length && reset){
        listEl.innerHTML = `<article class="card"><div class="body"><span class="muted">Nenhum processo encontrado.</span></div></article>`;
      } else {
        listEl.insertAdjacentHTML('beforeend', items.map(cardHtml).join(''));
      }

      if (d.next_offset!==null){
        moreWrap.style.display='block';
        offset = d.next_offset;
      } else {
        moreWrap.style.display='none';
      }
    }

    document.getElementById('btn_apply').addEventListener('click', ()=>load(true));
    document.getElementById('btn_clear').addEventListener('click', ()=>{
      ['f_q','f_part','f_dtini','f_dtfim','f_cat'].forEach(id=>document.getElementById(id).value='');
      document.getElementById('f_status').value='open';
      load(true);
    });
    btnMore.addEventListener('click', ()=>load(false));

    load(true);
  })();
  </script>

</body>
