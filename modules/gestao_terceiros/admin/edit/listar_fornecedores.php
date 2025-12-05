<?php
// public/modules/gestao_ativos/ativos-form.php
ini_set('display_errors',1); ini_set('display_startup_errors',1); error_reporting(E_ALL);

require_once __DIR__.'/../../../../config.php';
require_once ROOT_PATH.'/system/config/autenticacao.php';
require_once ROOT_PATH.'/system/config/connect.php';
require_once ROOT_PATH.'modules/gestao_terceiros/config/gest_connect.php'; // Inclui o arquivo de conexão
if (session_status()===PHP_SESSION_NONE) session_start();
proteger_pagina();

// Consulta ajustada para buscar os novos campos
$sql = "SELECT SEQFORNECEDOR, SEQREDE, NOME_FANTASIA, DESCRICAO FROM lista_fornecedores";
$result = $conn_terc->query($sql);
?>

<?php include_once ROOT_PATH.'system/includes/head.php'; ?>

<style>
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
        }
        th {
            background-color: #f2f2f2;
        }
    </style>
    <script>
        function confirmDelete(seqfornecedor, seqrede) {
            if (confirm('Tem certeza que deseja excluir este fornecedor?')) {
                window.location.href = 'excluir_fornecedor.php?seqfornecedor=' + seqfornecedor + '&seqrede=' + seqrede;
            }
        }
    </script>

<?php include_once ROOT_PATH.'system/includes/navbar.php'; ?>
		
    
        <div id="wrapper">

            <!-- Inicio menu Navigation -->
            <nav class="navbar navbar-inverse navbar-fixed-top" role="navigation">
                <div class="navbar-header">
                    <a class="navbar-brand" href="#"><img class="logo-image d-none d-lg-inline-block" src="../images/grupo-abc-logo.png" alt="Grupo ABC" width="65px" height="auto"></a>
                </div>

                <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
                    <span class="sr-only">Toggle navigation</span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>

                <ul class="nav navbar-nav navbar-left navbar-top-links">
                    <li><a href="listar_registros.php"><i class="fa fa-home fa-fw"></i> Promotores</a></li>
                </ul>
				<ul class="nav navbar-nav navbar-left navbar-top-links">
                    <li><a href="listar_fornecedores.php"><i class="fa fa-home fa-fw"></i> Fornecedores</a></li>
                </ul>
				
                <ul class="nav navbar-right navbar-top-links">
                    <li class="dropdown">
                        <a class="dropdown-toggle" data-toggle="dropdown" href="#">
                            <i class="fa fa-user fa-fw"></i> Área do Usuário <b class="caret"></b>
                        </a>
                        <ul class="dropdown-menu dropdown-user">
                            <li>
                                <a href="../user-senha.php"><i class="fa fa-gear fa-fw"></i> Mudar Senha</a>
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

            <aside class="sidebar navbar-default" role="navigation">
                <div class="sidebar-nav navbar-collapse">
                    <ul class="nav" id="side-menu">
                        <li>
                            <a href="../dashboard.php"><i class="fa fa-dashboard fa-fw"></i> Painel</a>
                        </li>
                        <li>
                            <a href="#"><i class="fa fa-bar-chart-o fa-fw"></i> Fornecedor<span class="fa arrow"></span></a>
                            <ul class="nav nav-second-level">
                                <li>
                                    <a href="listar_fornecedores.php">Listar</a>
                                </li>
                                <li>
                                    <a href="importar_csv.php">Importar</a>
                                </li>
                            </ul>
                            <!-- /.nav-second-level -->
                        </li>
                        <li>
                            <a href="#"><i class="fa fa-sitemap fa-fw"></i> Promotor<span class="fa arrow"></span></a>
                            <ul class="nav nav-second-level">
                                <li>
                                    <a href="listar_registros.php">Listar</a>
                                </li>
                                <li>
                                    <a href="exportar_csv.php">Exportar</a>
                                </li>
                            </ul>
                            <!-- /.nav-second-level -->
                        </li>
						<li>
                            <a href="#"><i class="fa fa-user fa-fw"></i> Usuário<span class="fa arrow"></span></a>
                            <ul class="nav nav-second-level">
                                <li>
                                    <a href="listar_user.php">Listar</a>
                                </li>
                                <li>
                                    <a href="registrar.php">Registrar</a>
                                </li>
                            </ul>
                            <!-- /.nav-second-level -->
                        </li>
                    </ul>
                </div>
            </aside>
            <!-- /.sidebar Fim menu -->

            <div id="page-wrapper">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-lg-12">
                            <h1 class="page-header">Listagem de Fornecedores</h1>
                        </div>
                        <!-- /.col-lg-12 -->
                    </div>
                    <!-- /.row -->
                   <div class="row">
                        <div class="col-lg-12">
<div class="panel-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped table-bordered table-hover" id="dataTables-example">
        <thead>
            <tr>
                <th>SEQFORNECEDOR</th>
                <th>SEQREDE</th>
                <th>NOME FANTASIA</th>
                <th>DESCRIÇÃO</th>
                <th>AÇÕES</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['SEQFORNECEDOR']) ?></td>
                    <td><?= htmlspecialchars($row['SEQREDE']) ?></td>
                    <td><?= htmlspecialchars($row['NOME_FANTASIA']) ?></td>
                    <td><?= htmlspecialchars($row['DESCRICAO']) ?></td>
                    <td>
                        <a href="editar_fornecedor.php?seqfornecedor=<?= $row['SEQFORNECEDOR'] ?>&seqrede=<?= $row['SEQREDE'] ?>">Editar</a> |
                        <a href="javascript:void(0);" onclick="confirmDelete(<?= $row['SEQFORNECEDOR'] ?>, <?= $row['SEQREDE'] ?>);">Excluir</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
                            </div>
                    </div>
                    <!-- table -->
                     
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

<?php include_once ROOT_PATH.'system/includes/code_footer.php'; ?>

<?php include_once ROOT_PATH.'system/includes/footer.php'; ?>