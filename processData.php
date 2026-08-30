<?php
header('Content-Type: application/json; charset=utf-8');

// Conexão com a Data Base
include_once('conection.php');

// Declaração - variáveis
$name = trim($_POST['name'] ?? "");
$email = filter_var($emailRaw, FILTER_VALIDATE_EMAIL);
$password = $_POST['password'] ?? "";
$password_hash = password_hash($password, PASSWORD_DEFAULT);
$cep = trim($_POST['cep'] ?? "");
$code = $_POST['code'] ?? "";

// Declaração - objetos
$message = [
    'check' => "",
    'name' => "",
    'e-mail' => "",
    'password' => ""
];
$status = [
    'general' => "",
    'check' => "",
    'name' => "",
    'e-mail' => "",
    'password' => ""
];
try {

    switch ($code) {
        // CÓDIGO DO FORMULÁRIO DE CADASTRO
        case "cadastro":
            $message['general'] = "Requisição forms cadastro efetuada com sucesso!";
            $status['general'] = "ok";

            if (!$email) {
                $message['e-mail'] = "E-mail inválido";
                $status['e-mail'] = "error";
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
            $users = $st->fetchALL(PDO::FETCH_ASSOC);

            // Condicional - Check da existência do User
            if (!$users) {

                // Sanitização de dados - Nome do User
                if (is_string($name) && !empty($name)) {
                    $aux = array_values(array_filter(explode(" ", $name)));
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
                    $message['password'] = "Cadastro efetuado com sucesso!";
                    $status['password'] = "ok";
                }

                // Sanitização de dados - CEP do user

                if ($status['name'] == "ok" && $status['password'] == "ok") {

                    // Insert SQL
                    $sql = "INSERT INTO users (nome, email, senha, cep) VALUES (:nome, :email, :senha, :cep);";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        ':nome' => $name,
                        ':email' => $email,
                        ':senha' => $password_hash,
                        ':cep' => $cep
                    ]);

                    $message['check'] = "Cadastro efetuado com sucesso!";
                    $status['check'] = "ok";
                } else {
                    $message['check'] = "Cadastro inválido!";
                    $status['check'] = "error";
                }

            } else {
                $message['check'] = "user já cadastrado!";
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
                $sql = "SELECT id, senha FROM users WHERE email = :email";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':email' => $email
                ]);
                $users = $stmt->fetch(PDO::FETCH_ASSOC);

                // Checagem do login
                if ($users && password_verify($password, $users['senha'])) {
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

            $message['general'] = "Requisição forms nova senha efetuada com sucesso!";
            $status['general'] = "ok";

            if (!$email) {
                $message['e-mail'] = "E-mail inválido";
                $status['e-mail'] = "error";
                break;
            }

            // Select SQL - Check do User
            $sql = "SELECT id FROM users WHERE email = :email";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':email' => $email
            ]);
            $users = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($users) {
                $message['check'] = "user encontrado com sucesso!";
                $status['check'] = "ok";

                // Sanitização de dados - Nova senha do User
                if (strlen($password) < 6) {
                    $message['password'] = "Senha deve conter no mínimo 6 caracteres";
                    $status['password'] = "error";
                } else {
                    // Update SQL
                    $Sql = "UPDATE users SET senha = :senha WHERE email = :email;";
                    $st = $pdo->prepare($Sql);
                    $st->execute([
                        ':email' => $email,
                        ':senha' => $password_hash
                    ]);
                    $message['password'] = "Senha atualizada!";
                    $status['password'] = "ok";
                }
            } else {
                $message['check'] = "user não encontrado";
                $status['check'] = "error";
            }

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
    'e-mail' => $email,
    'status' => $status,
    'message' => $message
);

echo json_encode($data, JSON_UNESCAPED_UNICODE);
?>