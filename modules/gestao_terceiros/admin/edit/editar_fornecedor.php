<?php
// public/modules/gestao_ativos/ativos-form.php
ini_set('display_errors',1); ini_set('display_startup_errors',1); error_reporting(E_ALL);

require_once __DIR__.'/../../../../config.php';
require_once ROOT_PATH.'/system/config/autenticacao.php';
require_once ROOT_PATH.'/system/config/connect.php';
require_once ROOT_PATH.'modules/gestao_terceiros/config/gest_connect.php'; // Inclui o arquivo de conexão
if (session_status()===PHP_SESSION_NONE) session_start();
proteger_pagina();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $seqfornecedor = $_POST['seqfornecedor'];
    $seqrede = $_POST['seqrede'];
	$nome_fantasia = $_POST['nome_fantasia'];
	$descricao = $_POST['descricao'];

    // Atualiza o fornecedor no banco de dados
    $sql = "UPDATE lista_fornecedores SET seqrede = ?, nome_fantasia = ?, descricao = ? WHERE seqfornecedor = ?";
    $stmt = $conn_terc->prepare($sql);
    $stmt->bind_param("sssi", $seqrede, $nome_fantasia, $descricao, $seqfornecedor);

    if ($stmt->execute()) {
        echo "Fornecedor atualizado com sucesso!";
    } else {
        echo "Erro ao atualizar fornecedor: " . $stmt->error;
    }

    $stmt->close();
} else {
    // Obtém o fornecedor pelo ID
    $id = $_GET['seqfornecedor'];
    $sql = "SELECT seqfornecedor, seqrede, nome_fantasia, descricao FROM lista_fornecedores WHERE seqfornecedor = ?";
    $stmt = $conn_terc->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $fornecedor = $result->fetch_assoc();
}

$conn_terc->close();

?>

<?php include_once ROOT_PATH.'system/includes/head.php'; ?>
		
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
		
<?php include_once ROOT_PATH.'system/includes/navbar.php'; ?>

        <div id="wrapper">
            <div id="page-wrapper">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-lg-12">
                            <h1 class="page-header">Editar Fornecedor</h1>
                        </div>
                        <!-- /.col-lg-12 -->
                    </div>
                    <!-- /.row -->
                   <div class="row">
                        <div class="col-lg-12">
    <form method="post">
        <label for="seqfornecedor">SEQFORNECEDOR:</label>
		<input type="text" name="seqfornecedor" value="<?= htmlspecialchars($fornecedor['seqfornecedor']) ?>" required>
		
		<label for="seqrede">SEQREDE:</label>
        <input type="text" id="seqrede" name="seqrede" value="<?= htmlspecialchars($fornecedor['seqrede']) ?>" required>
		
        <label for="nome_fantasia">Nome:</label>
        <input type="text" id="nome_fantasia" name="nome_fantasia" value="<?= htmlspecialchars($fornecedor['nome_fantasia']) ?>" required>
		
		<label for="descricao">Descricao:</label>
        <input type="text" id="descricao" name="descricao" value="<?= htmlspecialchars($fornecedor['descricao']) ?>" required>
		
        <button type="submit">Atualizar</button>
    </form>
    <br>
    <a href="listar_fornecedores.php">Voltar para a lista</a>
							
							
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