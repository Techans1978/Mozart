<?php
// public/modules/gestao_terceiros/importar_fornecedores.php
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(E_ALL);

require_once __DIR__.'/../../../../config.php';
require_once ROOT_PATH.'/system/config/autenticacao.php';
require_once ROOT_PATH.'/system/config/connect.php';
require_once ROOT_PATH.'/modules/gestao_terceiros/config/gest_connect.php'; // $conn_terc
if (session_status()===PHP_SESSION_NONE) session_start();
proteger_pagina();

$mensagem = '';
$inseridos = 0;
$atualizados = 0;
$linhas_ignoradas = 0;
$erros = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file']['tmp_name'];

    if (($handle = fopen($file, "r")) !== false) {
        // Ignora a primeira linha (cabeçalhos)
        fgetcsv($handle, 1000, ";");

        while (($data = fgetcsv($handle, 1000, ";")) !== false) {
            // Ignora linhas em branco
            if (empty(array_filter($data))) {
                $linhas_ignoradas++;
                continue;
            }

            // Verifica se a linha contém ao menos 3 colunas
            if (count($data) < 3) {
                $linhas_ignoradas++;
                continue;
            }

            // Dados extraídos
            $seqfornecedor = (int)$data[0];
            $seqrede = $seqfornecedor; // se quiser usar a coluna 1 do CSV, troque para: (int)$data[1];
            $nome_fantasia = trim($data[1]);
            $descricao = trim($data[2]);
            
            // Se o nome fantasia estiver vazio, use a descrição
            if (empty($nome_fantasia)) {
                $nome_fantasia = $descricao;
            }

            $sql = "INSERT INTO lista_fornecedores (SEQFORNECEDOR, SEQREDE, NOME_FANTASIA, DESCRICAO) 
                    VALUES (?, ?, ?, ?) 
                    ON DUPLICATE KEY UPDATE 
                        SEQREDE = VALUES(SEQREDE), 
                        NOME_FANTASIA = VALUES(NOME_FANTASIA), 
                        DESCRICAO = VALUES(DESCRICAO)";

            $stmt = $conn_terc->prepare($sql);
            if (!$stmt) {
                $erros++;
                continue;
            }

            $stmt->bind_param("iiss", $seqfornecedor, $seqrede, $nome_fantasia, $descricao);

            if ($stmt->execute()) {
                /*
                  affected_rows:
                  - 1 = inserção nova
                  - 2 = houve UPDATE (linha já existia e foi alterada)
                  - 0 = duplicata mas dados iguais (nada mudou)
                */
                if ($stmt->affected_rows === 1) {
                    $inseridos++;
                } elseif ($stmt->affected_rows === 2) {
                    $atualizados++;
                } else {
                    // linha existente mas sem alteração real, vamos considerar ignorada
                    $linhas_ignoradas++;
                }
            } else {
                $erros++;
            }

            $stmt->close();
        }
        fclose($handle);

        $mensagem  = "Importação concluída!<br>";
        $mensagem .= "Inseridos: <strong>{$inseridos}</strong><br>";
        $mensagem .= "Atualizados: <strong>{$atualizados}</strong><br>";
        $mensagem .= "Linhas ignoradas: <strong>{$linhas_ignoradas}</strong><br>";
        $mensagem .= "Erros: <strong>{$erros}</strong>";
    } else {
        $mensagem = "Erro ao abrir o arquivo CSV.";
    }
}

$conn_terc->close();
?>

<?php include_once ROOT_PATH.'system/includes/head.php'; ?>
<?php include_once ROOT_PATH.'system/includes/navbar.php'; ?>

<div id="wrapper">
    <div id="page-wrapper">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-12">
                    <h1 class="page-header">Importar Fornecedores</h1>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-12">

                    <?php if (!empty($mensagem)): ?>
                        <div class="alert alert-info">
                            <?= $mensagem ?>
                        </div>
                    <?php endif; ?>

                    <h2>Importar Fornecedores via CSV</h2>
                    <form method="post" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="csv_file">Arquivo CSV:</label>
                            <input type="file" id="csv_file" name="csv_file" accept=".csv" required class="form-control">
                        </div>

                        <button type="submit" class="btn btn-primary">Importar</button>
                    </form>

                    <br><br>
                    <h3>Estrutura do arquivo CSV (cada linha = 1 fornecedor):</h3>
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>seqfornecedor</th>
                                <th>seqrede</th>
                                <th>nome_fantasia</th>
                                <th>descricao</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>1</td>
                                <td>001</td>
                                <td>Nome 1</td>
                                <td>Descrição 1</td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>002</td>
                                <td>Nome 2</td>
                                <td>Descrição 2</td>
                            </tr>
                        </tbody>
                    </table>

                    <br>
                    <a href="listar_fornecedores.php" class="btn btn-default">Voltar para a lista</a>

                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once ROOT_PATH.'system/includes/code_footer.php'; ?>
<?php include_once ROOT_PATH.'system/includes/footer.php'; ?>
