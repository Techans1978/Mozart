<!DOCTYPE html>
<html lang="pt-br">

    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="">
        <meta name="author" content="">

        <title>Gestão de Terceiros</title>

        <!-- Bootstrap Core CSS -->
        <link href="../css/bootstrap.min.css" rel="stylesheet">

        <!-- MetisMenu CSS -->
        <link href="../css/metisMenu.min.css" rel="stylesheet">

        <!-- DataTables CSS -->
        <link href="../css/dataTables/dataTables.bootstrap.css" rel="stylesheet">

        <!-- DataTables Responsive CSS -->
        <link href="../css/dataTables/dataTables.responsive.css" rel="stylesheet">

        <!-- Custom CSS -->
        <link href="../css/startmin.css" rel="stylesheet">

        <!-- Custom Fonts -->
        <link href="../css/font-awesome.min.css" rel="stylesheet" type="text/css">

        <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
        <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
        <!--[if lt IE 9]>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/html5shiv/3.7.3/html5shiv.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/respond.js/1.4.2/respond.min.js"></script>
        <![endif]-->
		
    <style>
        form {
            max-width: 500px;
            margin: 0 auto;
        }
        input, select {
            width: 100%;
            padding: 10px;
            margin: 5px 0;
        }
    </style>
		
    </head>

    <body>

        <div id="wrapper">

            <!-- Inicio menu Navigation -->
            <nav class="navbar navbar-inverse navbar-fixed-top" role="navigation">
                <div class="navbar-header">
                    <a class="navbar-brand" href="../dashboard.php"><img class="logo-image d-none d-lg-inline-block" src="../images/grupo-abc-logo.png" alt="Grupo ABC" width="65px" height="auto"></a>
                </div>

	
                <ul class="nav navbar-right navbar-top-links">
                    <li class="dropdown">
                        <a class="dropdown-toggle" data-toggle="dropdown" href="#">
                            <i class="fa fa-user fa-fw"></i> Área do Usuário <b class="caret"></b>
                        </a>
                        <ul class="dropdown-menu dropdown-user">
                            <li>
                                <a href="editar_terceiro.php"><i class="fa fa-pencil fa-fw"></i> Editar Perfil</a>
                            </li>
                            <li>
                                <a href="qrcode.php"><i class="fa fa-qrcode fa-fw"></i> Baixar Qr Code</a>
                            </li>
                            <li>
                                <a href="terceiro_senha.php"><i class="fa fa-gear fa-fw"></i> Mudar Senha</a>
                            </li>
                            <li class="divider"></li>
                            <li>
                                <a href="../logout.php"><i class="fa fa-sign-out fa-fw"></i> Sair</a>
                            </li>
                        </ul>
                    </li>
                </ul>
                <!-- /.navbar-top-links -->
            </nav>

                <div class="container-fluid">
                    <!-- /.row -->
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="jumbotron">
                                <h2>Gestão de Terceiros do Grupo ABC</h2>
                                <p>Edite seus dados e emita seu QR Code de acesso.</p>
                            </div>
                        </div>
                        <!-- /.col-lg-12 -->
                    </div>
                    <!-- /.row -->
                    
        <div id="wrapper">
            <div id="page-wrapper">
                <div class="container-fluid">
                    <!-- /.row -->
                   <div class="row">
                        <div class="col-lg-12">
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
$stmt = $conn->prepare("SELECT id, nome_completo, codigo, NOME_FANTASIA FROM promotores WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$usuario = $result->fetch_assoc();

if (!$usuario) {
    die("Usuário não encontrado.");
}

$codigo  = $usuario['codigo'];
$nome    = $usuario['nome_completo'];
$empresa = $usuario['NOME_FANTASIA'];

// Verifica se o código está vazio ou nulo
if (empty($codigo)) {
    echo "<div class='alert alert-warning' role='alert'>
            Seu código ainda não foi gerado, volte em 30 min para e tente novamente.
          </div>";
} else {
    $diretorio = "../qrcodes";
    if (!is_dir($diretorio)) {
        mkdir($diretorio, 0755, true);
    }

    $caminho_qr = "$diretorio/final_{$codigo}.png";

    // Só gera o QR se ainda não existir

    // Caminho do QR temporário
    $temp_qr = "$diretorio/{$codigo}_temp.png";

    // Gera o QR Code com APENAS o código (ex: 123456)
    QRcode::png($codigo, $temp_qr, QR_ECLEVEL_H, 10);

    // Monta imagem com nome e empresa abaixo
    $qr = imagecreatefrompng($temp_qr);
    $largura = imagesx($qr);
    $altura = imagesy($qr);

    $linhas_nome = explode("\n", wordwrap($nome, 26, "\n", true));
$altura_extra = count($linhas_nome) * 20 + 30; // 20px por linha + espaço
$novaAltura = $altura + $altura_extra;

    $img_final = imagecreatetruecolor($largura, $novaAltura);

    $branco = imagecolorallocate($img_final, 255, 255, 255);
    imagefill($img_final, 0, 0, $branco);
    imagecopy($img_final, $qr, 0, 0, 0, 0, $largura, $altura);

    $preto = imagecolorallocate($img_final, 0, 0, 0);
    $fonte = __DIR__ . '/../fonts/DejaVuSans.ttf';

    // Centraliza o nome
// Limita o nome a 50 caracteres
$nome_limitado = mb_substr($nome, 0, 36);

// Quebra o nome em linhas de até 26 caracteres (respeitando palavras)
$linhas_nome = wordwrap($nome_limitado, 26, "\n", true);
$linhas_array = explode("\n", $linhas_nome);

// Desenha cada linha do nome, uma abaixo da outra
$linha_inicial = $altura + 10;
$espaco_entre_linhas = 20;

foreach ($linhas_array as $i => $linha) {
    $box = imagettfbbox(16, 0, $fonte, $linha);
    $largura_texto = $box[2] - $box[0];
    $x = ($largura - $largura_texto) / 2;
    $y = $linha_inicial + ($i * $espaco_entre_linhas);
    imagettftext($img_final, 16, 0, $x, $y, $preto, $fonte, $linha);
}


// Centraliza a empresa
$empresa_cortada = mb_substr($empresa, 0, 23); // Corta para no máximo 20 caracteres
$empresa_upper = strtoupper($empresa_cortada);
$box_empresa = imagettfbbox(12, 0, $fonte, $empresa_upper);
$largura_texto_empresa = $box_empresa[2] - $box_empresa[0];
$x_empresa = ($largura - $largura_texto_empresa) / 2;
imagettftext($img_final, 12, 0, $x_empresa, $altura + 55, $preto, $fonte, $empresa_upper);

    imagepng($img_final, $caminho_qr);
    unlink($temp_qr); // remove temp
    imagedestroy($qr);
    imagedestroy($img_final);
}


// Exibe o QR já pronto
echo "<h3>Seu QR Code:</h3>";
echo "<img src='$caminho_qr?" . time() . "' alt='QR Code'>";
?>
							
                            <!-- /.panel -->
                        </div>
                        <!-- /.col-lg-12 -->
                    </div>
                    <!-- /.row -->
                </div>
                <!-- /.container-fluid -->
            </div>
            <!-- /#page-wrapper -->

        </div>
        <!-- /#wrapper -->

        <!-- jQuery -->
        <script src="../js/jquery.min.js"></script>

        <!-- Bootstrap Core JavaScript -->
        <script src="../js/bootstrap.min.js"></script>

        <!-- Metis Menu Plugin JavaScript -->
        <script src="../js/metisMenu.min.js"></script>

        <!-- DataTables JavaScript -->
        <script src="../js/dataTables/jquery.dataTables.min.js"></script>
        <script src="../js/dataTables/dataTables.bootstrap.min.js"></script>

        <!-- Custom Theme JavaScript -->
        <script src="../js/startmin.js"></script>

        <!-- Page-Level Demo Scripts - Tables - Use for reference -->
        <script>
            $(document).ready(function () {
                $('#dataTables-example').DataTable({
                    responsive: true
                });
            });
        </script>

    </body>

</html>