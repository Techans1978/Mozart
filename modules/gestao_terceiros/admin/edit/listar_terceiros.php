<?php
// public/modules/gestao_ativos/ativos-form.php
ini_set('display_errors',1); ini_set('display_startup_errors',1); error_reporting(E_ALL);

require_once __DIR__.'/../../../../config.php';
require_once ROOT_PATH.'/system/config/autenticacao.php';
require_once ROOT_PATH.'/system/config/connect.php';
require_once ROOT_PATH.'modules/gestao_terceiros/config/gest_connect.php'; // Inclui o arquivo de conexão
if (session_status()===PHP_SESSION_NONE) session_start();
proteger_pagina();

$sql = "SELECT id, nome_completo, cpf, tem_agencia, nome_agencia, seqfornecedor, seqrede, nome_fantasia, celular FROM promotores";
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
        function confirmDelete(id) {
            if (confirm('Tem certeza que deseja excluir este registro?')) {
                window.location.href = 'excluir_terceiro.php?id=' + id;
            }
        }
    </script>

<?php include_once ROOT_PATH.'system/includes/navbar.php'; ?>

        <div id="wrapper">

            <div id="page-wrapper">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-lg-12">
                            <h1 class="page-header">Listagem de Promotores</h1>
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
                <th>NOME COMPLETO</th>
                <th>CPF</th>
                <th>TEM AGÊNCIA</th>
                <th>NOME DA AGÊNCIA</th>
                <th>SEQFORNECEDOR</th>
                <th>SEQREDE</th>
                <th>FORNECEDOR</th>
                <th>CELULAR</th>
                <th>AÇÕES</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['nome_completo']) ?></td>
                    <td><?= htmlspecialchars($row['cpf']) ?></td>
                    <td><?= $row['tem_agencia'] ? 'Sim' : 'Não' ?></td>
                    <td><?= htmlspecialchars($row['nome_agencia']) ?></td>
                    <td><?= htmlspecialchars($row['seqfornecedor']) ?></td>
                    <td><?= htmlspecialchars($row['seqrede']) ?></td>
                    <td><?= htmlspecialchars($row['nome_fantasia']) ?></td>
                    <td><?= htmlspecialchars($row['celular']) ?></td>
                    <td>
                    <!-- Editar -->
                    <a href="editar_terceiro.php?id=<?= (int)$row['id'] ?>" title="Editar Promotor" aria-label="Editar Promotor">
                        <i class="fa fa-cog fa-fw"></i>
                    </a>

                    <!-- Baixar QR Code (GET) -->
                    <a href="baixar_qrcode_terceiro.php?id=<?= (int)$row['id'] ?>" title="Baixar Qr Code" aria-label="Baixar Qr Code">
                        <i class="fa fa-download fa-fw"></i>
                    </a>

                    <!-- Novo QR Code (GET simples + confirmação) -->
                    <a href="novo_qrcode_terceiros.php?id=<?= (int)$row['id'] ?>&force=1"
                    onclick="return confirm('Gerar um novo QR Code para <?= htmlspecialchars($row['nome_completo'], ENT_QUOTES, 'UTF-8') ?> e baixar? O arquivo anterior será substituído.');"
                    title="Novo Qr Code" aria-label="Novo Qr Code">
                    <i class="fa fa-undo fa-fw"></i>
                    </a>

                    <!-- Excluir -->
                    <a href="javascript:void(0);" onclick="confirmDelete(<?= (int)$row['id'] ?>);" title="Excluir Promotor" aria-label="Excluir Promotor">
                        <i class="fa fa-remove fa-fw"></i>
                    </a>
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

        <!-- Page-Level Demo Scripts - Tables - Use for reference -->
        <script>
            $(document).ready(function () {
                $('#dataTables-example').DataTable({
                    responsive: true
                });
            });
        </script>
        <script>
        function submitNovoQr(id){
        var f = document.getElementById('form-nq-' + id);
        if(!f) return;
        if (confirm('Gerar um novo QR Code para este promotor? O arquivo anterior será substituído.')) {
            f.submit();
        }
        }
        </script>



<?php include_once ROOT_PATH.'system/includes/footer.php'; ?>