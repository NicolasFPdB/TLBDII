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
$token = "";
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
// CÓDIGO DO FORMULÁRIO DE NOVA SENHA/PASSWORD
            try {
            $message['general'] = "Requisição de reset da senha efetuada com sucesso!";
            $status['general'] = "ok";

            if (!RateLimitDb($pdo, $userIp, 'reset-token', 3, 600)) {
            $message['token'] = "Muitos pedidos de token enviados. Aguarde 10 minutos.";
            $status['token']  = "error";
            $message['check'] = "Bloqueado por excesso de tentativas.";
            $status['check']  = "error";
            }

            if (!$email) {
                $message['email'] = "E-mail inválido";
                $status['email'] = "error";
            }

            // Select SQL - Check do User
            $sql = "SELECT id FROM users WHERE email = :email";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                $token = sprintf("%06d", random_int(0, 999999));
                $expires = date('Y-m-d H:i:s', strtotime('+30 minutes'));

                $sql = "UPDATE users SET token = :token, token_expiration = :expires WHERE email = :email";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':token' => $token,
                    ':expires' => $expires,
                    ':email' => $email
                ]);

                TokenReset($email, $token);
            }
            
        $message["token"] = "Token enviado caso o usuário esteja cadastrado";
        $status["token"] = "ok";

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