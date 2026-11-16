<?php
// Iniciar sessão se necessário
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Conexão com a Database
include_once('../config/config.php');

// ADICIONE ESTA LINHA AQUI:
$pdo = db();

function RateLimitDb(PDO $pdo, string $ip, string $action, int $maxAttempts = 5, int $seconds = 300): bool
{
    try {
        $now = time(); // Usamos timestamp do PHP para evitar problemas de fuso horário/formato de data no MySQL
        $nowFormatted = date('Y-m-d H:i:s', $now);

        // Busca o registro atual para este IP e ação
        $sql = "SELECT id, attempts, last_attempt FROM rate_limits WHERE ip = :ip AND action = :action";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':ip' => $ip, ':action' => $action]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($record) {
            $lastAttemptTime = strtotime($record['last_attempt']);
            $timePassed = $now - $lastAttemptTime;

            // Se o tempo passou do limite, reseta as tentativas para 1
            if ($timePassed > $seconds) {
                $updateSql = "UPDATE rate_limits SET attempts = 1, last_attempt = :now WHERE id = :id";
                $updateStmt = $pdo->prepare($updateSql);
                $updateStmt->execute([':now' => $nowFormatted, ':id' => $record['id']]);
                return true;
            }

            // Se ainda está dentro do limite e abaixo do máximo, incrementa
            if ($record['attempts'] < $maxAttempts) {
                $updateSql = "UPDATE rate_limits SET attempts = attempts + 1, last_attempt = :now WHERE id = :id";
                $updateStmt = $pdo->prepare($updateSql);
                $updateStmt->execute([':now' => $nowFormatted, ':id' => $record['id']]);
                return true;
            }

            // Atingiu o limite máximo de tentativas
            return false;
        } else {
            // Primeiro acesso registrado para este IP/ação
            $insertSql = "INSERT INTO rate_limits (ip, action, attempts, last_attempt) VALUES (:ip, :action, 1, :now)";
            $insertStmt = $pdo->prepare($insertSql);
            $insertStmt->execute([':ip' => $ip, ':action' => $action, ':now' => $nowFormatted]);
            return true;
        }
    } catch (Throwable $e) {
        // Se houver erro no banco, logamos mas permitimos a requisição para não quebrar o site em desenvolvimento
        error_log("RateLimit Error: " . $e->getMessage());
        return true; 
    }
}

// Declaração - Variáveis alinhadas com o forms.html
$nameInput = trim($_POST['name'] ?? "");
$sobrenome = trim($_POST['sobrenome'] ?? "");
$name = trim($nameInput . " " . $sobrenome);

$emailCheck = $_POST["email"] ?? "";
$email = filter_var($emailCheck, FILTER_VALIDATE_EMAIL);
$password = $_POST['password'] ?? "";
$code = $_POST['code'] ?? "";
$userIp = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

// Declaração - objetos de resposta
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

