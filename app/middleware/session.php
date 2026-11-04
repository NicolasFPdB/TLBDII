<?php
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    session_start();
}

// Verifica se o usuário está autenticado
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {

    // Deteta se a requisição foi feita via AJAX (jQuery)
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

    if ($isAjax) {
        // Retorna um JSON de erro para o AJAX tratar graciosamente no frontend
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(401); // Unauthorized
        echo json_encode([
            'status' => ['general' => 'error'],
            'message' => ['general' => 'Sessão expirada. Faça login novamente.']
        ], JSON_UNESCAPED_UNICODE);
        exit;
    } else {
        // Redirecionamento normal para requisições de página direta
        header('Location: home.html');
        exit;
    }
}
?>