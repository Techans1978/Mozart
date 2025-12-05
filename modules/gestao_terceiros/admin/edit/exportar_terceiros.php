<?php
ini_set('display_errors',1); ini_set('display_startup_errors',1); error_reporting(E_ALL);

ob_start(); // Inicia o output buffering

require_once __DIR__.'/../../../../config.php';
require_once ROOT_PATH.'/system/config/autenticacao.php';
require_once ROOT_PATH.'/system/config/connect.php';
require_once ROOT_PATH.'modules/gestao_terceiros/config/gest_connect.php'; // Inclui o arquivo de conexão
if (session_status()===PHP_SESSION_NONE) session_start();
proteger_pagina();

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=promotores.csv');

$output = fopen('php://output', 'w');

// Cabeçalhos do CSV
fputcsv($output, ['NOME COMPLETO', 'CPF', 'TEM AGÊNCIA', 'NOME DA AGÊNCIA', 'CELULAR', 'SEQFORNECEDOR', 'SEQREDE', 'FORNECEDOR', 'ACEITOU OS TERMOS', 'DATA DO CADASTRO'], ';');

$sql = "SELECT nome_completo, cpf, tem_agencia, nome_agencia, celular, seqfornecedor, seqrede, nome_fantasia, aceitou_termos, data_cadastro FROM promotores";
$result = $conn_terc->query($sql);

while ($row = $result->fetch_assoc()) {
    // Mapeia os valores de booleanos para 'Sim' ou 'Não'
    $row['tem_agencia'] = $row['tem_agencia'] ? 'Sim' : 'Não';
    $row['aceitou_termos'] = $row['aceitou_termos'] ? 'Sim' : 'Não';
    
    // Formata a data de cadastro
    $row['data_cadastro'] = date('d/m/Y H:i:s', strtotime($row['data_cadastro']));

    fputcsv($output, $row, ';');
}

fclose($output);
$conn_terc->close();

ob_end_flush(); // Finaliza o output buffering
?>
