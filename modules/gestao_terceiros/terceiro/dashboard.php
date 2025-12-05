<?php
require 'lib/autenticacao.php'; // Inclui a função de proteção
proteger_pagina(); // Protege a página

require 'lib/conexao.php'; // Inclui o arquivo de conexão

// Consulta 1: Total de promotores
$query = "SELECT COUNT(*) AS TOTAL FROM promotores";
if ($stmt = $conn->prepare($query)) {
    $stmt->execute();
    $stmt->bind_result($totalpromotores);
    $stmt->fetch();   
    $stmt->close();
} else {
    die('Erro na preparação da consulta: ' . $conn->error);
}

// Consulta 2: Total de fornecedores
$query = "SELECT COUNT(*) AS TOTAL FROM lista_fornecedores";
if ($stmt = $conn->prepare($query)) {
    $stmt->execute();
    $stmt->bind_result($totalfornecedores);
    $stmt->fetch();   
    $stmt->close();
} else {
    die('Erro na preparação da consulta: ' . $conn->error);
}

// Consulta 3: Total de usuários
$query = "SELECT COUNT(*) AS TOTAL FROM usuarios";
if ($stmt = $conn->prepare($query)) {
    $stmt->execute();
    $stmt->bind_result($totalusuarios);
    $stmt->fetch();   
    $stmt->close();
} else {
    die('Erro na preparação da consulta: ' . $conn->error);
}
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
        <link href="css/bootstrap.min.css" rel="stylesheet">

        <!-- MetisMenu CSS -->
        <link href="css/metisMenu.min.css" rel="stylesheet">

        <!-- Custom CSS -->
        <link href="css/startmin.css" rel="stylesheet">

        <!-- Custom Fonts -->
        <link href="css/font-awesome.min.css" rel="stylesheet" type="text/css">

        <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
        <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
        <!--[if lt IE 9]>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/html5shiv/3.7.3/html5shiv.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/respond.js/1.4.2/respond.min.js"></script>
        <![endif]-->
    </head>

    <body>

        <div id="wrapper">

            <!-- Inicio menu Navigation -->
            <nav class="navbar navbar-inverse navbar-fixed-top" role="navigation">
                <div class="navbar-header">
                    <a class="navbar-brand" href="#"><img class="logo-image d-none d-lg-inline-block" src="images/grupo-abc-logo.png" alt="Grupo ABC" width="65px" height="auto"></a>
                </div>

	
                <ul class="nav navbar-right navbar-top-links">
                    <li class="dropdown">
                        <a class="dropdown-toggle" data-toggle="dropdown" href="#">
                            <i class="fa fa-user fa-fw"></i> Área do Usuário <b class="caret"></b>
                        </a>
                        <ul class="dropdown-menu dropdown-user">
                            <li>
                                <a href="edit/editar_terceiro.php"><i class="fa fa-pencil fa-fw"></i> Editar Perfil</a>
                            </li>
                            <li>
                                <a href="edit/qrcode.php"><i class="fa fa-qrcode fa-fw"></i> Baixar Qr Code</a>
                            </li>
                            <li>
                                <a href="edit/terceiro_senha.php"><i class="fa fa-gear fa-fw"></i> Mudar Senha</a>
                            </li>
                            <li class="divider"></li>
                            <li>
                                <a href="logout.php"><i class="fa fa-sign-out fa-fw"></i> Sair</a>
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
                    <div class="row">
                        <div class="col-sm-12">
                            <div class="hero-widget well well-sm">
                                <div class="icon">
                                    <i class="glyphicon glyphicon-user"></i>
                                </div>
                                <div class="text">
                                    <label class="text-muted">Painel do Usuário</label>
                                </div>
                                <div class="options">
                                    <a href="edit/editar_terceiro.php" class="btn btn-default btn-lg"><i class="fa fa-pencil fa-fw"></i> Editar Perfil</a>
                                </div>
                                <div class="options">
                                    <a href="edit/qrcode.php" class="btn btn-default btn-lg"><i class="fa fa-qrcode fa-fw"></i> Baixar Qr Code</a>
                                </div>
                                <div class="options">
                                    <a href="edit/terceiro_senha.php" class="btn btn-default btn-lg"><i class="fa fa-gear fa-fw"></i> Mudar Senha</a>
                                </div>
                                <div class="options">
                                    <a href="logout.php" class="btn btn-default btn-lg"><i class="fa fa-sign-out fa-fw"></i> Sair</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- /.container-fluid -->
        </div>
        <!-- /#wrapper -->

        <!-- jQuery -->
        <script src="js/jquery.min.js"></script>

        <!-- Bootstrap Core JavaScript -->
        <script src="js/bootstrap.min.js"></script>

        <!-- Metis Menu Plugin JavaScript -->
        <script src="js/metisMenu.min.js"></script>

        <!-- Custom Theme JavaScript -->
        <script src="js/startmin.js"></script>

    </body>

</html>