<?php
require '../config/conexao.php'; // Arquivo de conexão com o banco de dados

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

function sanitize_input($data) {
    return htmlspecialchars(trim((string)$data)); // Convert to string before trim
}

$nome_completo = sanitize_input($_POST['nome_completo'] ?? '');
$cpf = sanitize_input($_POST['cpf'] ?? '');
$categoria = sanitize_input($_POST['categoria'] ?? ''); // Variável categoria
$nome_fantasia = sanitize_input($_POST['fornecedor'] ?? '');
$tem_agencia = ($_POST['tem_agencia'] ?? 'Não') === 'Sim' ? 1 : 0;
$nome_agencia = $tem_agencia ? sanitize_input($_POST['nome_agencia'] ?? '') : null;
$celular = sanitize_input($_POST['celular'] ?? '');
$aceitou_termos = isset($_POST['aceite_termos']) ? 1 : 0;
$email = sanitize_input($_POST['email'] ?? '');
$senha_digitada = $_POST['senha'] ?? '';
$password_hash = password_hash(trim($senha_digitada), PASSWORD_DEFAULT);

if (empty($nome_completo) || empty($cpf) || empty($nome_fantasia) || empty($celular)) {
    header("Location: ../includes/erro-campos.html?mensagem=" . urlencode("Por favor, preencha todos os campos obrigatórios."));
    exit(); // Certifique-se de sair após o redirecionamento
}

if (!preg_match('/^\d{11}$/', $cpf)) {
    header("Location: ../includes/erro-cpf.html?mensagem=" . urlencode("CPF inválido! Deve ser colocado apenas números. Deve conter 11 dígitos numéricos."));
    exit();
}

if ($tem_agencia && empty($nome_agencia)) {
    header("Location: ../includes/erro-nome-agencia.html?mensagem=" . urlencode("Por favor, preencha o nome da agência."));
    exit();
}

$sql_check_lista_fornecedores = "SELECT SEQFORNECEDOR, SEQREDE FROM lista_fornecedores WHERE NOME_FANTASIA = ?";
$stmt_check_lista_fornecedores = $conn->prepare($sql_check_lista_fornecedores);
$stmt_check_lista_fornecedores->bind_param("s", $nome_fantasia);
$stmt_check_lista_fornecedores->execute();
$stmt_check_lista_fornecedores->store_result();

if ($stmt_check_lista_fornecedores->num_rows === 0) {
    header("Location: ../includes/erro-fornecedor.html?mensagem=" . urlencode("Fornecedor não encontrado no banco de dados."));
    exit();
}

$stmt_check_lista_fornecedores->bind_result($seqfornecedor, $seqrede);
$stmt_check_lista_fornecedores->fetch();

$sql_check = "SELECT id FROM promotores WHERE cpf = ?";
$stmt_check = $conn->prepare($sql_check);
$stmt_check->bind_param("s", $cpf);
$stmt_check->execute();
$stmt_check->store_result();

if ($stmt_check->num_rows > 0) {
    header("Location: ../includes/erro-cpf-cadastrado.html?mensagem=" . urlencode("CPF já cadastrado!"));
    exit();
} else {
    $sql = "INSERT INTO promotores (nome_completo, cpf, seqfornecedor, seqrede, nome_fantasia, categoria, tem_agencia, nome_agencia, celular, aceitou_termos, email, password_hash ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssiisssssiss", $nome_completo, $cpf, $seqfornecedor, $seqrede, $nome_fantasia, $categoria, $tem_agencia, $nome_agencia, $celular, $aceitou_termos, $email, $password_hash);

    if ($stmt->execute()) {
        header("Location: ../includes/ok-sucesso.html?mensagem=" . urlencode("Dados salvos com sucesso!"));
        exit();
    } else {
        echo "Erro: " . $stmt->error;
    }

    $stmt->close();
}
?>
