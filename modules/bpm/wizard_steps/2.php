<?php /* Origem */ ?>

<?php
// Evita warning quando ainda não tiver origem no state
$origem = $state['origem'] ?? '';
?>

<div class="card"><h2>2) Origem do fluxo</h2>
<form method="post" enctype="multipart/form-data" action="/modules/bpm/wizard_steps/save.php?step=2">
  <label>
    <input type="radio" name="origem" value="novo"
           <?php echo ($origem === 'novo' ? 'checked' : ''); ?>>
    Criar novo
  </label><br>

  <label>
    <input type="radio" name="origem" value="ia"
           <?php echo ($origem === 'ia' ? 'checked' : ''); ?>>
    Criar completo por IA
  </label><br>

  <label>
    <input type="radio" name="origem" value="fluig"
           <?php echo ($origem === 'fluig' ? 'checked' : ''); ?>>
    Importar Fluig (.bpmn/.json/zip)
  </label><br>

  <label>
    <input type="radio" name="origem" value="camunda"
           <?php echo ($origem === 'camunda' ? 'checked' : ''); ?>>
    Importar Camunda (.bpmn)
  </label>

  <hr>

  <div class="row">
    <div class="col-12 mb-3">
      <label for="ia_prompt"><strong>Descrição (se IA)</strong></label>
      <textarea
          id="ia_prompt"
          name="ia_prompt"
          class="form-control"
          rows="6"
          placeholder="Descreva aqui o processo para a IA montar o fluxo (etapas, responsáveis, regras, exceções, prazos, etc.)"
      ><?php echo htmlspecialchars($state['ia_prompt'] ?? ''); ?></textarea>
    </div>

    <div class="col-12 col-md-6 mb-3">
  <label class="form-label"><strong>Upload opcional (.bpmn, .json ou .zip)</strong></label>

  <div class="border rounded p-3">
    <div class="d-flex align-items-center mb-2">
      <button type="button"
              class="btn btn-outline-primary btn-sm"
              onclick="document.getElementById('upload').click();">
        Escolher arquivo
      </button>
      <span id="upload-name" class="ms-2 small text-muted">
        Nenhum arquivo selecionado
      </span>
    </div>

    <!-- input real fica escondido -->
    <input
        type="file"
        id="upload"
        name="upload"
        class="d-none"
        accept=".bpmn,.json,.zip"
        onchange="document.getElementById('upload-name').textContent =
                  this.files[0] ? this.files[0].name : 'Nenhum arquivo selecionado';"
    >

    <div class="small text-muted">
      Use um arquivo exportado do Fluig ou Camunda se já tiver o fluxo pronto.
    </div>
  </div>
</div>

  </div>

  <button class="btn primary">Processar</button>
</form>
</div>
