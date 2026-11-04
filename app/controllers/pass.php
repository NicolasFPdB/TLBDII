<?php
declare(strict_types=1);
require_once __DIR__ . '/../middleware/session_bootstrap.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/process.php';
require_once __DIR__ . '/../services/mail.php';
header('Content-Type: application/json; charset=utf-8');

function resetRequestResponse(string $status, string $message, int $code = 200): never
{
    http_response_code($code);
    echo json_encode(['status' => $status, 'message' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}
try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') resetRequestResponse('error', 'Método não permitido.', 405);
    $pdo = db();
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    if (!RateLimitDb($pdo, $ip, 'reset-token', 3, 600)) resetRequestResponse('error', 'Muitos pedidos. Aguarde 10 minutos.', 429);
    $email = filter_var(trim((string) ($_POST['email'] ?? '')), FILTER_VALIDATE_EMAIL);
    if (!$email) resetRequestResponse('error', 'Informe um e-mail válido.', 422);

    // Resposta igual para contas existentes e inexistentes, evitando enumeração.
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();
    if ($user) {
        $token = (string) random_int(100000, 999999);
        $hash = hash('sha256', $token);
        $expires = (new DateTimeImmutable('+30 minutes'))->format('Y-m-d H:i:s');
        $save = $pdo->prepare('UPDATE users SET reset_token_hash=:hash, reset_token_expires_at=:expires WHERE id=:id');
        $save->execute([':hash' => $hash, ':expires' => $expires, ':id' => $user['id']]);
        if (!TokenReset($email, $token)) {
            error_log('Solicitação de redefinição registrada, mas o e-mail não pôde ser enviado.');
        }
    }
    resetRequestResponse('ok', 'Se o e-mail estiver cadastrado, você receberá um código de redefinição.');
} catch (Throwable $e) {
    error_log('Solicitação de reset: ' . $e->getMessage());
    resetRequestResponse('error', 'Não foi possível processar a solicitação.', 500);
}
