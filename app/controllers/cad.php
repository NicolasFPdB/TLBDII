<?php
declare(strict_types=1);
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/conection.php';
require_once __DIR__ . '/process.php';

$out = ['status' => ['general' => 'error'], 'message' => ['general' => 'Não foi possível concluir o cadastro.']];
try {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    if (!RateLimitDb($pdo, $ip, 'cadastro', 10, 600)) {
        throw new RuntimeException('Muitas tentativas de cadastro. Aguarde alguns minutos.');
    }
    $fullName = trim((string)($_POST['name'] ?? ''));
    $email = filter_var(trim((string)($_POST['email'] ?? '')), FILTER_VALIDATE_EMAIL);
    $password = (string)($_POST['password'] ?? '');
    $parts = preg_split('/\s+/', $fullName, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    if (count($parts) < 2) throw new RuntimeException('Informe nome e sobrenome.');
    if (!$email) throw new RuntimeException('Informe um e-mail válido.');
    if (strlen($password) < 8) throw new RuntimeException('A senha deve conter no mínimo 8 caracteres.');

    $check = $pdo->prepare('SELECT id FROM users WHERE email = :email');
    $check->execute([':email' => $email]);
    if ($check->fetch()) throw new RuntimeException('Este e-mail já está cadastrado.');

    $nome = array_shift($parts);
    $sobrenome = implode(' ', $parts);
    $stmt = $pdo->prepare('INSERT INTO users (nome, sobrenome, email, senha) VALUES (:nome, :sobrenome, :email, :senha)');
    $stmt->execute([':nome' => $nome, ':sobrenome' => $sobrenome, ':email' => $email, ':senha' => password_hash($password, PASSWORD_DEFAULT)]);
    $out = ['status' => ['general' => 'ok', 'check' => 'ok'], 'message' => ['general' => 'Cadastro realizado com sucesso!']];
} catch (Throwable $e) {
    if (!($e instanceof RuntimeException)) error_log('Cadastro: ' . $e->getMessage());
    $out['message']['general'] = $e instanceof RuntimeException ? $e->getMessage() : 'Erro interno ao realizar cadastro.';
}
echo json_encode($out, JSON_UNESCAPED_UNICODE);
