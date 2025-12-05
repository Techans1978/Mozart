<?php
require '../conexao.php'; // Arquivo de conexПлкo com o banco de dados

$term = $_GET['term'];
$query = "SELECT NOME_FANTASIA FROM lista_fornecedores WHERE NOME_FANTASIA LIKE '%".$conn->real_escape_string($term)."%'";
$result = $conn->query($query);

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row['NOME_FANTASIA'];
}

echo json_encode($data);
?>