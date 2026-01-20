<?php
// modules/bpm/bpmsai_wizard_steps/4.php
if (session_status()===PHP_SESSION_NONE) session_start();
$st = $_SESSION['bpmsai_wizard'] ?? [];
$steps = $st['steps'] ?? [];
if (!is_array($steps)) $steps = [];


$transitions = $st['transitions'] ?? [];   // [stepId][actionCode] = toStepId
$formsByStep = $st['formsByStep'] ?? [];   // [stepId] = ['form_slug'=>..,'editable'=>[],'readonly'=>[]]
$defaultForm = $st['default_form_slug'] ?? '';
?>

<form method="post" action="/modules/bpm/bpmsai_wizard_steps/save.php?step=4">
  <h3>Destinos e Formulários</h3>
  <p class="text-muted">Defina para onde vai cada ação (idas e voltas) e quais campos ficam editáveis por etapa.</p>

  <div class="panel panel-default">
    <div class="panel-body">
      <div class="form-group">
        <label>Formulário padrão (opcional)</label>
        <select class="form-control" name="default_form_slug" id="default_form_slug">
          <option value="">(nenhum)</option>
          <?php
          // lista forms BPM ativos
          // OBS: $conn existe no wizard principal (include). Se não existir, wizard deve passar/usar global.
          global $conn;
          $forms = [];
          if (isset($conn) && $conn instanceof mysqli) {
            $rs = $conn->query("
              SELECT f.`key`, f.title, f.id
              FROM bpm_form f
              JOIN (SELECT `key`, MAX(id) AS id FROM bpm_form GROUP BY `key`) x ON x.id=f.id
              ORDER BY f.title ASC
            ");
            if ($rs) $forms = $rs->fetch_all(MYSQLI_ASSOC);
          }
          foreach($forms as $f):
            $sel = ($defaultForm===$f['slug']) ? 'selected' : '';
          ?>
            <option <?= $sel ?> value="<?= h($f['key']) ?>">
              <?= h($f['title']) ?> (<?= h($f['key']) ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
  </div>

  <?php foreach($steps as $s): 
    $sid = (string)($s['id'] ?? '');
    if ($sid==='') continue;
    $actions = (array)($s['actions'] ?? []);
    $curT = $transitions[$sid] ?? [];
    $curF = $formsByStep[$sid] ?? ['form_slug'=>'','editable'=>[],'readonly'=>[]];
    $curFormSlug = (string)($curF['form_slug'] ?? '');
    $editable = $curF['editable'] ?? [];
    $readonly = $curF['readonly'] ?? [];
  ?>
  <div class="panel panel-default" data-step="<?= h($sid) ?>">
    <div class="panel-heading">
      <strong><?= h($s['name'] ?? $sid) ?></strong>
      <span class="text-muted"> (#<?= h($sid) ?>)</span>
    </div>
    <div class="panel-body">

      <div class="row">
        <div class="col-md-6">
          <h4>Ações → Destino</h4>
          <table class="table table-bordered">
            <thead>
              <tr>
                <th style="width:220px">Ação</th>
                <th>Destino</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach($actions as $acode=>$ainfo):
                $to = $curT[$acode] ?? '';
              ?>
              <tr>
                <td><code><?= h($acode) ?></code> <?= h($ainfo['label'] ?? '') ?></td>
                <td>
                  <select class="form-control" name="to[<?= h($sid) ?>][<?= h($acode) ?>]">
                    <option value="">(sem destino)</option>
                    <option value="__END__" <?= ($to==='__END__'?'selected':'') ?>>(Finalizar)</option>
                    <?php foreach($steps as $s2):
                      $sid2 = (string)($s2['id'] ?? '');
                      if ($sid2==='') continue;
                      $sel = ($to===$sid2) ? 'selected' : '';
                    ?>
                      <option <?= $sel ?> value="<?= h($sid2) ?>"><?= h($s2['name'] ?? $sid2) ?> (#<?= h($sid2) ?>)</option>
                    <?php endforeach; ?>
                  </select>

                  <?php if (in_array($acode, ['reject','revise'], true)): ?>
                    <small class="text-muted">Sugestão: em <b><?= h($acode) ?></b>, exija comentário (config no runtime).</small>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <div class="col-md-6">
          <h4>Formulário e campos</h4>

          <div class="form-group">
            <label>Formulário desta etapa (opcional)</label>
            <select class="form-control step-form-select" name="form_slug[<?= h($sid) ?>]" data-step="<?= h($sid) ?>">
              <option value="">(usar padrão)</option>
              <?php foreach($forms as $f):
                $sel = ($curFormSlug===$f['slug']) ? 'selected' : '';
              ?>
                <option <?= $sel ?> value="<?= h($f['slug']) ?>"><?= h($f['nome']) ?> (<?= h($f['slug']) ?>)</option>
              <?php endforeach; ?>
            </select>
            <small class="text-muted">Os campos serão carregados para marcar <b>Editáveis</b> e <b>Somente leitura</b>.</small>
          </div>

          <div class="row">
            <div class="col-md-6">
              <label>Campos editáveis</label>
              <div class="well well-sm fields-box" id="editable_<?= h($sid) ?>" data-mode="editable" style="max-height:240px;overflow:auto">
                <em class="text-muted">Selecione um formulário para carregar campos…</em>
              </div>
              <input type="hidden" name="editable_json[<?= h($sid) ?>]" id="editable_json_<?= h($sid) ?>" value="<?= h(json_encode($editable, JSON_UNESCAPED_UNICODE)) ?>">
            </div>
            <div class="col-md-6">
              <label>Campos somente leitura</label>
              <div class="well well-sm fields-box" id="readonly_<?= h($sid) ?>" data-mode="readonly" style="max-height:240px;overflow:auto">
                <em class="text-muted">Selecione um formulário para carregar campos…</em>
              </div>
              <input type="hidden" name="readonly_json[<?= h($sid) ?>]" id="readonly_json_<?= h($sid) ?>" value="<?= h(json_encode($readonly, JSON_UNESCAPED_UNICODE)) ?>">
            </div>
          </div>

          <small class="text-muted">
            Dica: se não marcar nada, a etapa pode ser considerada “somente visualização”.
          </small>
        </div>
      </div>

    </div>
  </div>
  <?php endforeach; ?>

  <div class="text-right">
    <a class="btn btn-default" href="/modules/bpm/bpmsai-wizard.php?step=3">Voltar</a>
    <button class="btn btn-primary" type="submit">Salvar e avançar</button>
  </div>
</form>

<script>
(function(){
  function safeParse(v){ try { return JSON.parse(v||'[]'); } catch(e){ return []; } }

  function renderFields(stepId, fields){
    const edBox = document.getElementById('editable_'+stepId);
    const roBox = document.getElementById('readonly_'+stepId);
    const edHidden = document.getElementById('editable_json_'+stepId);
    const roHidden = document.getElementById('readonly_json_'+stepId);

    const selectedEditable = new Set(safeParse(edHidden.value));
    const selectedReadonly = new Set(safeParse(roHidden.value));

    function mkList(box, mode){
      if(!box) return;
      box.innerHTML = '';
      if(!fields.length){
        box.innerHTML = '<em class="text-muted">Nenhum campo detectado no schema.</em>';
        return;
      }
      fields.forEach(f=>{
        const id = mode+'_'+stepId+'_'+f.key;
        const checked = (mode==='editable' ? selectedEditable.has(f.key) : selectedReadonly.has(f.key)) ? 'checked' : '';
        const div = document.createElement('div');
        div.className = 'checkbox';
        div.innerHTML =
          '<label for=\"'+id+'\">' +
          '<input type=\"checkbox\" id=\"'+id+'\" data-step=\"'+stepId+'\" data-mode=\"'+mode+'\" value=\"'+f.key+'\" '+checked+'> ' +
          (f.label || f.key) + ' <span class=\"text-muted\">('+f.key+')</span>' +
          '</label>';
        box.appendChild(div);
      });
    }

    mkList(edBox, 'editable');
    mkList(roBox, 'readonly');
  }

  function syncHidden(stepId){
    const edHidden = document.getElementById('editable_json_'+stepId);
    const roHidden = document.getElementById('readonly_json_'+stepId);

    const ed = [];
    document.querySelectorAll('input[type=checkbox][data-step=\"'+stepId+'\"][data-mode=\"editable\"]:checked')
      .forEach(x=>ed.push(x.value));

    const ro = [];
    document.querySelectorAll('input[type=checkbox][data-step=\"'+stepId+'\"][data-mode=\"readonly\"]:checked')
      .forEach(x=>ro.push(x.value));

    edHidden.value = JSON.stringify(ed);
    roHidden.value = JSON.stringify(ro);
  }

  document.addEventListener('change', function(e){
    const t = e.target;
    if(t && t.matches('input[type=checkbox][data-step][data-mode]')){
      syncHidden(t.getAttribute('data-step'));
    }
  });

  async function loadFieldsFor(stepId, slug){
    if(!slug) {
      renderFields(stepId, []);
      return;
    }
    const res = await fetch('/modules/bpm/api/bpmsai_form_fields.php?key='+encodeURIComponent(slug));
    const j = await res.json();
    renderFields(stepId, (j && j.ok && Array.isArray(j.fields)) ? j.fields : []);
    syncHidden(stepId);
  }

  // auto-load fields for preselected forms
  document.querySelectorAll('.step-form-select').forEach(sel=>{
    sel.addEventListener('change', function(){
      loadFieldsFor(this.getAttribute('data-step'), this.value);
    });

    const stepId = sel.getAttribute('data-step');
    if(sel.value) loadFieldsFor(stepId, sel.value);
  });
})();
</script>
