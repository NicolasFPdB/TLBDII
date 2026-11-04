<?php
declare(strict_types=1);
require_once __DIR__ . '/../middleware/session_bootstrap.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/process.php';
header('Content-Type: application/json; charset=utf-8');

function loginResponse(string $status, string $message, int $code = 200): never
{
    http_response_code($code);
    echo json_encode(['status' => $status, 'message' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}
try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') loginResponse('error', 'Método não permitido.', 405);
    $pdo = db();
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    if (!RateLimitDb($pdo, $ip, 'login', 5, 300)) loginResponse('error', 'Muitas tentativas. Aguarde alguns minutos.', 429);
    $email = filter_var(trim((string) ($_POST['email'] ?? '')), FILTER_VALIDATE_EMAIL);
    $senha = (string) ($_POST['password'] ?? '');
    if (!$email || $senha === '') loginResponse('error', 'Informe e-mail e senha válidos.', 422);

    $stmt = $pdo->prepare('SELECT id, nome, senha, tipo FROM users WHERE email = :email LIMIT 1');
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();
    if (!$user || !password_verify($senha, $user['senha'])) loginResponse('error', 'E-mail ou senha incorretos.', 401);

    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['user_name'] = $user['nome'];
    $_SESSION['user_type'] = $user['tipo'];
    $_SESSION['logged_in'] = true;
    ResetRateLimit($pdo, $ip, 'login');
    loginResponse('ok', 'Login realizado com sucesso.');
} catch (Throwable $e) {
    error_log('Login: ' . $e->getMessage());
    loginResponse('error', 'Não foi possível realizar o login.', 500);
}
