<?php
$servername = "localhost";
$username = "gestaodeterceiro_promoter";
$password = "Oldsmobile@1957!";
$dbname = "gestaodeterceiro_promoter";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("ConexПлкo falhou: " . $conn->connect_error);
}
?>