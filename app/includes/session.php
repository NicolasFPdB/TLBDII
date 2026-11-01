<?php
declare(strict_types=1);

/* Inicia a sessão do painel com cookies endurecidos. Use em TODAS as páginas
   (auth.php, login.php, logout.php) para que os atributos sejam sempre os mesmos. */
function session_boot(): void
{
    if (session_status() === PHP_SESSION_ACTIVE)
        return;
    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_name('lrc_admin');            // nome próprio: não colide com a sessão da loja em localhost
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => $https,             // true só em HTTPS; em http://localhost (XAMPP) um 'secure' fixo quebraria o login
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function session_destroy_full(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $c = session_get_cookie_params();
        setcookie(session_name(), '', ['expires' => time() - 3600, 'path' => $c['path'], 'secure' => $c['secure'], 'httponly' => true, 'samesite' => 'Lax']);
    }
    session_destroy();
}
