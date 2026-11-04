<?php
declare(strict_types=1);
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/conection.php';
require_once __DIR__ . '/process.php';

$out = ['status' => 'error', 'message' => 'Não foi possível processar a solicitação.'];
try {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    if (!RateLimitDb($pdo, $ip, 'alteracao_senha', 5, 600)) throw new RuntimeException('Muitas tentativas. Aguarde 10 minutos.');

    if (!empty($_SESSION['logged_in']) && !empty($_SESSION['user_id'])) {
        $current = (string)($_POST['current_password'] ?? '');
        $new = (string)($_POST['new_password'] ?? '');
        $confirm = (string)($_POST['confirm_password'] ?? '');
        if (strlen($new) < 8) throw new RuntimeException('A nova senha deve conter no mínimo 8 caracteres.');
        if (!hash_equals($new, $confirm)) throw new RuntimeException('A confirmação da senha não corresponde.');
        $stmt = $pdo->prepare('SELECT senha FROM users WHERE id = :id');
        $stmt->execute([':id' => (int)$_SESSION['user_id']]);
        $row = $stmt->fetch();
        if (!$row || !password_verify($current, $row['senha'])) throw new RuntimeException('Senha atual incorreta.');
        $update = $pdo->prepare('UPDATE users SET senha = :senha WHERE id = :id');
        $update->execute([':senha' => password_hash($new, PASSWORD_DEFAULT), ':id' => (int)$_SESSION['user_id']]);
        $out = ['status' => 'ok', 'message' => 'Senha alterada com sucesso.'];
    } else {
        $email = filter_var(trim((string)($_POST['email'] ?? '')), FILTER_VALIDATE_EMAIL);
        if (!$email) throw new RuntimeException('Informe um e-mail válido.');
        $out = ['status' => 'error', 'message' => 'A recuperação por e-mail está indisponível: o banco atual não possui armazenamento de tokens.'];
    }
} catch (Throwable $e) {
    if (!($e instanceof RuntimeException)) error_log('Alteração de senha: ' . $e->getMessage());
    $out['message'] = $e instanceof RuntimeException ? $e->getMessage() : 'Erro interno ao atualizar a senha.';
}
echo json_encode($out, JSON_UNESCAPED_UNICODE);
