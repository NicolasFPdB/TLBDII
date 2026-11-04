<?php
declare(strict_types=1);
if (session_status() !== PHP_SESSION_ACTIVE)
    session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/config.php';

$pdo = db();
require_once __DIR__ . '/process.php';


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
// CÓDIGO DO FORMULÁRIO DE LOGIN
try {
    $message['general'] = "Requisição forms login efetuada com sucesso!";
    $status['general'] = "ok";

    // Proteção - Brute Force
    if (!RateLimitDb($pdo, $userIp, 'login', 5, 300)) {

        http_response_code(429);

        echo json_encode([
            'status' => 'error',
            'message' => 'Muitas tentativas. Aguarde alguns minutos.'
        ]);

        exit;
    }

    if ($email && !empty($password)) {
        $message['check'] = "Campos preenchidos com sucesso!";
        $status['check'] = "ok";

        // Select SQL - Check do User
        $sql = "SELECT id, senha, nome, tipo FROM users WHERE email = :email";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Checagem do login
        if ($user && password_verify($password, $user['senha'])) {
            session_regenerate_id(true);

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['nome'];
            $_SESSION['user_type'] = $user['tipo'];
            $_SESSION['logged_in'] = true;

            ResetRateLimit($pdo, $userIp, 'login');

            $message['check'] = "Sucesso ao logar!";
            $status['check'] = "ok";
        } else {
            $message['check'] = "Login inválido!";
            $status['check'] = "error";
        }
    } else {
        $message['check'] = "Campos vazios!";
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