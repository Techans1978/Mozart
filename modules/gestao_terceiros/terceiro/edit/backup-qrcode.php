<?php
require_once '../lib/phpqrcode/qrlib.php'; // biblioteca QR
require '../lib/conexao.php';
require '../lib/autenticacao.php';
proteger_pagina();

session_start();

$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    die("Usuário não autenticado.");
}

// Busca os dados do usuário logado
$stmt = $conn->prepare("SELECT id, nome_completo, NOME_FANTASIA, codigo FROM promotores WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$usuario = $result->fetch_assoc();

if (!$usuario) {
    die("Usuário não encontrado.");
}

$codigo = $usuario['codigo']; // pode ser o ID, CPF ou outro identificador único
$nome = $usuario['nome_completo'];
$empresa = $usuario['NOME_FANTASIA'];

$diretorio = "../qrcodes";
if (!is_dir($diretorio)) {
    mkdir($diretorio, 0755, true);
}

$caminho_qr = "$diretorio/final_{$codigo}.png";

// ✅ Só gera o QR se ainda **não existir**
if (!file_exists($caminho_qr)) {
    $url = 'https://gestaodeterceiros.superabconline.com.br/verifica.php?cod=' . urlencode($codigo);

    // Cria QR Code básico
    $temp_qr = "$diretorio/{$codigo}_temp.png";
    QRcode::png($url, $temp_qr, QR_ECLEVEL_H, 10);

    // Monta imagem com nome e empresa abaixo
    $qr = imagecreatefrompng($temp_qr);
    $largura = imagesx($qr);
    $altura = imagesy($qr);

    $novaAltura = $altura + 80;
    $img_final = imagecreatetruecolor($largura, $novaAltura);

    $branco = imagecolorallocate($img_final, 255, 255, 255);
    imagefill($img_final, 0, 0, $branco);
    imagecopy($img_final, $qr, 0, 0, 0, 0, $largura, $altura);

    $preto = imagecolorallocate($img_final, 0, 0, 0);
    $fonte = __DIR__ . '/arial.ttf';

    imagettftext($img_final, 16, 0, 10, $altura + 25, $preto, $fonte, $nome);
    imagettftext($img_final, 12, 0, 10, $altura + 50, $preto, $fonte, strtoupper($empresa));

    imagepng($img_final, $caminho_qr);
    unlink($temp_qr); // remove temp
    imagedestroy($qr);
    imagedestroy($img_final);
}

// Exibe o QR já pronto
echo "<h3>Seu QR Code:</h3>";
echo "<img src='$caminho_qr' alt='QR Code'>";
?>