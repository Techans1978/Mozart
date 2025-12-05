<?php
require 'lib/conexao.php';

$codigo = $_GET['cod'] ?? null;

if (!$codigo) {
    die("Código não fornecido.");
}

$stmt = $conn->prepare("SELECT nome_completo, empresa, categoria, celular, email, aceitou_termos FROM promotores WHERE codigo = ?");
$stmt->bind_param("s", $codigo);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Código inválido ou promotor não encontrado.");
}

$promotor = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Validação de Acesso</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f3f3f3; padding: 20px; }
        .card {
            background: #fff;
            max-width: 500px;
            margin: auto;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        h2 { color: #333; }
        .status {
            font-weight: bold;
            padding: 10px;
            color: #fff;
            margin-top: 15px;
            display: inline-block;
            border-radius: 4px;
        }
        .ativo { background: #28a745; }
        .inativo { background: #dc3545; }
    </style>
</head>
<body>

<div class="card">
    <h2>Identificação do Promotor</h2>
    <p><strong>Nome:</strong> <?= htmlspecialchars($promotor['nome_completo']) ?></p>
    <p><strong>Empresa:</strong> <?= htmlspecialchars($promotor['empresa']) ?></p>
    <p><strong>Categoria:</strong> <?= htmlspecialchars($promotor['categoria']) ?></p>
    <p><strong>Celular:</strong> <?= htmlspecialchars($promotor['celular']) ?></p>
    <p><strong>Email:</strong> <?= htmlspecialchars($promotor['email']) ?></p>

    <p>Status:
        <?php if ($promotor['aceitou_termos']): ?>
            <span class="status ativo">Autorizado</span>
        <?php else: ?>
            <span class="status inativo">Não autorizado</span>
        <?php endif; ?>
    </p>
</div>

</body>
</html>