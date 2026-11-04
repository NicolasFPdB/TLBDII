<?php
declare(strict_types=1);

require_once __DIR__ . '/session_bootstrap.php';

$authenticated = ($_SESSION['logged_in'] ?? false) === true
    && !empty($_SESSION['user_id']);
if (!$authenticated) {
    $isAjax = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest'
        || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Sessão expirada. Faça login novamente.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    header('Location: ../../public/forms.html');
    exit;
}
