<?php
require '../lib/autenticacao.php'; // Função de proteção
proteger_pagina();

session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require '../lib/conexao.php'; // Arquivo de conexão com o banco de dados

// Verifica se o usuário está logado e se o ID do usuário está na sessão
if (!isset($_SESSION['user_id'])) {
    die("Erro: Usuário não autenticado.");
}

$user_id = $_SESSION['user_id'];

// Recupera o nome do usuário do banco de dados usando MySQLi
$query = $conn->prepare("SELECT email FROM promotores WHERE id = ?");
$query->bind_param("i", $user_id);

if ($query->execute()) {
    $result = $query->get_result();
    $usuario = $result->fetch_assoc();

    if (!$usuario) {
        die("Erro: Usuário não encontrado.");
    }
} else {
    die("Erro: Falha na execução da consulta.");
}

$id = $user_id;

// Busca o registro atual
$sql = "SELECT nome_completo, cpf, tem_agencia, nome_agencia, celular, aceitou_termos, categoria, email FROM promotores WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id); // Agora $id é o mesmo que $user_id
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("Registro não encontrado.");
}

$registro = $result->fetch_assoc();

// Atualiza o registro se o formulário foi submetido
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome_completo = $_POST['nome_completo'];
    $cpf = $_POST['cpf'];
    $tem_agencia = $_POST['tem_agencia'] == 'Sim' ? 1 : 0;
    $nome_agencia = $tem_agencia ? $_POST['nome_agencia'] : null;
    $celular = $_POST['celular'];
    $email = $_POST['email'];

    // Extrai seqfornecedor, seqrede e nome_fantasia do valor do select
    list($seqfornecedor, $seqrede, $nome_fantasia) = explode('|', $_POST['fornecedor']);

    $sql_update = "UPDATE promotores SET nome_completo = ?, cpf = ?, tem_agencia = ?, nome_agencia = ?, celular = ?, email = ? WHERE id = ?";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("ssisssi", $nome_completo, $cpf, $tem_agencia, $nome_agencia, $celular, $email, $id);

    if ($stmt_update->execute()) {
        header("Location: editar_terceiro.php?mensagem=" . urlencode("Dados salvos com sucesso!"));
    	exit();
    } else {
        echo "Erro ao atualizar: " . $stmt_update->error;
    }

    $stmt_update->close();
}

$stmt->close();
$conn->close();
?>

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
                    <div class="row">
                        <div class="col-lg-12">
                            <h1 class="page-header">Editar Registro</h1>
                        </div>
                        <!-- /.col-lg-12 -->
                    </div>
                    <!-- /.row -->
                   <div class="row">
                        <div class="col-lg-12">
    <form method="post">
        <label for="nome_completo">Nome Completo:</label>
        <input type="text" id="nome_completo" name="nome_completo" value="<?= htmlspecialchars($registro['nome_completo']) ?>" required>

        <label for="cpf">CPF:</label>
        <input type="text" id="cpf" name="cpf" value="<?= htmlspecialchars($registro['cpf']) ?>" required>

        <label for="tem_agencia">Tem agência?</label>
        <select id="tem_agencia" name="tem_agencia">
            <option value="Não" <?= !$registro['tem_agencia'] ? 'selected' : '' ?>>Não</option>
            <option value="Sim" <?= $registro['tem_agencia'] ? 'selected' : '' ?>>Sim</option>
        </select>

        <div id="nome_agencia" style="display: <?= $registro['tem_agencia'] ? 'block' : 'none' ?>;">
            <label for="nome_agencia">Nome da Agência:</label>
            <input type="text" id="nome_agencia" name="nome_agencia" value="<?= htmlspecialchars($registro['nome_agencia'] ?? '') ?>">
        </div>
        
        <label for="celular">Celular:</label>
        <input type="text" id="celular" name="celular" value="<?= htmlspecialchars($registro['celular']) ?>" required>

        <label for="email">E-mail:</label>
        <input type="text" id="email" name="email" value="<?= htmlspecialchars($registro['email']) ?>" required>

        <button type="submit">Salvar Alterações</button>
    </form>

    <script>
        document.getElementById('tem_agencia').addEventListener('change', function() {
            var nomeAgenciaDiv = document.getElementById('nome_agencia');
            if (this.value === 'Sim') {
                nomeAgenciaDiv.style.display = 'block';
            } else {
                nomeAgenciaDiv.style.display = 'none';
            }
        });
    </script>
							
							
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