<?php
// modules/bpm/bpmsai_wizard_steps/5.php
if (session_status()===PHP_SESSION_NONE) session_start();
$st = $_SESSION['bpmsai_wizard'] ?? [];


$ai = $st['last_ai_test'] ?? ['status'=>'never','messages'=>[]];
$human = $st['last_human_test'] ?? ['status'=>'never','messages'=>[]];

$errors = $st['validation_errors'] ?? [];
$warns  = $st['validation_warns'] ?? [];
?>

<h3>Testes e Publicação</h3>
<p class="text-muted">Antes de publicar, rode o teste IA e o teste humano (sandbox). Publicar cria uma nova versão.</p>

<div class="row">
  <div class="col-md-6">
    <div class="panel panel-default">
      <div class="panel-heading"><strong>Validação determinística</strong></div>
      <div class="panel-body">
        <?php if(!$errors && !$warns): ?>
          <div class="alert alert-success">Tudo certo ✅</div>
        <?php else: ?>
          <?php if($errors): ?>
            <div class="alert alert-danger">
              <b>Erros:</b>
              <ul><?php foreach($errors as $m): ?><li><?= h($m) ?></li><?php endforeach; ?></ul>
            </div>
          <?php endif; ?>
          <?php if($warns): ?>
            <div class="alert alert-warning">
              <b>Avisos:</b>
              <ul><?php foreach($warns as $m): ?><li><?= h($m) ?></li><?php endforeach; ?></ul>
            </div>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="col-md-6">
    <div class="panel panel-default">
      <div class="panel-heading"><strong>Teste por IA</strong></div>
      <div class="panel-body">
        <p>Status: <code><?= h($ai['status'] ?? 'never') ?></code></p>
        <?php if(!empty($ai['messages'])): ?>
          <ul><?php foreach($ai['messages'] as $m): ?><li><?= h($m) ?></li><?php endforeach; ?></ul>
        <?php else: ?>
          <p class="text-muted">Ainda não executado.</p>
        <?php endif; ?>

        <button class="btn btn-default" type="button" id="btnAiTest">Rodar teste IA</button>
      </div>
    </div>
  </div>
</div>

<div class="panel panel-default">
  <div class="panel-heading"><strong>Teste Humano (Sandbox)</strong></div>
  <div class="panel-body">
    <p>Status: <code><?= h($human['status'] ?? 'never') ?></code></p>
    <?php if(!empty($human['messages'])): ?>
      <ul><?php foreach($human['messages'] as $m): ?><li><?= h($m) ?></li><?php endforeach; ?></ul>
    <?php else: ?>
      <p class="text-muted">Clique para iniciar uma simulação “trocar papel”.</p>
    <?php endif; ?>

    <div class="btn-group">
      <button class="btn btn-default" type="button" id="btnHumanStart">Iniciar Sandbox</button>
      <button class="btn btn-default" type="button" id="btnHumanReset">Resetar Sandbox</button>
    </div>
  </div>
</div>

<form method="post" action="/modules/bpm/bpmsai_wizard_steps/save.php?step=5" onsubmit="return confirm('Confirmar publicação da nova versão?');">
  <div class="text-right">
    <a class="btn btn-default" href="/modules/bpm/bpmsai-wizard.php?step=4">Voltar</a>
    <button class="btn btn-default" type="submit" name="action" value="save_draft">Salvar rascunho</button>
    <button class="btn btn-primary" type="submit" name="action" value="publish">Publicar nova versão</button>
  </div>
</form>

<script>
(async function(){
  const aiBtn = document.getElementById('btnAiTest');
  const hsBtn = document.getElementById('btnHumanStart');
  const hrBtn = document.getElementById('btnHumanReset');

  async function post(url){
    const res = await fetch(url, {method:'POST', headers:{'Content-Type':'application/json'}, body:'{}'});
    return res.json();
  }

  if(aiBtn){
    aiBtn.addEventListener('click', async ()=>{
      const j = await post('/modules/bpm/api/bpmsai_test_ai.php');
      alert(j && j.ok ? 'Teste IA executado. Recarregue a página.' : ('Falhou: '+(j.error||'erro')));
      if(j && j.ok) location.reload();
    });
  }
  if(hsBtn){
    hsBtn.addEventListener('click', async ()=>{
      const j = await post('/modules/bpm/api/bpmsai_test_human_start.php');
      alert(j && j.ok ? 'Sandbox iniciado. (Implementação MVP)' : ('Falhou: '+(j.error||'erro')));
      if(j && j.ok) location.reload();
    });
  }
  if(hrBtn){
    hrBtn.addEventListener('click', async ()=>{
      const j = await post('/modules/bpm/api/bpmsai_test_human_reset.php');
      alert(j && j.ok ? 'Sandbox resetado.' : ('Falhou: '+(j.error||'erro')));
      if(j && j.ok) location.reload();
    });
  }
})();
</script>
