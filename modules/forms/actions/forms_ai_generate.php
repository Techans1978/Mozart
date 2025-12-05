<?php
// modules/forms/actions/forms_ai_generate.php
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../../config.php';
require_once ROOT_PATH . '/system/config/autenticacao.php';

header('Content-Type: application/json; charset=utf-8');

if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }

$prompt = trim($_POST['prompt'] ?? '');

if ($prompt === '') {
    echo json_encode(['error' => 'Prompt vazio.']);
    exit;
}

/*
 * Aqui é só um exemplo estático.
 * Depois você pode chamar a API de IA e montar o schema dinâmico.
 */

$title = mb_substr($prompt, 0, 60) . (mb_strlen($prompt) > 60 ? '...' : '');

$schema = [
    'schemaVersion' => 1,
    'type' => 'default',
    'components' => [
        [
            'type'  => 'textfield',
            'key'   => 'titulo',
            'label' => 'Título',
            'validate' => [ 'required' => true ],
        ],
        [
            'type'  => 'textarea',
            'key'   => 'descricao',
            'label' => 'Descrição',
        ],
        [
            'type'  => 'textarea',
            'key'   => 'observacoes',
            'label' => 'Observações (gerado a partir do prompt)',
            'description' => $prompt,
        ],
    ],
    'data' => []
];

echo json_encode([
    'title'  => $title !== '' ? $title : 'Formulário gerado',
    'schema' => $schema,
]);
