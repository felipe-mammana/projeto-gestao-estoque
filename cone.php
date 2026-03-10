<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Alterar a linha abaixo com os dados do servidor e banco de dados
$host = "localhost"; // Coloque o IP ou o domínio do servidor onde o MySQL está rodando
$username = "root";        // Coloque o nome do usuário MySQL
$password = "";   // Coloque a senha do usuário MySQL
$dbname = "estoqueti";     // Nome do banco de dados

// Cria a conexão
$cone = new mysqli($host, $username, $password, $dbname);

// Verifica se houve erro na conexão
if ($cone->connect_error) {
    die("Erro de conexão MySQL: " . $cone->connect_error);
}


?>
