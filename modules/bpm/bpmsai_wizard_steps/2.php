<?php
// modules/bpm/bpmsai_wizard_steps/2.php
if (session_status()===PHP_SESSION_NONE) session_start();
$st = $_SESSION['bpmsai_wizard'] ?? [];

$txt = (string)($st['original_text'] ?? '');
$dict = (string)($st['actors_dict'] ?? '');
?>

<form method="post" action="/modules/bpm/bpmsai_wizard_steps/save.php?step=2">
  <h3>Texto / IA</h3>
  <p class="text-muted">
    Cole o texto corrido do processo e (opcional) o dicionário de atores para padronizar “quem”.
  </p>

  <div class="form-group">
    <label>Texto do processo</label>
    <textarea class="form-control" name="original_text" rows="10"
      placeholder="Ex: O grupo de analistas abre..."><?= h($txt) ?></textarea>
  </div>

  <div class="form-group">
    <label>Dicionário de atores (alias=roleKey)</label>
    <textarea class="form-control" name="actors_dict" rows="6"
      placeholder="analista=analista&#10;gerente=gerente_area&#10;rh=rh"><?= h($dict) ?></textarea>
    <small class="text-muted">Você pode expandir isso depois. Serve pra IA e auditoria.</small>
  </div>

  <div class="text-right">
    <a class="btn btn-default" href="/modules/bpm/bpmsai-wizard.php?step=1">Voltar</a>
    <button class="btn btn-primary" type="submit">Salvar e avançar</button>
  </div>
</form>