try {
    switch ($code) {
        // CÓDIGO DO FORMULÁRIO DE CADASTRO
        case "cadastro":
            $message['general'] = "Requisição forms cadastro efetuada com sucesso!";
            $status['general'] = "ok";

            if (!RateLimitDb($pdo, $userIp, 'cadastro', 10, 600)) {
                $message['check'] = "Muitas tentativas de cadastro. Aguarde alguns minutos.";
                $status['check']  = "error";
                break;
            }

            if (!$email) {
                $message['email'] = "E-mail inválido";
                $status['email'] = "error";
                $message['check'] = "Cadastro inválido";
                $status['check'] = "error";
                break;
            }

            // Select SQL - Check do User
            $sql = "SELECT id FROM users WHERE email = :email";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                if (!empty($nameInput)) {
                    $message['name'] = "Nome cadastrado com sucesso!";
                    $status['name'] = "ok";
                } else {
                    $message['name'] = "Informe o nome!";
                    $status['name'] = "error";
                }

                if (strlen($password) < 6) {
                    $message['password'] = "Senha deve conter no mínimo 6 caracteres";
                    $status['password'] = "error";
                } else {
                    $message['password'] = "Senha cadastrada com sucesso!";
                    $status['password'] = "ok";
                }

                if ($status['name'] === "ok" && $status['password'] === "ok") {
                    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

                    // Insert SQL - Cadastro do Usuário na database (sem CEP, compatível com o HTML)
                    $sql = "INSERT INTO users (nome, sobrenome, email, senha) VALUES (:nome, :sobrenome, :email, :senha);";
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
            break;

        // CÓDIGO DO FORMULÁRIO DE LOGIN
        case "login":
            $message['general'] = "Requisição forms login efetuada com sucesso!";
            $status['general'] = "ok";

            if (!RateLimitDb($pdo, $userIp, 'login', 5, 300)) {
                $message['check'] = "Muitas tentativas de login. Aguarde 5 minutos e tente novamente.";
                $status['check']  = "error";
                break;
            }

            if ($email && !empty($password)) {
                $sql = "SELECT id, senha, nome FROM users WHERE email = :email";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([':email' => $email]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user && password_verify($password, $user['senha'])) {
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['nome'];
                    $_SESSION['logged_in'] = true;

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
            break;

        // CÓDIGO DO FORMULÁRIO DE NOVA SENHA/PASSWORD
        case "password":
            $message['general'] = "Requisição de reset da senha efetuada com sucesso!";
            $status['general'] = "ok";

            if (!RateLimitDb($pdo, $userIp, 'reset-token', 3, 600)) {
                $message['token'] = "Muitos pedidos de token enviados. Aguarde 10 minutos.";
                $status['token']  = "error";
                $message['check'] = "Bloqueado por excesso de tentativas.";
                $status['check']  = "error";
                break;
            }

            if (!$email) {
                $message['email'] = "E-mail inválido";
                $status['email'] = "error";
                break;
            }

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

                // Aqui você pode integrar o envio real do e-mail (ex: PHPMailer ou mail())
                // mail($email, "Seu Token de Recuperação", "Seu token é: $token");
            }
            
            $message["token"] = "Token enviado caso o usuário esteja cadastrado";
            $status["token"] = "ok";
            break;

        // CÓDIGO DO FORMULÁRIO DE ENVIO DO TOKEN
        case "token":
            $message['general'] = "Requisição da nova senha efetuada com sucesso!";
            $status['general'] = "ok";
                        
            if (!RateLimitDb($pdo, $userIp, 'token-verify', 5, 300)) {
                $message['check'] = "Muitas tentativas de troca de senha. Aguarde 5 minutos e tente novamente.";
                $status['check']  = "error";
                break;
            }
            
            if (!$email) {
                $message['email'] = "E-mail inválido";
                $status['email'] = "error";
                break;
            }

            $sql = "SELECT id, token, token_expiration FROM users WHERE email = :email";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && !empty($user['token'])) {
                $userToken = trim($_POST['token-numbers'] ?? "");
                $now = date('Y-m-d H:i:s');
            
                if (hash_equals((string)$user['token'], (string)$userToken) && $user['token_expiration'] >= $now) {
                    $message['token'] = "Token válido";
                    $status['token'] = "ok";
                } else {
                    $message['token'] = "Token inválido";
                    $status['token'] = "error";
                    $message['check'] = "Token inválido ou expirado";
                    $status['check'] = "error";
                    break;
                }
            
                if (strlen($password) < 6) {
                    $message['password'] = "Senha deve conter no mínimo 6 caracteres";
                    $status['password'] = "error";
                } else {
                    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            
                    $sql = "UPDATE users SET senha = :senha, token = NULL, token_expiration = NULL WHERE email = :email";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        ':email' => $email,
                        ':senha' => $passwordHash
                    ]);
                    $message['password'] = "Senha atualizada!";
                    $status['password'] = "ok";
                    $message['check'] = "Senha atualizada com sucesso!";
                    $status['check'] = "ok";
                }
            } else {
                $message['check'] = "User não encontrado ou token não solicitado";
                $status['check'] = "error";
            }
            break;

        default:
            $message['general'] = "Falha ao encontrar CODE!";
            $status['general'] = "error";
    }

} catch (PDOException $e) {
    $message['general'] = "Falha nas requisições!";
    $status['general'] = "error";
}

// Output JSON
$data = array(
    'status' => $status,
    'message' => $message
);

header('Content-Type: application/json; charset=utf-8');
echo json_encode($data, JSON_UNESCAPED_UNICODE);
?>