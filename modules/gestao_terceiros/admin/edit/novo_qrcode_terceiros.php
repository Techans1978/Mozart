<?php
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(E_ALL);

require_once __DIR__.'/../../../../config.php';
require_once ROOT_PATH.'/system/config/autenticacao.php';
require_once ROOT_PATH.'/system/config/connect.php';
require_once ROOT_PATH.'/modules/gestao_terceiros/config/gest_connect.php'; // $conn_terc

// ====== Aceita SOMENTE GET com forçar explícito ======
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$force = isset($_GET['force']) ? (string)$_GET['force'] : '';

if ($id <= 0) {
  http_response_code(400);
  die('ID inválido.');
}
if ($force !== '1') {
  http_response_code(405);
  die('Uso inválido.');
}

// ====== 1) Carrega dados do promotor
$stmt = $conn_terc->prepare("SELECT id, nome_completo, codigo, NOME_FANTASIA FROM promotores WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$res  = $stmt->get_result();
$prom = $res->fetch_assoc();
$stmt->close();

if (!$prom) {
  http_response_code(404);
  die('Promotor não encontrado.');
}

$codigo  = preg_replace('/\D+/', '', (string)$prom['codigo']);
$nome    = trim((string)($prom['nome_completo'] ?? ''));
$empresa = trim((string)($prom['NOME_FANTASIA'] ?? ''));

// ====== 2) Caminhos (painel/lib e qrcodes)
$basePainel = realpath(__DIR__ . '/../../') ?: __DIR__ . '/../../';
$libQr      = $basePainel . '/lib/phpqrcode/qrlib.php';
$destDir    = $basePainel . '/qrcode';
$fontFile   = $basePainel . '/fonts/DejaVuSans.ttf';

if (!file_exists($libQr))  die('Biblioteca QR não encontrada: ' . $libQr);
if (!is_dir($destDir))     @mkdir($destDir, 0755, true);
if (!file_exists($fontFile)) die('Fonte TTF não encontrada: ' . $fontFile);

require_once $libQr;

// ====== 3) Remove arquivo antigo, se houver
$finalFile = $destDir . "/final_{$codigo}.png";
if (is_file($finalFile)) { @unlink($finalFile); }

// ====== 4) Gera o QR “cru” temporário
$tempFile  = $destDir . "/{$codigo}_temp.png";
@unlink($tempFile);

$conteudoQR = $codigo;   // ajuste se precisar de URL etc.
$level      = 'M';       // L, M, Q, H
$pixelSize  = 8;
$frameSize  = 1;

QRcode::png($conteudoQR, $tempFile, $level, $pixelSize, $frameSize);

if (!is_file($tempFile)) {
  http_response_code(500);
  die('Falha ao gerar QR temporário.');
}

// ====== 5) Monta imagem final (nome + empresa)
$qr      = imagecreatefrompng($tempFile);
$largura = imagesx($qr);
$altura  = imagesy($qr);

// Nome com quebra
$nome_limitado   = mb_substr($nome ?: "PROMOTOR {$codigo}", 0, 36);
$linhas_nome_raw = wordwrap($nome_limitado, 26, "\n", true);
$linhas_nome     = explode("\n", $linhas_nome_raw);

$altura_extra = count($linhas_nome) * 20 + 30;
$novaAltura   = $altura + $altura_extra;

$img_final = imagecreatetruecolor($largura, $novaAltura);
$branco    = imagecolorallocate($img_final, 255, 255, 255);
$preto     = imagecolorallocate($img_final, 0, 0, 0);

imagefill($img_final, 0, 0, $branco);
imagecopy($img_final, $qr, 0, 0, 0, 0, $largura, $altura);

$fonte = $fontFile;

// Centraliza linhas do nome
$linha_inicial = $altura + 10;
$espaco_entre  = 20;
foreach ($linhas_nome as $i => $linha) {
  $box = imagettfbbox(16, 0, $fonte, $linha);
  $largura_texto = $box[2] - $box[0];
  $x = ($largura - $largura_texto) / 2;
  $y = $linha_inicial + ($i * $espaco_entre);
  imagettftext($img_final, 16, 0, $x, $y, $preto, $fonte, $linha);
}

// Empresa
$empresa_cortada = mb_substr($empresa ?: 'GRUPO ABC', 0, 23);
$empresa_upper   = mb_strtoupper($empresa_cortada, 'UTF-8');
$box_emp  = imagettfbbox(12, 0, $fonte, $empresa_upper);
$larg_emp = $box_emp[2] - $box_emp[0];
$x_emp    = ($largura - $larg_emp) / 2;
imagettftext($img_final, 12, 0, $x_emp, $altura + 55, $preto, $fonte, $empresa_upper);

// Salva final e limpa
imagepng($img_final, $finalFile);
imagedestroy($qr);
imagedestroy($img_final);
@unlink($tempFile);

// ====== 6) Redireciona para o download do arquivo gerado
header('Location: baixar_qrcode_terceiro.php?id=' . (int)$id);
exit;
