<?php
declare(strict_types=1);
require_once __DIR__ . '/../middleware/session_bootstrap.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/process.php';
header('Content-Type: application/json; charset=utf-8');

function tokenResponse(string $status, string $message, int $code = 200): never
{
    http_response_code($code);
    echo json_encode(['status' => $status, 'message' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}
try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') tokenResponse('error', 'Método não permitido.', 405);
    $pdo = db();
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    if (!RateLimitDb($pdo, $ip, 'reset-password', 5, 600)) tokenResponse('error', 'Muitas tentativas. Aguarde alguns minutos.', 429);

    $email = filter_var(trim((string) ($_POST['email'] ?? '')), FILTER_VALIDATE_EMAIL);
    $token = trim((string) ($_POST['token-numbers'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $confirmation = (string) ($_POST['password-confirm'] ?? '');
    if (!$email || !preg_match('/^\d{6}$/', $token)) tokenResponse('error', 'E-mail ou código inválido.', 422);
    if (strlen($password) < 8) tokenResponse('error', 'A nova senha deve possuir pelo menos 8 caracteres.', 422);
    if ($password !== $confirmation) tokenResponse('error', 'As senhas não coincidem.', 422);

    $stmt = $pdo->prepare('SELECT id, reset_token_hash, reset_token_expires_at FROM users WHERE email=:email LIMIT 1');
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();
    $valid = $user
        && !empty($user['reset_token_hash'])
        && !empty($user['reset_token_expires_at'])
        && strtotime($user['reset_token_expires_at']) >= time()
        && hash_equals($user['reset_token_hash'], hash('sha256', $token));
    if (!$valid) tokenResponse('error', 'Código inválido ou expirado.', 422);

    $update = $pdo->prepare('UPDATE users SET senha=:senha, reset_token_hash=NULL, reset_token_expires_at=NULL WHERE id=:id');
    $update->execute([':senha' => password_hash($password, PASSWORD_DEFAULT), ':id' => $user['id']]);
    ResetRateLimit($pdo, $ip, 'reset-password');
    tokenResponse('ok', 'Senha redefinida com sucesso. Faça login.');
} catch (Throwable $e) {
    error_log('Validação de token: ' . $e->getMessage());
    tokenResponse('error', 'Não foi possível redefinir a senha.', 500);
}
