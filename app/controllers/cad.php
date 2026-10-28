<?php

// Conexão com a Database
    include_once('conection.php');
// Conexão de functions
    require_once('process.php');

// Declaração - Variáveis
$name = trim($_POST['name'] ?? "");
$emailCheck = $_POST["email"] ?? "";
$email = filter_var($emailCheck, FILTER_VALIDATE_EMAIL);
$password = $_POST['password'] ?? "";
$cep = trim($_POST['cep'] ?? "");
$userIp = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

// Declaração - objetos
$message = [
    'general' => "",
    'check' => "",
    'name' => "",
    'email' => "",
    'password' => "",
    'cep' => ""
];
$status = [
    'general' => "",
    'check' => "",
    'name' => "",
    'email' => "",
    'password' => "",
    'cep' => ""
];

try {
        // CÓDIGO DO FORMULÁRIO DE CADASTRO
            $message['general'] = "Requisição forms cadastro efetuada com sucesso!";
            $status['general'] = "ok";

            // Proteção - Automatizada de criação de contas
            if (!RateLimitDb($pdo, $userIp, 'cadastro', 10, 600)) {
                $message['check'] = "Muitas tentativas de cadastro. Aguarde alguns minutos.";
                $status['check']  = "error";
            }

            if (!$email) {
                $message['email'] = "E-mail inválido";
                $status['email'] = "error";
                $message['check'] = "Cadastro inválido";
                $status['check'] = "error";
            }

            // Select SQL - Check do User
            $Sql = "SELECT id FROM users WHERE email = :email";
            $stmt = $pdo->prepare($Sql);
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                // Sanitização de dados - Nome do User
                $aux = array_values(array_filter(explode(" ", $name)));
                if (!empty($aux)) {
                    $name = (count($aux) > 1) ? $aux[0] . " " . end($aux) : $aux[0];
                    $message['name'] = "Nome cadastrado com sucesso!";
                    $status['name'] = "ok";
                } else {
                    $message['name'] = "Erro ao cadastrar user!";
                    $status['name'] = "error";
                }

                // Sanitização de dados - Senha do user
                if (strlen($password) < 6) {
                    $message['password'] = "Senha deve conter no mínimo 6 caracteres";
                    $status['password'] = "error";
                } else {
                    $message['password'] = "Senha cadastrada com sucesso!";
                    $status['password'] = "ok";
                }

                // Sanitização de dados - CEP do user
                if (!empty($cep) && preg_match('/^\d{5}-?\d{3}$/', $cep)) {
                    $message['cep'] = "CEP cadastrado com sucesso!";
                    $status['cep'] = "ok";
                } else {
                    $message['cep'] = "CEP inválido!";
                    $status['cep'] = "error";
                }

                if ($status['name'] === "ok" && $status['password'] === "ok" && $status['cep'] === "ok") {
                    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

                    // Insert SQL - Cadastro do Usuário na database
                    $sql = "INSERT INTO users (nome, email, senha, cep) VALUES (:nome, :email, :senha, :cep);";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        ':nome' => $name,
                        ':email' => $email,
                        ':senha' => $passwordHash,
                        ':cep' => $cep
                    ]);

                    $message['email'] = "E-mail cadastrado com sucesso!";
                    $status['email'] = "ok";
                    $message['check'] = "Cadastro efetuado com sucesso!";
                    $status['check'] = "ok";
                } else {
                    $message['check'] = "Cadastro inválido!";
                    $status['check'] = "error";
                }

            } else {
                $message['check'] = "Usuário já cadastrado!";
                $status['check'] = "error";
            }

} catch (PDOException $e) {
    $message['general'] = "Falha nas requisições!";
    $status['general'] = "error";
}

// Output JSON
$data = array(
    'name' => $name,
    'email' => $email,
    'cep' => $cep,
    'status' => $status,
    'message' => $message
);

echo json_encode($data, JSON_UNESCAPED_UNICODE);
?>