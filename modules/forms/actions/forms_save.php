<?php
// modules/forms/actions/forms_save.php
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../../config.php';
require_once ROOT_PATH . '/system/config/autenticacao.php';
require_once ROOT_PATH . '/system/config/connect.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($conn) && isset($mysqli)) { $conn = $mysqli; }
if (!($conn instanceof mysqli)) {
    echo json_encode(['error' => 'Conexão MySQLi não encontrada.']);
    exit;
}
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }

$id          = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$titulo      = trim($_POST['titulo'] ?? '');
$schema_json = $_POST['schema_json'] ?? '';
$html        = $_POST['html'] ?? '';
$tipo        = $_POST['tipo'] ?? 'bpm';
$categoria   = $_POST['categoria'] ?? null;

if ($titulo === '') {
    echo json_encode(['error' => 'Título é obrigatório.']);
    exit;
}
if ($schema_json === '') {
    echo json_encode(['error' => 'Schema JSON vazio.']);
    exit;
}

// Gera slug simples
$slug = strtolower($titulo);
$slug = preg_replace('/[^a-z0-9]+/i', '-', $slug);
$slug = trim($slug, '-');
if ($slug === '') {
    $slug = 'form-' . time();
}

$userId = $_SESSION['id_usuario'] ?? null; // ajuste se o nome da sessão for outro

try {

    if ($id > 0) {
        $sql = "UPDATE moz_forms
                   SET nome = ?, slug = ?, tipo = ?, categoria = ?, json = ?, html = ?, versao = versao + 1
                 WHERE id = ?";
        $st = $conn->prepare($sql);
        if (!$st) { throw new Exception($conn->error); }
        $st->bind_param('ssssssi', $titulo, $slug, $tipo, $categoria, $schema_json, $html, $id);
        $st->execute();
        $st->close();
    } else {
        $sql = "INSERT INTO moz_forms (nome, slug, tipo, categoria, caminho_json, caminho_html, json, html, versao, ativo, criado_por)
                VALUES (?,?,?,?,NULL,NULL,?,?,1,1,?)";
        $st = $conn->prepare($sql);
        if (!$st) { throw new Exception($conn->error); }
        $st->bind_param('ssssssi', $titulo, $slug, $tipo, $categoria, $schema_json, $html, $userId);
        $st->execute();
        $id = $st->insert_id;
        $st->close();
    }

    echo json_encode(['id' => $id]);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
