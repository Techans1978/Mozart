<?php echo 'TODO: Step 1 (Metadados)';
// modules/bpm/bpmsai_wizard_steps/1.php
if (session_status()===PHP_SESSION_NONE) session_start();
$st = $_SESSION['bpmsai_wizard'] ?? [];

$nome        = (string)($st['nome'] ?? '');
$codigo      = (string)($st['codigo'] ?? '');
$descricao   = (string)($st['descricao'] ?? '');
$categoriaId = (int)($st['categoria_id'] ?? 0);

$cats = $CATEGORIAS ?? [];
?>

<form method="post" action="/modules/bpm/bpmsai_wizard_steps/save.php?step=1">
  <h3>Metadados</h3>
  <p class="text-muted">Defina nome, código e categoria (usa a mesma categoria do BPM).</p>

  <div class="row">
    <div class="col-md-4">
      <div class="form-group">
        <label>Nome *</label>
        <input class="form-control" name="nome" value="<?= h($nome) ?>" required>
      </div>
    </div>

    <div class="col-md-3">
      <div class="form-group">
        <label>Código *</label>
        <input class="form-control" name="codigo" value="<?= h($codigo) ?>" required
               placeholder="ex: demissao_adm">
        <small class="text-muted">Sem espaços. Use underscore.</small>
      </div>
    </div>

    <div class="col-md-5">
      <div class="form-group">
        <label>Categoria (BPM)</label>
        <select class="form-control" name="categoria_id">
          <option value="">(sem categoria)</option>
          <?php foreach($cats as $c): ?>
            <?php $sel = ((int)$c['id']===$categoriaId) ? 'selected' : ''; ?>
            <option value="<?= (int)$c['id'] ?>" <?= $sel ?>><?= h($c['nome']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
  </div>

  <div class="form-group">
    <label>Descrição</label>
    <textarea class="form-control" name="descricao" rows="3"><?= h($descricao) ?></textarea>
  </div>

  <div class="text-right">
    <a class="btn btn-default" href="/modules/bpm/bpmsai-listar.php">Cancelar</a>
    <button class="btn btn-primary" type="submit">Salvar e avançar</button>
  </div>
</form>

<script>
(function(){
  const code = document.querySelector('input[name=codigo]');
  if(!code) return;
  code.addEventListener('input', function(){
    this.value = this.value.replace(/[^a-zA-Z0-9_\\-]/g,'_');
  });
})();
</script>
