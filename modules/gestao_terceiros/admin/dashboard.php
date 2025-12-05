<?php
// public/modules/gestao_ativos/ativos-form.php
ini_set('display_errors',1); ini_set('display_startup_errors',1); error_reporting(E_ALL);

require_once __DIR__.'/../../../config.php';
require_once ROOT_PATH.'/system/config/autenticacao.php';
require_once ROOT_PATH.'/system/config/connect.php';
require_once ROOT_PATH.'modules/gestao_terceiros/config/gest_connect.php'; // Inclui o arquivo de conexão
if (session_status()===PHP_SESSION_NONE) session_start();
proteger_pagina();

// Consulta 1: Total de promotores
$query = "SELECT COUNT(*) AS TOTAL FROM promotores";
if ($stmt = $conn_terc->prepare($query)) {
    $stmt->execute();
    $stmt->bind_result($totalpromotores);
    $stmt->fetch();   
    $stmt->close();
} else {
    die('Erro na preparação da consulta: ' . $conn_terc->error);
}

// Consulta 2: Total de fornecedores
$query = "SELECT COUNT(*) AS TOTAL FROM lista_fornecedores";
if ($stmt = $conn_terc->prepare($query)) {
    $stmt->execute();
    $stmt->bind_result($totalfornecedores);
    $stmt->fetch();   
    $stmt->close();
} else {
    die('Erro na preparação da consulta: ' . $conn_terc->error);
}
?>
<?php include_once ROOT_PATH.'system/includes/head.php'; ?>
<?php include_once ROOT_PATH.'system/includes/navbar.php'; ?>

        <div id="wrapper">
            <div id="page-wrapper">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="well">
                                <h4>Painel</h4>
                            </div>
                        </div>
                    </div>
                    <!-- /.row -->
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="hero-widget well well-sm">
                                <div class="icon">
                                    <i class="glyphicon glyphicon-user"></i>
                                </div>
                                <div class="text">
                                    <span class="value"><span class="value"><?= htmlspecialchars($totalpromotores) ?></span>
                        			<label class="text-muted">Promotores</label></span>
                                    <label class="text-muted">Promotores</label>
                                </div>
                                <div class="options">
                                    <a href="edit/exportar_csv.php" class="btn btn-default btn-lg"><i class="glyphicon glyphicon-plus"></i> Exportar CSV</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="hero-widget well well-sm">
                                <div class="icon">
                                    <i class="glyphicon glyphicon-star"></i>
                                </div>
                                <div class="text">
                                    <span class="value"><?= htmlspecialchars($totalfornecedores) ?></span>
                                    <label class="text-muted">Fornecedores</label>
                                </div>
                                <div class="options">
                                    <a href="edit/importar_fornecedores.php" class="btn btn-default btn-lg"><i class="glyphicon glyphicon-plus"></i> Importar CSV</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- /.row -->
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="jumbotron">
                                <h1>Administração Cadastro de Promotores</h1>
                                <p>Gestão fácil e eficiente, com cadastro simplificado, controle de aceites, e exclusão segura. Organize sua equipe com praticidade e otimize processos com nosso painel intuitivo e robusto!</p>
                            </div>
                        </div>
                        <!-- /.col-lg-12 -->
                    </div>
                    <!-- /.row -->
                </div>
                <!-- /.container-fluid -->
            </div>
            <!-- /#page-wrapper -->
        </div>
<?php include_once ROOT_PATH.'system/includes/code_footer.php'; ?>
<?php include_once ROOT_PATH.'system/includes/footer.php'; ?>