<?php require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/security_headers.php';
session_boot();
if (isset($_SESSION['uid']) && ($_SESSION['tipo'] ?? '') === 'administrador') {
    header('Location:index.html');
    exit;
}

/* Limite de tentativas no servidor (tabela login_tentativas, ver sql/migracao_01.sql).
   Por IP (5/15min) e por e-mail (15/15min). O limite por e-mail é mais alto de propósito:
   um bloqueio rígido por conta deixaria qualquer pessoa travar o login do administrador. */
const LOGIN_JANELA_MIN = 15, LOGIN_MAX_IP = 5, LOGIN_MAX_EMAIL = 15;

function login_bloqueado(PDO $db, string $email, string $ip): bool
{
    $q = $db->prepare('SELECT SUM(ip=?) a, SUM(email=?) b FROM login_tentativas WHERE criado_em > NOW() - INTERVAL ' . LOGIN_JANELA_MIN . ' MINUTE AND (ip=? OR email=?)');
    $q->execute([$ip, $email, $ip, $email]);
    $r = $q->fetch();
    return (int) $r['a'] >= LOGIN_MAX_IP || (int) $r['b'] >= LOGIN_MAX_EMAIL;
}

$erro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = db();
    $email = mb_strtolower(trim((string) ($_POST['email'] ?? '')));
    $senha = (string) ($_POST['senha'] ?? '');
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';   // não confie em X-Forwarded-For sem um proxy conhecido

    if (login_bloqueado($db, $email, $ip)) {
        http_response_code(429);
        $erro = 'Muitas tentativas. Tente novamente em alguns minutos.';
    } else {
        $s = $db->prepare('SELECT id,tipo,ativo,senha FROM users WHERE email=? LIMIT 1');
        $s->execute([$email]);
        $u = $s->fetch();
        // hash falso: o tempo de resposta é parecido com e sem usuário existente
        $hash = $u['senha'] ?? '$2y$10$bTCurNRgw0qOajCfOWjxFOqIA.LvX4RD8fXRZBZC1VRCJZi4XKQy2';
        $senhaOk = password_verify($senha, $hash);
        if ($u && $senhaOk && $u['tipo'] === 'administrador' && (int) $u['ativo'] === 1) {
            session_regenerate_id(true);
            $_SESSION['uid'] = (int) $u['id'];
            $_SESSION['tipo'] = $u['tipo'];
            $_SESSION['last'] = time();
            $db->prepare('DELETE FROM login_tentativas WHERE ip=? OR email=?')->execute([$ip, $email]);
            header('Location:index.html');
            exit;
        }
        $db->prepare('INSERT INTO login_tentativas(email,ip) VALUES(?,?)')->execute([mb_substr($email, 0, 150), $ip]);
        $db->exec('DELETE FROM login_tentativas WHERE criado_em < NOW() - INTERVAL 1 DAY');
        $erro = 'E-mail, senha ou permissão inválidos.';
    }
} ?><!doctype html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>LRC1970 - Login</title>
    <link rel="stylesheet" href="../public/css/admin.css">
    <link rel="stylesheet" href="../public/css/admin-extra.css">
    <style>
        body { display: flex; min-height: 100vh; align-items: center; justify-content: center; padding: 16px }
        .login { width: 390px; max-width: 100%; background: #fff; padding: 32px; border-radius: 8px; border: 1px solid #eee }
        .login h1 { text-align: center }
        .login h1 span { color: #ee4d2d }
        .login p { text-align: center; color: #999 }
        .login input.form-input { width: 100%; margin: 8px 0 15px }
        .login .pass-wrap input { margin: 8px 0 15px }
        .login .pass-wrap button { top: 50%; margin-top: -4px }
        .login button.primary-btn { width: 100% }
    </style>
</head>

<body>
    <div class="login">
        <h1>LRC<span>1970</span></h1>
        <p>Painel administrativo</p>
        <?php if ($erro): ?>
            <div class="flash erro"><?= e($erro) ?></div><?php endif; ?>
        <form method="post">
            <label>E-mail</label>
            <input class="form-input" type="email" name="email" required autofocus
                value="<?= e($_POST['email'] ?? '') ?>">
            <label>Senha</label>
            <div class="pass-wrap">
                <input class="form-input" type="password" name="senha" required>
                <button type="button" data-toggle-pass>Mostrar</button>
            </div>
            <button class="primary-btn">Entrar</button>
        </form>
    </div>
    <script>
        document.querySelector('[data-toggle-pass]').addEventListener('click', function () {
            const i = this.previousElementSibling;
            i.type = i.type === 'password' ? 'text' : 'password';
            this.textContent = i.type === 'password' ? 'Mostrar' : 'Ocultar';
        });
    </script>
</body>

</html>
