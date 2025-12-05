<?php
require '../config/conexao.php'; // Arquivo de conexão com o banco de dados

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cpf = preg_replace('/\D/', '', $_POST['cpf'] ?? '');
    $celular = preg_replace('/\D/', '', $_POST['celular'] ?? '');

    if (empty($cpf) || empty($celular)) {
        exit("CPF e Celular são obrigatórios.");
    }

    $sql = "SELECT id, nome_completo, password_hash FROM promotores WHERE cpf = ? AND celular = ?";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        exit("Erro na preparação da consulta: " . $conn->error);
    }

    $stmt->bind_param("ss", $cpf, $celular);
    $stmt->execute();
    $result = $stmt->get_result();

    if (!$result || $result->num_rows === 0) {
        exit("Cadastro não encontrado. Verifique os dados informados.");
    }

    $dados = $result->fetch_assoc();

    $nome = trim($dados['nome_completo']);
    $primeiras_palavras = implode(' ', array_slice(explode(' ', $nome), 0, 1));

    // Exibe tela de confirmação
    ?>
    <!DOCTYPE html>
    <html lang="pt-br">
    <head>
        <meta charset="UTF-8">
        <title>Confirmação</title>
        <link href="../css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body class="container mt-5">
        <div class="alert alert-info mt-4">
            <h4>Confirmar Identidade</h4>
            Parte do nome encontrado: <strong><?= htmlspecialchars($primeiras_palavras) ?></strong><br><br>
            Se for você, clique em "Sim" para continuar.
        </div>

        <form action="atualiza-cadastro.php" method="get">
            <input type="hidden" name="id" value="<?= $dados['id'] ?>">
            <input type="hidden" name="preview_nome" value="<?= htmlspecialchars($primeiras_palavras) ?>">
            <button type="submit" class="btn btn-success">Sim, sou eu</button>
            <a href="index.html" class="btn btn-secondary">Não sou eu</a>
        </form>
    </body>
    </html>
    <?php
    exit();
}

exit("Método inválido.");
