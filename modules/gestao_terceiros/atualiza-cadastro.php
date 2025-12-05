<?php
require __DIR__ . '/config/conexao.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Verifica se o ID foi passado corretamente
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("ID inválido.");
}
$id = (int) $_GET['id'];

// Busca o registro atual
$sql = "SELECT nome_completo, cpf, tem_agencia, nome_agencia, celular, nome_fantasia, aceitou_termos, categoria, email, password_hash, seqfornecedor, seqrede FROM promotores WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Registro não encontrado.");
}
$registro = $result->fetch_assoc();

// Carrega os fornecedores
$sql_fornecedores = "SELECT seqfornecedor, seqrede, nome_fantasia FROM lista_fornecedores";
$result_fornecedores = $conn->query($sql_fornecedores);
$fornecedores = $result_fornecedores->fetch_all(MYSQLI_ASSOC);

// Atualiza o registro
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome_completo = trim($_POST['nome_completo'] ?? '');
    $cpf = preg_replace('/\D/', '', $_POST['cpf'] ?? '');
    $tem_agencia = ($_POST['tem_agencia'] ?? '') === 'Sim' ? 1 : 0;
    $nome_agencia = $tem_agencia ? trim($_POST['nome_agencia'] ?? '') : '';
    $celular = preg_replace('/\D/', '', $_POST['celular'] ?? '');
    $categoria = $_POST['categoria'] ?? '';
    $email = trim($_POST['email'] ?? '');
    $nova_senha = $_POST['nova_senha'] ?? '';
    $password_hash = $registro['password_hash'];

    // Validação básica
    if (!$nome_completo || !$cpf || !$celular || !$categoria || !$email) {
        die("Todos os campos obrigatórios devem ser preenchidos.");
    }

    // Atualiza a senha apenas se foi enviada
    if (!empty($nova_senha)) {
        $password_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
    }

    // Trata fornecedor
    $seqfornecedor = $registro['seqfornecedor'];
$seqrede = $registro['seqrede'];
$nome_fantasia = $registro['nome_fantasia'];

    // Atualiza no banco
    $sql_update = "UPDATE promotores SET 
        nome_completo = ?, cpf = ?, tem_agencia = ?, nome_agencia = ?, 
        seqfornecedor = ?, seqrede = ?, nome_fantasia = ?, celular = ?, 
        categoria = ?, email = ?, password_hash = ? 
        WHERE id = ?";
    
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param(
        "ssississsssi",
        $nome_completo,
        $cpf,
        $tem_agencia,
        $nome_agencia,
        $seqfornecedor,
        $seqrede,
        $nome_fantasia,
        $celular,
        $categoria,
        $email,
        $password_hash,
        $id
    );

    if ($stmt_update->execute()) {
        header("Location: listar_registros.php?mensagem=" . urlencode("Dados atualizados com sucesso!"));
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
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Cadastro de Prestadores Terceiros</title>
<meta name="description" content="Cadastre-se na Gestão de Terceiros do Grupo ABC e otimize suas parcerias. Acesse oportunidades exclusivas e suporte especializado.">
<meta name="author" content="Grupo ABC!">

<link href="css/bootstrap.min.css" rel="stylesheet">
<link href="css/style.css" rel="stylesheet">

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
.cor-footer {
    background-color: #CCC;
}
button {
    padding: 10px;
    margin-top: 5px;
}
#description {
    font-style: italic;
    margin-top: 5px;
}
</style>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
<link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
</head>

<body>

