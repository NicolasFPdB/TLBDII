<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json; charset=utf-8');

// Conexão com a Database
    include_once('conection.php');

// Conexão com E-mail
    include_once('mail.php');

    // Conexão com o Redis
    $redis = null;
    try {
        $redisInstance = new Redis();
        if ($redisInstance->connect('127.0.0.1', 6379)) {
            $redis = $redisInstance;
        }
    } catch (Exception $e) {
        $message['general'] = "Erro ao conectar ao serviço de cache.";
        $status['general']  = "error";
    }

// Declaração - Variáveis
$name = trim($_POST['name'] ?? "");
$emailCheck = $_POST["email"] ?? "";
$email = filter_var($emailCheck, FILTER_VALIDATE_EMAIL);
$password = $_POST['password'] ?? "";
$cep = trim($_POST['cep'] ?? "");
$code = $_POST['code'] ?? "";
$token = null;
$userIp = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

// Declaração - objetos
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

function RateLimit(?Redis $redis, string $ip, string $action, int $maxAttempts = 5, int $Seconds = 300): bool {
    if (!$redis) {
        return true;
    } 
    try {
        $key = "rate_limit:{$action}:{$ip}";
        $currentAttempts = $redis->get($key);
        if ($currentAttempts && (int)$currentAttempts >= $maxAttempts) {
            return false;
        }
        $newAttempts = $redis->incr($key);
        if ($newAttempts === 1) {
            $redis->expire($key, $Seconds);
        }
        return true;
    }
    catch (Throwable $e) {
        return false;
    }
}

try {
    switch ($code) {
        // CÓDIGO DO FORMULÁRIO DE CADASTRO
        case "cadastro":
            $message['general'] = "Requisição forms cadastro efetuada com sucesso!";
            $status['general'] = "ok";

            // Proteção - Automatizada de criação de contas
            if (!RateLimit($redis, $userIp, 'cadastro', 10, 600)) {
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
            $Sql = "SELECT id FROM users WHERE email = :email";
            $stmt = $pdo->prepare($Sql);
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                // Sanitização de dados - Nome do User
                $aux = array_values(array_filter(explode(" ", $name)));
                if (!empty($aux)) {
                    $name = (count($aux) > 1) ? $aux[0] . " " . end($aux) : $aux[0];
                    $message['name'] = "Nome cadastrado com sucesso!";
                    $status['name'] = "ok";
                } else {
                    $message['name'] = "Erro ao cadastrar user!";
                    $status['name'] = "error";
                }

                // Sanitização de dados - Senha do user
                if (strlen($password) < 6) {
                    $message['password'] = "Senha deve conter no mínimo 6 caracteres";
                    $status['password'] = "error";
                } else {
                    $message['password'] = "Senha cadastrada com sucesso!";
                    $status['password'] = "ok";
                }

                // Sanitização de dados - CEP do user
                if (!empty($cep) && preg_match('/^\d{5}-?\d{3}$/', $cep)) {
                    $message['cep'] = "CEP cadastrado com sucesso!";
                    $status['cep'] = "ok";
                } else {
                    $message['cep'] = "CEP inválido!";
                    $status['cep'] = "error";
                }

                if ($status['name'] === "ok" && $status['password'] === "ok" && $status['cep'] === "ok") {
                    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

                    // Insert SQL - Cadastro do Usuário na database
                    $sql = "INSERT INTO users (nome, email, senha, cep) VALUES (:nome, :email, :senha, :cep);";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        ':nome' => $name,
                        ':email' => $email,
                        ':senha' => $passwordHash,
                        ':cep' => $cep
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

            // Proteção - Brute Force
            if (!RateLimit($redis, $userIp, 'login', 5, 300)) {
            $message['check'] = "Muitas tentativas de login. Aguarde 5 minutos e tente novamente.";
            $status['check']  = "error";
            break;
        }

            if ($email && !empty($password)) {
                $message['check'] = "Campos preenchidos com sucesso!";
                $status['check'] = "ok";

                // Select SQL - Check do User
                $sql = "SELECT id, senha, nome FROM users WHERE email = :email";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([':email' => $email]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                // Checagem do login
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

            $message['general'] = "Requisição da nova senha efetuada com sucesso!";
            $status['general'] = "ok";
            
            // Proteção - Brute Force
            if (!RateLimit($redis, $userIp, 'login', 5, 300)) {
            $message['check'] = "Muitas tentativas de troca de senha. Aguarde 5 minutos e tente novamente.";
            $status['check']  = "error";
            break;
            }

            if (!$email) {
                $message['email'] = "E-mail inválido";
                $status['email'] = "error";
                break;
            }

            // Select SQL - Check do User
            $sql = "SELECT id, token, token_expiration FROM users WHERE email = :email";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && !empty($user['token'])) {
                $message['check'] = "User encontrado com sucesso!";
                $status['check'] = "ok";

                $userToken = $_POST['token'] ?? "";
                $now = date('Y-m-d H:i:s');

                if (hash_equals($userToken, $user['token']) && $user['token_expiration'] >= $now) {
                    $message['token'] = "Token válido";
                    $status['token'] = "ok";
                } else {
                    $message['token'] = "Token inválido";
                    $status['token'] = "error";
                    $message['check'] = "Token inválido";
                    $status['check'] = "error";
                    break;
                }

                // Sanitização de dados - Nova senha do User
                if (strlen($password) < 6) {
                    $message['password'] = "Senha deve conter no mínimo 6 caracteres";
                    $status['password'] = "error";
                } else {
                    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

                    // Update SQL
                    $sql = "UPDATE users SET senha = :senha, token = NULL, token_expiration = NULL WHERE email = :email";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        ':email' => $email,
                        ':senha' => $passwordHash
                    ]);
                    $message['password'] = "Senha atualizada!";
                    $status['password'] = "ok";
                }
            } else {
                $message['check'] = "User não encontrado";
                $status['check'] = "error";
            }
            break;

        // CÓDIGO DO FORMULÁRIO DE ENVIO DO TOKEN
        case "reset-token":
            $message['general'] = "Requisição de reset da senha efetuada com sucesso!";
            $status['general'] = "ok";

            if (!RateLimit($redis, $userIp, 'reset-token', 3, 600)) {
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

            // Select SQL - Check do User
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

                TokenReset($email, $token);
            }
            
            $message["token"] = "Token enviado caso o usuário esteja cadastrado";
            $status["token"] = "ok";
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
    'name' => $name,
    'email' => $email,
    'cep' => $cep,
    'status' => $status,
    'message' => $message
);

echo json_encode($data, JSON_UNESCAPED_UNICODE);
?>