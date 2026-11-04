<?php
declare(strict_types=1);
require_once __DIR__ . '/../middleware/session_bootstrap.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/process.php';
header('Content-Type: application/json; charset=utf-8');

function cadastroResponse(string $status, string $message, int $code = 200): never
{
    http_response_code($code);
    echo json_encode(['status' => $status, 'message' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') cadastroResponse('error', 'Método não permitido.', 405);
    $pdo = db();
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    if (!RateLimitDb($pdo, $ip, 'cadastro', 5, 600)) cadastroResponse('error', 'Muitas tentativas. Aguarde alguns minutos.', 429);

    $nome = trim((string) ($_POST['name'] ?? ''));
    $sobrenome = trim((string) ($_POST['sobrenome'] ?? ''));
    $email = filter_var(trim((string) ($_POST['email'] ?? '')), FILTER_VALIDATE_EMAIL);
    $senha = (string) ($_POST['password'] ?? '');

    if ($nome === '' || strlen($nome) > 50 || $sobrenome === '' || strlen($sobrenome) > 100)
        cadastroResponse('error', 'Informe nome e sobrenome válidos.', 422);
    if (!$email || strlen($email) > 100) cadastroResponse('error', 'Informe um e-mail válido.', 422);
    if (strlen($senha) < 8) cadastroResponse('error', 'A senha deve possuir pelo menos 8 caracteres.', 422);

    $check = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
    $check->execute([':email' => $email]);
    if ($check->fetch()) cadastroResponse('error', 'Este e-mail já está cadastrado.', 409);

    $insert = $pdo->prepare('INSERT INTO users (nome, sobrenome, email, senha, tipo) VALUES (:nome, :sobrenome, :email, :senha, \'cliente\')');
    $insert->execute([
        ':nome' => $nome,
        ':sobrenome' => $sobrenome,
        ':email' => $email,
        ':senha' => password_hash($senha, PASSWORD_DEFAULT),
    ]);
    ResetRateLimit($pdo, $ip, 'cadastro');
    cadastroResponse('ok', 'Cadastro realizado com sucesso. Faça login para continuar.');
} catch (PDOException $e) {
    error_log('Cadastro: ' . $e->getMessage());
    if ($e->getCode() === '23000') cadastroResponse('error', 'Este e-mail já está cadastrado.', 409);
    cadastroResponse('error', 'Não foi possível realizar o cadastro.', 500);
} catch (Throwable $e) {
    error_log('Cadastro: ' . $e->getMessage());
    cadastroResponse('error', 'Não foi possível realizar o cadastro.', 500);
}
