<?php
// public/modules/gestao_ativos/ativos-form.php
ini_set('display_errors',1); ini_set('display_startup_errors',1); error_reporting(E_ALL);

require_once __DIR__.'/../../../../config.php';
require_once ROOT_PATH.'/system/config/autenticacao.php';
require_once ROOT_PATH.'/system/config/connect.php';
require_once ROOT_PATH.'modules/gestao_terceiros/config/gest_connect.php'; // Inclui o arquivo de conexão
if (session_status()===PHP_SESSION_NONE) session_start();
proteger_pagina();

// Verifica se o ID foi passado como parâmetro
if (!isset($_GET['id'])) {
    die("ID não fornecido.");
}

$id = $_GET['id'];

// Busca o registro atual
$sql = "SELECT nome_completo, cpf, tem_agencia, nome_agencia, celular, nome_fantasia, aceitou_termos, categoria, email, password_hash FROM promotores WHERE id = ?";
$stmt = $conn_terc->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("Registro não encontrado.");
}

$registro = $result->fetch_assoc();

// Carrega os fornecedores
$sql_fornecedores = "SELECT seqfornecedor, seqrede, nome_fantasia FROM lista_fornecedores";
$result_fornecedores = $conn_terc->query($sql_fornecedores);
$fornecedores = $result_fornecedores->fetch_all(MYSQLI_ASSOC);

// Atualiza o registro se o formulário foi submetido
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome_completo = $_POST['nome_completo'];
    $cpf = $_POST['cpf'];
    $tem_agencia = $_POST['tem_agencia'] == 'Sim' ? 1 : 0;
    $nome_agencia = $tem_agencia ? $_POST['nome_agencia'] : null;
    $celular = $_POST['celular'];
    $categoria = $_POST['categoria'];
    $email = $_POST['email'];
    $password_hash = $registro['password_hash'];
if (!empty($_POST['nova_senha'])) {
    $password_hash = password_hash($_POST['nova_senha'], PASSWORD_DEFAULT);
}

    // Extrai seqfornecedor, seqrede e nome_fantasia do valor do select
    list($seqfornecedor, $seqrede, $nome_fantasia) = explode('|', $_POST['fornecedor']);

    $sql_update = "UPDATE promotores SET nome_completo = ?, cpf = ?, tem_agencia = ?, nome_agencia = ?, seqfornecedor = ?, seqrede = ?, nome_fantasia = ?, celular = ?, categoria = ?, email = ?, password_hash = ? WHERE id = ?";
    $stmt_update = $conn_terc->prepare($sql_update);
    $stmt_update->bind_param("ssississsssi", $nome_completo, $cpf, $tem_agencia, $nome_agencia, $seqfornecedor, $seqrede, $nome_fantasia, $celular, $categoria, $email, $password_hash, $id);

    if ($stmt_update->execute()) {
        header("Location: listar_terceiros.php?mensagem=" . urlencode("Dados salvos com sucesso!"));
    	exit();
    } else {
        echo "Erro ao atualizar: " . $stmt_update->error;
    }

    $stmt_update->close();
}

$stmt->close();
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
            <input type="text" id="nome_agencia" name="nome_agencia" value="<?= htmlspecialchars($registro['nome_agencia']) ?>">
        </div>
        
		<label for="fornecedor">Fornecedor:</label>
		<select id="fornecedor" name="fornecedor">
			<?php foreach ($fornecedores as $fornecedor): ?>
				<option value="<?php echo $fornecedor['seqfornecedor'] . '|' . $fornecedor['seqrede'] . '|' . htmlspecialchars($fornecedor['nome_fantasia']); ?>">
					<?php echo htmlspecialchars($fornecedor['nome_fantasia']); ?>
				</option>
			<?php endforeach; ?>
		</select>

        <label for="celular">Celular:</label>
        <input type="text" id="celular" name="celular" value="<?= htmlspecialchars($registro['celular']) ?>" required>
        
        <label for="categoria">Categoria:</label>
        <select id="categoria" name="categoria" required onchange="showDescription()">
            
            			<option value="">Selecionar</option>
			
			<option value="consultor_ou_auditor" <?= $registro['categoria'] === 'consultor_ou_auditor' ? 'selected' : '' ?>>Auditor / Consultor</option>
			
			<option value="entregador" <?= $registro['categoria'] === 'entregador' ? 'selected' : '' ?>>Entregador</option>
			
			<option value="limpeza_e_conservacao" <?= $registro['categoria'] === 'limpeza_e_conservacao' ? 'selected' : '' ?>>Limpeza e Conservação</option>
			
			<option value="motorista" <?= $registro['categoria'] === 'motorista' ? 'selected' : '' ?>>Motorista</option>
			
			<option value="promotor_de_vendas_ou_repositor" <?= $registro['categoria'] === 'promotor_de_vendas_ou_repositor' ? 'selected' : '' ?>>Promotor de Vendas / Repositor</option>
			
			<option value="seguranca_terceirizada" <?= $registro['categoria'] === 'seguranca_terceirizada' ? 'selected' : '' ?>>Segurança Terceirizada</option>
			
			<option value="servicos_de_marketing" <?= $registro['categoria'] === 'servicos_de_marketing' ? 'selected' : '' ?>>Serviços de Marketing</option>
			
			<option value="tecnicos_de_manutencao" <?= $registro['categoria'] === 'tecnicos_de_manutencao' ? 'selected' : '' ?>>Técnicos de Manutenção</option>
			
			<option value="tecnicos_de_telefonia_e_ti" <?= $registro['categoria'] === 'tecnicos_de_telefonia_e_ti' ? 'selected' : '' ?>>Técnicos de Telefonia / Ti</option>
        </select>
        
        <label for="email">E-mail:</label>
        <input type="text" id="email" name="email" value="<?= htmlspecialchars($registro['email']) ?>" required>
        
        <label for="nova_senha">Nova Senha (preencha apenas para criar ou atualizar):</label>
<input type="password" id="nova_senha" name="nova_senha">

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

<?php include_once ROOT_PATH.'system/includes/code_footer.php'; ?>

<?php include_once ROOT_PATH.'system/includes/footer.php'; ?>