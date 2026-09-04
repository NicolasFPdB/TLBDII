<?php
$host = "localhost:3309";
$banco = "lrc1970";
$usuario = "root";
$senha = "";

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$banco;charset=utf8mb4",
        $usuario,
        $senha
    );

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Conexão realizada com sucesso! <br>";
}
catch (PDOException $e)
{
    die("Erro ao encontrar com o banco, filhô: " . $e->getMessage());
}

?>