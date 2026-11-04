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
$sobrenome = trim($_POST['sobrenome'] ?? '');
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
    respond([
        'status' => 'ok',
        'message' => 'Formulário de cadastro recebido com sucesso.'
    ], 200);

    // Proteção - Automatizada de criação de contas
    if (!RateLimitDb($pdo, $userIp, 'cadastro', 10, 600)) {
        respond([
            'status' => 'error',
            'message' => 'Muitas tentativas de cadastro. Aguarde alguns minutos.'
        ], 429);
    }

    if (!$email) {
        respond([
            'status' => 'error',
            'message' => 'E-mail inválido.'
        ], 422);
        exit;
    }

    // Select SQL - Check do User
    $Sql = "SELECT id FROM users WHERE email = :email";
    $stmt = $pdo->prepare($Sql);
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {

        if ($name === '' || $sobrenome === '') {
            respond([
                'status' => 'error',
                'message' => 'Informe nome e sobrenome.'
            ], 422);
        }
        // Sanitização de dados - Nome do User
        $aux = array_values(array_filter(explode(" ", $name)));
        if (!empty($aux)) {
            $name = (count($aux) > 1) ? $aux[0] . " " . end($aux) : $aux[0];
            respond([
                'status' => 'ok',
                'message' => 'Nome cadastrado com sucesso.'
            ], 200);
        } else {
            respond([
                'status' => 'error',
                'message' => 'Informe um nome válido.'
            ], 422);
        }


        if (!$email) {
            respond([
                'status' => 'error',
                'message' => 'Informe um e-mail válido.'
            ], 422);
        }

        if (strlen($password) < 8) {
            respond([
                'status' => 'error',
                'message' => 'A senha deve possuir pelo menos 8 caracteres.'
            ], 422);
        }

        // Sanitização de dados - Senha do user
        if (strlen($password) < 6) {
            respond([
                'status' => 'error',
                'message' => 'A senha deve possuir pelo menos 6 caracteres.'
            ], 422);
        } else {
            respond([
                'status' => 'ok',
                'message' => 'Senha cadastrada com sucesso.'
            ], 200);
        }

        // Sanitização de dados - CEP do user
        if (!empty($cep) && preg_match('/^\d{5}-?\d{3}$/', $cep)) {
            respond([
                'status' => 'ok',
                'message' => 'CEP cadastrado com sucesso.'
            ], 200);
        } else {
            respond([
                'status' => 'error',
                'message' => 'CEP inválido!'
            ], 422);
        }

        if ($status['name'] === "ok" && $status['password'] === "ok" && $status['cep'] === "ok") {
            $passwordHash = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            // Insert SQL - Cadastro do Usuário na database
            $sql = "INSERT INTO users
        (nome, sobrenome, email, senha, tipo)
        VALUES
        (:nome, :sobrenome, :email, :senha, 'cliente')";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                ':nome' => $name,
                ':sobrenome' => $sobrenome,
                ':email' => $email,
                ':senha' => $passwordHash
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


} catch (Throwable $e) {

    error_log(
        'Erro no cadastro: ' . $e->getMessage()
    );

    http_response_code(500);

    echo json_encode([
        'status' => 'error',
        'message' => 'Não foi possível realizar o cadastro.'
    ]);

    exit;
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