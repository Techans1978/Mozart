<?php
// modules/forms/actions/form_ai_step1_generate.php
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

function flash(string $msg): void { $_SESSION['__flash'] = ['m' => $msg]; }
function back_with_old(string $msg): void {
  $_SESSION['__old'] = [
    'title' => $_POST['title'] ?? '',
    'tags' => $_POST['tags'] ?? '',
    'prompt' => $_POST['prompt'] ?? '',
    'opt_layout' => isset($_POST['opt_layout']) ? 1 : 0,
    'opt_valid'  => isset($_POST['opt_valid']) ? 1 : 0,
    'opt_rules'  => isset($_POST['opt_rules']) ? 1 : 0,
  ];
  flash($msg);
  header('Location: ' . BASE_URL . '/public/modules/forms/wizard/1.php');
  exit;
}

/**
 * Placeholder do gerador (depois trocamos por IA real no mesmo estilo do BPMS AI).
 * Retorna schema JSON já com seções/campos.
 */
function forms_ai_generate_schema(array $in): array {
  $prompt = trim((string)($in['prompt'] ?? ''));
  $title  = trim((string)($in['title'] ?? ''));
  $tags   = trim((string)($in['tags'] ?? ''));

  // Se não vier título, tenta inferir algo básico
  if ($title === '') $title = 'Formulário Gerado por IA';

  // MVP: gera uma estrutura exemplo (pra você validar o pipeline)
  // Depois substituímos por uma chamada real ao mesmo mecanismo do BPMS AI.
  return [
    'meta' => [
      'title' => $title,
      'tags' => $tags,
      'source' => 'forms_ai_placeholder',
      'prompt' => $prompt,
    ],
    'sections' => [
      [
        'id' => 'sec_1',
        'title' => 'Seção 1',
        'fields' => [
          ['id'=>'f_nome','name'=>'nome','label'=>'Nome','type'=>'text','col'=>6,'required'=>true],
          ['id'=>'f_email','name'=>'email','label'=>'E-mail','type'=>'email','col'=>6,'required'=>false,
            'validators'=>[['type'=>'email']]
          ],
        ],
      ],
      [
        'id' => 'sec_2',
        'title' => 'Seção 2',
        'fields' => [
          ['id'=>'f_valor','name'=>'valor','label'=>'Valor','type'=>'text','col'=>4,'required'=>true,
            'format'=>'R$ #,##0.00','autoFormatOnType'=>'money_br',
            'validators'=>[['type'=>'money_br']]
          ],
          ['id'=>'f_obs','name'=>'observacao','label'=>'Observação','type'=>'textarea','col'=>8,'required'=>false],
        ],
      ],
    ],
    'globals' => [
      'presets' => ['cpf'=>true,'cnpj'=>true,'money_br'=>true,'time_hhmm'=>true,'phone_br'=>true,'cep'=>true],
    ],
  ];
}

$title = trim($_POST['title'] ?? '');
$tags  = trim($_POST['tags'] ?? '');
$prompt = trim($_POST['prompt'] ?? '');

$opt_layout = isset($_POST['opt_layout']) ? 1 : 0;
$opt_valid  = isset($_POST['opt_valid']) ? 1 : 0;
$opt_rules  = isset($_POST['opt_rules']) ? 1 : 0;

if ($prompt === '') {
  back_with_old('Descreva o formulário para a IA gerar.');
}

$createdBy = (int)($_SESSION['user_id'] ?? 0);
if ($createdBy <= 0) $createdBy = null;

// code pode ser gerado automático (slug). Depois você decide se quer editar.
$codeBase = strtoupper(preg_replace('/[^a-zA-Z0-9]+/', '_', $title ?: 'FORM_AI'));
$codeBase = trim($codeBase, '_');
if ($codeBase === '') $codeBase = 'FORM_AI';
$code = $codeBase . '_' . date('Ymd_His'); // garante único

$conn->begin_transaction();

try {
  // 1) cria forms_form
  $stmt = $conn->prepare("INSERT INTO forms_form (code, title, description, tags, status, created_by) VALUES (?, ?, ?, ?, 'draft', ?)");
  $desc = "Gerado por IA (Step 1).";
  $stmt->bind_param("ssssi", $code, $title ?: $code, $desc, $tags, $createdBy);
  if (!$stmt->execute()) throw new Exception("Falha ao criar formulário: " . $stmt->error);
  $formId = (int)$stmt->insert_id;
  $stmt->close();

  // 2) chama gerador (placeholder agora)
  $schema = forms_ai_generate_schema([
    'title' => $title,
    'tags' => $tags,
    'prompt' => $prompt,
    'opt_layout' => $opt_layout,
    'opt_valid' => $opt_valid,
    'opt_rules' => $opt_rules,
  ]);

  // injeta ids do sistema
  $schema['meta']['form_id'] = $formId;
  $schema['meta']['code'] = $code;
  $schema['meta']['version'] = 1;

  $schemaJson = json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  if (!$schemaJson) throw new Exception("Falha ao gerar schema JSON.");

  // 3) cria versão 1 draft
  $stmt = $conn->prepare("INSERT INTO forms_form_version (form_id, version, schema_json, status, created_by) VALUES (?, 1, CAST(? AS JSON), 'draft', ?)");
  $stmt->bind_param("isi", $formId, $schemaJson, $createdBy);
  if (!$stmt->execute()) throw new Exception("Falha ao criar versão do formulário: " . $stmt->error);
  $verId = (int)$stmt->insert_id;
  $stmt->close();

  $conn->commit();

  // contexto do wizard
  $_SESSION['forms_wizard'] = [
    'form_id' => $formId,
    'version' => 1,
    'version_id' => $verId,
    'code' => $code,
  ];

  flash("Formulário gerado por IA: $code (v1 draft).");
  header('Location: ' . BASE_URL . '/public/modules/forms/wizard/2.php'); // Step 2 vira editor de seções/campos
  exit;

} catch (Throwable $e) {
  $conn->rollback();
  back_with_old($e->getMessage());
}
