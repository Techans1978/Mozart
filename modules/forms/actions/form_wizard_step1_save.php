<?php
// modules/forms/actions/form_wizard_step1_save.php
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
    'code' => $_POST['code'] ?? '',
    'title' => $_POST['title'] ?? '',
    'description' => $_POST['description'] ?? '',
    'tags' => $_POST['tags'] ?? '',
  ];
  flash($msg);
  header('Location: ' . BASE_URL . '/public/modules/forms/wizard/1.php');
  exit;
}

$code = strtoupper(trim($_POST['code'] ?? ''));
$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$tags = trim($_POST['tags'] ?? '');

if ($code === '' || $title === '') {
  back_with_old('Código e Título são obrigatórios.');
}

// Normaliza: só A-Z 0-9 _
$code = preg_replace('/[^A-Z0-9_]/', '_', $code);
$code = preg_replace('/_+/', '_', $code);

// Validação básica: começa com letra/número, sem vazio
if (!preg_match('/^[A-Z0-9][A-Z0-9_]{1,79}$/', $code)) {
  back_with_old('Código inválido. Use letras/números/underscore e mínimo 2 caracteres.');
}

// tags: normaliza espaços
$tags = preg_replace('/\s+/', ' ', $tags);

// created_by (se existir user_id)
$createdBy = (int)($_SESSION['user_id'] ?? 0);
if ($createdBy <= 0) $createdBy = null;

$conn->begin_transaction();

try {
  // Verifica se já existe code
  $stmt = $conn->prepare("SELECT id FROM forms_form WHERE code=? LIMIT 1");
  $stmt->bind_param("s", $code);
  $stmt->execute();
  $exists = $stmt->get_result()->fetch_assoc();
  $stmt->close();

  if ($exists) {
    throw new Exception("Já existe um formulário com este código ($code).");
  }

  // Insere forms_form
  $stmt = $conn->prepare("INSERT INTO forms_form (code, title, description, tags, status, created_by) VALUES (?, ?, ?, ?, 'draft', ?)");
  $stmt->bind_param("ssssi", $code, $title, $description, $tags, $createdBy);
  if (!$stmt->execute()) throw new Exception("Falha ao criar formulário: " . $stmt->error);
  $formId = (int)$stmt->insert_id;
  $stmt->close();

  // Schema mínimo versão 1
  $schema = [
    'meta' => [
      'id' => $formId,
      'code' => $code,
      'title' => $title,
      'description' => $description,
      'tags' => $tags,
      'version' => 1,
    ],
    'sections' => [],
    'globals' => [
      'presets' => [
        // placeholders (vamos preencher nos próximos steps)
        'cpf' => true, 'cnpj' => true, 'money_br' => true, 'time_hhmm' => true
      ]
    ]
  ];

  $schemaJson = json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  if (!$schemaJson) throw new Exception("Falha ao gerar schema JSON.");

  // Insere forms_form_version v1 draft
  $stmt = $conn->prepare("INSERT INTO forms_form_version (form_id, version, schema_json, status, created_by) VALUES (?, 1, CAST(? AS JSON), 'draft', ?)");
  $stmt->bind_param("isi", $formId, $schemaJson, $createdBy);
  if (!$stmt->execute()) throw new Exception("Falha ao criar versão do formulário: " . $stmt->error);
  $formVersionId = (int)$stmt->insert_id;
  $stmt->close();

  $conn->commit();

  // Guarda em sessão o "contexto do wizard"
  $_SESSION['forms_wizard'] = [
    'form_id' => $formId,
    'version' => 1,
    'version_id' => $formVersionId,
    'code' => $code,
  ];

  flash("Formulário criado: $code (v1 draft).");
  header('Location: ' . BASE_URL . '/public/modules/forms/wizard/2.php'); // Step 2: Seções (vamos criar em seguida)
  exit;

} catch (Throwable $e) {
  $conn->rollback();
  back_with_old($e->getMessage());
}
