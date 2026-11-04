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
$token = null;
$userIp = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

// Declaração - objetos
$message = [
    'general' => "",
    'check' => "",
    'name' => "",
    'email' => "",
    'password' => "",
    'token' => "",
    'cep' => ""
];
$status = [
    'general' => "",
    'check' => "",
    'name' => "",
    'email' => "",
    'password' => "",
    'token' => "",
    'cep' => ""
];

// CÓDIGO DO FORMULÁRIO DE ENVIO DO TOKEN
       try {         
                $message['general'] = "Requisição da nova senha efetuada com sucesso!";
                $status['general'] = "ok";
                            
                // Proteção - Brute Force
                if (!RateLimitdB($pdo, $userIp, 'login', 5, 300)) {
                $message['check'] = "Muitas tentativas de troca de senha. Aguarde 5 minutos e tente novamente.";
                $status['check']  = "error";
                }
                
                if (!$email) {
                    $message['email'] = "E-mail inválido";
                    $status['email'] = "error";
                }

                // Select SQL - Check do User
                $sql = "SELECT id, token, token_expiration FROM users WHERE email = :email";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([':email' => $email]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($user && !empty($user['token'])) {
                    $message['check'] = "User encontrado com sucesso!";
                    $status['check'] = "ok";
                
                    $userToken = $_POST['token-numbers'] ?? "";
                    $now = date('Y-m-d H:i:s');
                
                    if (hash_equals($userToken, $user['token']) && $user['token_expiration'] >= $now) {
                        $message['token'] = "Token válido";
                        $status['token'] = "ok";
                    } else {
                        $message['token'] = "Token inválido";
                        $status['token'] = "error";
                        $message['check'] = "Token inválido";
                        $status['check'] = "error";
                    }
                
                    // Sanitização de dados - Nova senha do User
                    if (strlen($password) < 6) {
                        $message['password'] = "Senha deve conter no mínimo 6 caracteres";
                        $status['password'] = "error";
                    } else {
                        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                
                        // Update SQL
                        $sql = "UPDATE users SET senha = :senha, token = NULL, token_expiration = NULL WHERE email = :email";
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([
                            ':email' => $email,
                            ':senha' => $passwordHash
                        ]);
                        $message['password'] = "Senha atualizada!";
                        $status['password'] = "ok";
                    }
                } else {
                    $message['check'] = "User não encontrado";
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