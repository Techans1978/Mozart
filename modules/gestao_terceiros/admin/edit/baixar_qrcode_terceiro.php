<?php
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(E_ALL);

require_once __DIR__.'/../../../../config.php';
require_once ROOT_PATH.'/system/config/autenticacao.php';
require_once ROOT_PATH.'/system/config/connect.php';
require_once ROOT_PATH.'/modules/gestao_terceiros/config/gest_connect.php'; // $conn_terc

if (session_status()===PHP_SESSION_NONE) session_start();
proteger_pagina();

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    http_response_code(400);
    die('ID inválido.');
}

// Busca codigo do promotor (sem get_result)
$stmt = $conn_terc->prepare("SELECT codigo, nome_completo FROM promotores WHERE id = ?");
if (!$stmt) {
    http_response_code(500);
    die('Falha ao preparar consulta: ' . $conn_terc->error);
}

$stmt->bind_param("i", $id);

if (!$stmt->execute()) {
    http_response_code(500);
    die('Falha ao executar consulta: ' . $stmt->error);
}

$stmt->bind_result($codigo_raw, $nome_raw);

if (!$stmt->fetch()) {
    $stmt->close();
    http_response_code(404);
    die('Promotor não encontrado.');
}

$stmt->close();

$codigo = preg_replace('/\D+/', '', (string)$codigo_raw);
$nome   = $nome_raw ?: 'promotor';

// Caminho do arquivo gerado na pasta qrcode (fora de admin/edit)
$qrPath = realpath(__DIR__ . '/../../qrcode');
$file   = $qrPath
    ? $qrPath . "/final_{$codigo}.png"
    : __DIR__ . "/../../qrcode/final_{$codigo}.png";


if (!is_file($file)) {
    // Aqui SIM usamos o layout pra deixar bonitinho
    include_once ROOT_PATH.'system/includes/head.php';
    include_once ROOT_PATH.'system/includes/navbar.php';
    ?>

    <div id="wrapper">
        <div id="page-wrapper">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-lg-12">
                        <h1 class="page-header">Download de QR Code</h1>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-12">
                        <div class="alert alert-danger">
                            <p><strong>QR Code não encontrado</strong> para o código <strong><?= h($codigo) ?></strong>.</p>
                        </div>

                        <p>
                            <a href="listar_terceiros.php" class="btn btn-default">Voltar para a lista</a>
                            <a href="javascript:history.back()" class="btn btn-link">Voltar (histórico)</a>
                            <a href="novo_qrcode_terceiros.php?id=<?= (int)$id ?>&force=1"
                               class="btn btn-primary"
                               onclick="return confirm('Gerar um novo QR Code agora?')">
                                Gerar novo QR Code
                            </a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php
    include_once ROOT_PATH.'system/includes/code_footer.php';
    include_once ROOT_PATH.'system/includes/footer.php';
    exit;
}

// Se chegou aqui, arquivo existe → resposta deve ser só o download, sem layout
$downloadName = "final_{$codigo}.png";

header('Content-Type: image/png');
header('Content-Length: ' . filesize($file));
header('Content-Disposition: attachment; filename="' . $downloadName . '"');
readfile($file);
exit;
