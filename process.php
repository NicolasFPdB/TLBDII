<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json; charset=utf-8');

// Conexão com a Data Base
include_once('conection.php');
include_once('mail.php');

// Declaração - variáveis
$name = trim($_POST['name'] ?? "");
$emailCheck = $_POST["email"] ?? "";
$email = filter_var($emailCheck, FILTER_VALIDATE_EMAIL);
$password = $_POST['password'] ?? "";
$cep = trim($_POST['cep'] ?? "");
$code = $_POST['code'] ?? "";
$token = null;

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
try {

    switch ($code) {
        // CÓDIGO DO FORMULÁRIO DE CADASTRO
        case "cadastro":
            $message['general'] = "Requisição forms cadastro efetuada com sucesso!";
            $status['general'] = "ok";

            if (!$email) {
                $message['email'] = "E-mail inválido";
                $status['email'] = "error";
                $message['check'] = "Cadastro inválido";
                $status['check'] = "error";
                break;
            }

            // Select SQL - Check do User
            $Sql = "SELECT id FROM users WHERE email = :email";
            $st = $pdo->prepare($Sql);
            $st->execute([
                ':email' => $email
            ]);
            $user = $st->fetch(PDO::FETCH_ASSOC);

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
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);

                    // Insert SQL - Cadastro do Usuário na database
                    $sql = "INSERT INTO users (nome, email, senha, cep) VALUES (:nome, :email, :senha, :cep);";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        ':nome' => $name,
                        ':email' => $email,
                        ':senha' => $password_hash,
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

            if ($email && !empty($password)) {
                $message['check'] = "Campos preenchidos com sucesso!";
                $status['check'] = "ok";

                // Select SQL - Check do User
                $sql = "SELECT id, senha, nome FROM users WHERE email = :email";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':email' => $email
                ]);
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

            if (!$email) {
                $message['email'] = "E-mail inválido";
                $status['email'] = "error";
                break;
            }

            // Select SQL - Check do User
            $sql = "SELECT id, token, token_expiration FROM users WHERE email = :email";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':email' => $email
            ]);
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
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);

                    // Update SQL
                    $Sql = "UPDATE users SET senha = :senha, token = NULL, token_expiration = NULL WHERE email = :email;";
                    $st = $pdo->prepare($Sql);
                    $st->execute([
                        ':email' => $email,
                        ':senha' => $password_hash
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

            if (!$email) {
                $message['email'] = "E-mail inválido";
                $status['email'] = "error";
                break;
            }

            // Select SQL - Check do User
            $sql = "SELECT id FROM users WHERE email = :email";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':email' => $email
            ]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                $message["token"] = "Token enviado caso o usuário esteja cadastrado";
                $status["token"] = "ok";
            } else {

                $token = sprintf("%06d", random_int(0, 999999));
                $expires = date('Y-m-d H:i:s', strtotime('+30 minutes'));

                $sql = "UPDATE users SET token = :token, token_expiration = :expires WHERE email = :email";
                $st = $pdo->prepare($sql);
                $st->execute([
                    ':token' => $token,
                    ':expires' => $expires,
                    ':email' => $email
                ]);

                $message['token'] = "Token enviado caso o usuário esteja cadastrado";
                $status['token'] = "ok";

                TokenReset($email, $token);
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
    'name' => $name,
    'email' => $email,
    'cep' => $cep,
    'status' => $status,
    'message' => $message
);

echo json_encode($data, JSON_UNESCAPED_UNICODE);
?>