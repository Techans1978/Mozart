<?php
// public/modules/forms/wizard/1.php (AI Builder Step 1)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../../config.php';
require_once ROOT_PATH . '/system/config/autenticacao.php';
require_once ROOT_PATH . '/system/config/connect.php';

if (!isset($conn) && isset($mysqli)) { $conn = $mysqli; }
if (!($conn instanceof mysqli)) { die('Conexão MySQLi $conn não encontrada.'); }

if (session_status() !== PHP_SESSION_ACTIVE) session_start();
proteger_pagina();

$conn->set_charset('utf8mb4');

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$flash = $_SESSION['__flash']['m'] ?? '';
unset($_SESSION['__flash']);

$old = $_SESSION['__old'] ?? [];
unset($_SESSION['__old']);

$title = $old['title'] ?? '';
$tags  = $old['tags'] ?? '';
$prompt = $old['prompt'] ?? '';

$opt_layout = isset($old['opt_layout']) ? (int)$old['opt_layout'] : 1;
$opt_valid  = isset($old['opt_valid']) ? (int)$old['opt_valid'] : 1;
$opt_rules  = isset($old['opt_rules']) ? (int)$old['opt_rules'] : 1;

?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Forms AI • Wizard • Step 1</title>
  <link rel="stylesheet" href="<?php echo h(BASE_URL); ?>/assets/css/bootstrap.min.css">
  <style>
    .req::after{ content:" *"; color:#dc3545; font-weight:700; }
    .hint{ font-size:.9rem; color:#6c757d; }
  </style>
</head>
<body class="bg-light">
<div class="container py-4">

  <div class="d-flex align-items-center justify-content-between mb-3">
    <div>
      <h3 class="mb-0">Criador de Formulários por IA</h3>
      <div class="text-muted">Wizard • Step 1 — Descreva o formulário</div>
    </div>
    <a class="btn btn-outline-secondary" href="<?php echo h(BASE_URL); ?>/pages/dashboard.php">Voltar</a>
  </div>

  <?php if ($flash): ?>
    <div class="alert alert-warning"><?php echo h($flash); ?></div>
  <?php endif; ?>

  <div class="card shadow-sm">
    <div class="card-body">
      <form method="post" action="<?php echo h(BASE_URL); ?>/modules/forms/actions/form_ai_step1_generate.php" autocomplete="off">

        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Título (opcional)</label>
            <input type="text" name="title" class="form-control" maxlength="160"
                   value="<?php echo h($title); ?>"
                   placeholder="Ex: Solicitação de Compra / Cadastro de Terceiro">
            <div class="hint mt-1">A IA também pode sugerir um título se você deixar em branco.</div>
          </div>

          <div class="col-md-6">
            <label class="form-label">Tags (opcional)</label>
            <input type="text" name="tags" class="form-control" maxlength="255"
                   value="<?php echo h($tags); ?>"
                   placeholder="Ex: financeiro, compras, rh">
          </div>

          <div class="col-12">
            <label class="form-label req">Descreva o formulário</label>
            <textarea name="prompt" class="form-control" rows="10" required
              placeholder="Ex:
Quero um formulário de Solicitação de Compra.
Seções:
1) Solicitante: nome, matrícula, setor, telefone
2) Item: descrição, quantidade, valor unitário (moeda), valor total (auto)
3) Aprovação: centro de custo (select do dataset), urgência (radio)
Regras:
- Se urgência=Sim, exigir justificativa
- Valor total = quantidade * valor unitário
Layout:
- 6/6 na primeira linha, depois 4/4/4 no item
Validações:
- Telefone BR, moeda BR, números sem ponto/vírgula digitando e formatando
Datasets:
- Centro de custo vem do dataset (sql/endpoint)
"><?php echo h($prompt); ?></textarea>

            <div class="hint mt-2">
              Dica: fale em <b>seções</b>, <b>campos</b>, <b>regras</b>, <b>datasets</b> e <b>layout (col 1..12)</b>.
            </div>
          </div>

          <div class="col-12">
            <div class="border rounded p-3 bg-white">
              <div class="fw-semibold mb-2">Opções de geração</div>

              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="opt_layout" value="1" id="opt_layout"
                       <?php echo $opt_layout ? 'checked' : ''; ?>>
                <label class="form-check-label" for="opt_layout">
                  Sugerir layout (grade 12 colunas) automaticamente
                </label>
              </div>

              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="opt_valid" value="1" id="opt_valid"
                       <?php echo $opt_valid ? 'checked' : ''; ?>>
                <label class="form-check-label" for="opt_valid">
                  Incluir validações BR (CPF/CNPJ, moeda, hora, telefone, CEP)
                </label>
              </div>

              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="opt_rules" value="1" id="opt_rules"
                       <?php echo $opt_rules ? 'checked' : ''; ?>>
                <label class="form-check-label" for="opt_rules">
                  Gerar regras (mostrar/esconder/obrigatório condicional/calculados)
                </label>
              </div>

              <div class="hint mt-2">
                Tudo vem como <b>draft</b> e você ajusta depois no editor.
              </div>
            </div>
          </div>
        </div>

        <hr class="my-4">
        <div class="d-flex justify-content-end">
          <button class="btn btn-primary">Gerar Formulário (v1 draft)</button>
        </div>

      </form>
    </div>
  </div>

</div>
</body>
</html>
