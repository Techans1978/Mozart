<?php
// modules/gestao_terceiros/config_terc.php (exemplo)

$host_terc = "localhost";
$user_terc = "mozarth1super_terceiros";
$pass_terc = "Oldsmobile@1957!";
$db_terc   = "mozarth1super_terceiros";

$conn_terc = new mysqli($host_terc, $user_terc, $pass_terc, $db_terc);

if ($conn_terc->connect_error) {
    die("Erro na conexão com o banco de terceiros: " . $conn_terc->connect_error);
}

$conn_terc->set_charset('utf8mb4');
