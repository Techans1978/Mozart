<?php
// modules/bpm/bpmsai_wizard_steps/3.php
if (session_status()===PHP_SESSION_NONE) session_start();
$st = $_SESSION['bpmsai_wizard'] ?? [];
$steps = $st['steps'] ?? [];
if (!is_array($steps)) $steps = [];


$prefill = json_encode($steps ?: [[
  'id'=>'abertura','name'=>'Abertura',
  'assignment'=>['type'=>'role','key'=>'analista'],
  'description'=>'Preenche dados e anexa documentos.',
  'actions'=>['submit'=>['label'=>'Enviar']]
],[
  'id'=>'gerente','name'=>'Gerente',
  'assignment'=>['type'=>'role','key'=>'gerente_area'],
  'description'=>'Analisa e decide.',
  'actions'=>[
    'approve'=>['label'=>'Aprovar'],
    'reject'=>['label'=>'Recusar']
  ]
],[
  'id'=>'rh','name'=>'Recursos Humanos',
  'assignment'=>['type'=>'role','key'=>'rh'],
  'description'=>'Analisa e pode pedir ajuste.',
  'actions'=>[
    'approve'=>['label'=>'Aprovar'],
    'reject'=>['label'=>'Recusar'],
    'revise'=>['label'=>'Solicitar ajuste']
  ]
]], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
?>

<form method="post" action="/modules/bpm/bpmsai_wizard_steps/save.php?step=3">
  <h3>Etapas</h3>
  <p class="text-muted">
    MVP sólido: você define as etapas em JSON. No próximo passo, define <b>destinos (idas/voltas)</b> e <b>formulário/campos</b>.
  </p>

  <div class="form-group">
    <label>Etapas (JSON)</label>
    <textarea class="form-control" name="steps_json" id="steps_json" rows="18"
      style="font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono', 'Courier New', monospace;"><?php echo h($prefill); ?></textarea>
    <small class="text-muted">
      Cada etapa deve ter: <code>id</code>, <code>name</code>, <code>assignment{type,key}</code>, <code>actions{...}</code>.
    </small>
  </div>

  <div class="text-right">
    <a class="btn btn-default" href="/modules/bpm/bpmsai-wizard.php?step=2">Voltar</a>
    <button class="btn btn-primary" type="submit">Salvar e avançar</button>
  </div>
</form>

<script>
(function(){
  const form = document.querySelector('form');
  const ta = document.getElementById('steps_json');
  if(!form || !ta) return;

  form.addEventListener('submit', function(ev){
    try{
      const v = JSON.parse(ta.value||'[]');
      if(!Array.isArray(v) || v.length===0) throw new Error('Adicione ao menos 1 etapa.');

      const ids = new Set();
      for(const s of v){
        if(!s.id || !s.name) throw new Error('Cada etapa precisa de id e name.');
        if(ids.has(s.id)) throw new Error('ID repetido: '+s.id);
        ids.add(s.id);

        if(!s.assignment || !s.assignment.type || !s.assignment.key){
          throw new Error('Etapa '+s.id+' precisa de assignment {type,key}.');
        }
        if(!s.actions || typeof s.actions!=='object'){
          throw new Error('Etapa '+s.id+' precisa de actions.');
        }
      }
    } catch(e){
      alert('JSON inválido: ' + (e.message||e));
      ev.preventDefault();
    }
  });
})();
</script>
