<?php
    header('Content-Type: application/json; charset=utf-8');
    
// Inclusão do Banco de Dados
    include_once('conection.php');

// Declaração de variáveis
$name = trim($_POST["name"] ?? "");
$email = filter_var(trim($_POST["e-mail"] ?? ""), FILTER_VALIDATE_EMAIL);
$password = $_POST["password"] ?? "";
$cep = trim($_POST["cep"] ?? "");
$code = $_POST["code"] ?? "";
$message = "Valor padrão";
$status = "Valor padrão";

if ($code == "cadastro") {

    // Funções de cadastro
    $name = NewUser($name);
    $email = NewEmail($email);
    $password = NewPassword($password);

    // SQL
    $Sql = "SELECT * FROM users WHERE email = :email";
    $st = $pdo->prepare($Sql);
    $st->execute([
        ':email' => $email
        ]);
    $users = $st->fetchALL(PDO :: FETCH_ASSOC);

    // Check do usuário
    if (!$users) {

        if (!$email) {
            $message = "E-mail inválido!";
            $status = "Naok";
        }

        else {
            // SQL
            $Sql = "INSERT INTO users (nome, email, cep, senha) VALUES (:nome, :email, :cep, :senha);";
            $st = $pdo->prepare($Sql);
            $st->execute([
                ':nome' => $name,
                ':email' => $email,
                ':cep' => $cep,
                ':senha' => $password
            ]);
            $message = "Cadastro efetuado com sucesso!";
            $status = "ok";
        }
    }

    else {
            $message = "Cadastro inválido!";
            $status = "Naok";
    }


    // Cadastro do nome do usuário
    if (is_string($name)) {
        $aux = explode(" ", $name);
        $name = $aux[0]." ".end($aux);
        $message = "Nome cadastrado com sucesso!";
    } 

    else {
        $message = "Erro ao cadastrar usuário!";
    }
    
    // Cadastro do email do usuário
        if (is_string($email)) {
            $procurador = strpos($email,".com");
            if ($procurador == TRUE) {
                $message = "E-mail cadastrado com sucesso";
            } 

            else {
                $message = "E-mail inválido!";
            }
        } 
        
        else {
            $message = "Erro ao digitar e-mail";
        }
    
    // Cadastro da senha do usuário
    if (strlen($password) < 6) {
        $message = "Senha deve conter no mínimo 6 caracteres";
    } 
    
    else {
        $message = "Senha cadastrada com sucesso";
    }

    // Código do formulário de Login
    else if ($code == "login") {
        
        // SQL
        $sql = "SELECT * FROM users WHERE email = :email AND senha = :senha";     
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':email' => $email,
            ':senha' => $password
            ]);
        $users = $stmt->fetchALL(PDO :: FETCH_ASSOC);
    
        // Checagem do login
        if($users) {
                $message = "Sucesso ao logar!";
                $status = "ok";
        }
        else {
                $message = "Login inválido!";
                $status = "Naok";
        }
    }

    // Código do formulário de nova senha/Password
    else if ($code == "password") {

        if (strlen($password) < 6) {
            $message = "Senha deve conter no mínimo 6 caracteres";
        } 
    
        else {
            $message = "Senha atualizada com sucesso";
        }

        // SQL
        $Sql = "SELECT * FROM users WHERE email = :email";
        $st = $pdo->prepare($Sql);
        $st->execute([
            ':email' => $email
        ]);
        
        $users = $st->fetchALL(PDO :: FETCH_ASSOC);

        // Check do usuário
        if ($users) {
            // SQL
            $Sql = "UPDATE users SET senha = :senha WHERE email = :email;";
            $st = $pdo->prepare($Sql);
            $st->execute([
                ':email' => $email,
                ':senha' => $password
            ]);
            $message = "Senha atualizada!";
            $status = "ok";
        }
    } 

    else {
        $message = "Falha nas requisições!";
    }
    
    // Dados
    $data = array(
        "name" => $name,
        "e-mail" => $email,
        "password" => $password,
        "status" => $status,
        "message" => $message
    );

    echo json_encode($data, JSON_UNESCAPED_UNICODE);
?>