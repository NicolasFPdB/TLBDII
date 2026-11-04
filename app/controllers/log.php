<?php
declare(strict_types=1);
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/conection.php';
require_once __DIR__ . '/process.php';
$out = ['status' => ['general' => 'error'], 'message' => ['general' => 'Credenciais inválidas.']];
try {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    if (!RateLimitDb($pdo, $ip, 'login', 5, 300)) throw new RuntimeException('Muitas tentativas de login. Aguarde 5 minutos.');
    $email = filter_var(trim((string)($_POST['email'] ?? '')), FILTER_VALIDATE_EMAIL);
    $password = (string)($_POST['password'] ?? '');
    if (!$email || $password === '') throw new RuntimeException('Preencha e-mail e senha.');
    $stmt = $pdo->prepare('SELECT id, nome, sobrenome, senha, tipo FROM users WHERE email = :email');
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();
    if (!$user || !password_verify($password, $user['senha'])) throw new RuntimeException('E-mail ou senha incorretos.');
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['user_name'] = trim($user['nome'] . ' ' . $user['sobrenome']);
    $_SESSION['user_type'] = $user['tipo'];
    $_SESSION['logged_in'] = true;
    $out = ['status' => ['general' => 'ok', 'check' => 'ok'], 'message' => ['general' => 'Login realizado com sucesso!']];
} catch (Throwable $e) {
    if (!($e instanceof RuntimeException)) error_log('Login: ' . $e->getMessage());
    $out['message']['general'] = $e instanceof RuntimeException ? $e->getMessage() : 'Erro interno ao autenticar.';
}
echo json_encode($out, JSON_UNESCAPED_UNICODE);