<div class="container-fluid">
  <div class="row">
    <div class="col-md-3 text-center">
      <a href="index.html">
        <img src="https://grupo.superabc.com.br/images/grupo-abc-logo.png" alt="Grupo ABC" width="150px" height="auto">
      </a>
    </div>
    <div class="col-md-9">
      <br>
      <a href="painel">Painel de Terceiros</a> | 
      <a href="https://grupo.superabc.com.br" target="_blank">Grupo ABC</a> | 
      <a href="politica.html">Política de Privacidade</a>
    </div>
  </div>

  <div class="row">
    <div class="col-md-12">
      <img alt="Cadastro de Promotores" src="images/banner2.jpg" width="100%" height="auto">
    </div>
  </div>

  <br><br>

  <div id="page-wrapper">
    <div class="container-fluid">
      <div class="row">
        <div class="col-lg-12">
          <h1 class="page-header text-center">Editar Registro</h1><br><br>
        </div>
      </div>

      <div class="row">
        <div class="col-lg-12">
          <form method="post" action="">
            <label for="nome_completo">Nome Completo:</label>
            <input type="text" id="nome_completo" name="nome_completo" value="<?= htmlspecialchars($registro['nome_completo']) ?>" required>

            <label for="cpf">CPF:</label>
            <input type="text" id="cpf" name="cpf" value="<?= htmlspecialchars($registro['cpf']) ?>" required>

            <label for="tem_agencia">Tem agência?</label>
            <select id="tem_agencia" name="tem_agencia" aria-controls="nome_agencia" aria-expanded="<?= $registro['tem_agencia'] ? 'true' : 'false' ?>">
              <option value="Não" <?= !$registro['tem_agencia'] ? 'selected' : '' ?>>Não</option>
              <option value="Sim" <?= $registro['tem_agencia'] ? 'selected' : '' ?>>Sim</option>
            </select>

            <div id="nome_agencia" style="display: <?= $registro['tem_agencia'] ? 'block' : 'none' ?>;">
              <label for="nome_agencia">Nome da Agência:</label>
              <input type="text" id="nome_agencia" name="nome_agencia" value="<?= htmlspecialchars($registro['nome_agencia']) ?>">
            </div>
            
            <label for="celular">Celular:</label>
            <input type="tel" id="celular" name="celular" value="<?= htmlspecialchars($registro['celular']) ?>" required>

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
            <p id="description" aria-live="polite"></p>

            <label for="email">E-mail:</label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($registro['email']) ?>" required>

            <label for="nova_senha">Senha:</label>
            <input type="password" id="nova_senha" name="nova_senha" required>

            <button type="submit" class="btn btn-primary">Salvar Alterações</button>
          </form>

          <script>
            document.getElementById('tem_agencia').addEventListener('change', function() {
              var nomeAgenciaDiv = document.getElementById('nome_agencia');
              if (this.value === 'Sim') {
                nomeAgenciaDiv.style.display = 'block';
                this.setAttribute('aria-expanded', 'true');
              } else {
                nomeAgenciaDiv.style.display = 'none';
                this.setAttribute('aria-expanded', 'false');
              }
            });

            function showDescription() {
              const descriptions = {
                consultor_ou_auditor: 'Profissionais que realizam auditorias ou consultorias em diversas áreas, como segurança alimentar, finanças, etc.',
                entregador: 'Pessoas encarregadas de entregar mercadorias ao supermercado ou aos clientes, caso o supermercado ofereça serviços de entrega.',
                limpeza_e_conservacao: 'Equipes responsáveis pela limpeza e manutenção da higiene do supermercado.',
                motorista: 'Profissionais que transportam mercadorias, seja para entrega ou para abastecimento do supermercado.',
                promotor_de_vendas_ou_repositor: 'Profissionais responsáveis por promover produtos específicos dentro do supermercado, organizando displays e interagindo com os clientes para aumentar as vendas.',
                seguranca_terceirizada: 'Profissionais de segurança contratados para garantir a segurança do local.',
                servicos_de_marketing: 'Especialistas que ajudam o supermercado a planejar e executar estratégias de marketing e promoção.',
                tecnicos_de_manutencao: 'Especialistas responsáveis por realizar reparos e manutenção em equipamentos e instalações do supermercado.',
                tecnicos_de_telefonia_e_ti: 'Profissionais que prestam suporte técnico para sistemas de telefonia e tecnologia da informação do supermercado.',
              };

              const select = document.getElementById('categoria');
              const description = descriptions[select.value] || '';
              document.getElementById('description').textContent = description;
            }

            // Executa a função para mostrar a descrição no carregamento da página
            showDescription();
          </script>

        </div>
      </div>

    </div>
  </div>

  <br><br>

  <div class="row cor-footer">
    <div class="col-md-4 text-center"><br><br><a class="nav-link" href="painel">Painel de Terceiros</a><br><br></div>
    <div class="col-md-4 text-center"><br><br><a class="nav-link" href="https://grupo.superabc.com.br" target="_blank">Grupo ABC</a><br><br></div>
    <div class="col-md-4 text-center"><br><br><a class="nav-link" href="politica.html">Política de Privacidade</a><br><br></div>
  </div>

</div>

<script src="js/bootstrap.min.js"></script>
<script src="js/scripts.js"></script>

</body>
</html>


<?php
$conn->close();
?>