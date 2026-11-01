<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../session.php';
session_boot();

const SESSAO_INATIVIDADE = 1800; // 30 min

/* Sessão válida = logado + sem estouro de inatividade + usuário AINDA existe, ativo e administrador no banco.
   A consulta roda no máximo 1x por requisição. */
function admin_ok(): bool
{
    static $ok = null;
    if ($ok !== null)
        return $ok;
    if (empty($_SESSION['uid']) || ($_SESSION['tipo'] ?? '') !== 'administrador')
        return $ok = false;
    if (time() - (int) ($_SESSION['last'] ?? 0) > SESSAO_INATIVIDADE) {
        session_destroy_full();
        return $ok = false;
    }
    $s = db()->prepare("SELECT 1 FROM users WHERE id=? AND tipo='administrador' AND ativo=1");
    $s->execute([$_SESSION['uid']]);
    if (!$s->fetchColumn()) {
        session_destroy_full();
        return $ok = false;
    }
    $_SESSION['last'] = time();
    return $ok = true;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf']))
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_check(): bool
{
    $t = $_POST['csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    return is_string($t) && hash_equals(csrf_token(), $t);
}

function wants_json(): bool
{
    return ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'fetch';
}

function json_out(array $data, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function admin_only(): void
{
    if (!admin_ok()) {
        if (wants_json())
            json_out(['ok' => false, 'msg' => 'Sessão expirada. Entre novamente.'], 401);
        header('Location: login.php');
        exit;
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !csrf_check()) {
        if (wants_json())
            json_out(['ok' => false, 'msg' => 'Token de segurança inválido. Recarregue a página.'], 419);
        http_response_code(419);
        exit('Token de segurança inválido. Volte e recarregue a página.');
    }
}

function me(): array
{
    $s = db()->prepare('SELECT id,nome,sobrenome,email,telefone,tipo FROM users WHERE id=?');
    $s->execute([$_SESSION['uid']]);
    return $s->fetch() ?: [];
}

function flash($type, $msg): void
{
    $_SESSION['flash'] = [$type, $msg];
}

/* O flash vira um toast no navegador (ver admin.js). Fica oculto no HTML como dado. */
function show_flash(): void
{
    if (isset($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        echo '<div class="flash ' . e($f[0]) . '" data-toast>' . e($f[1]) . '</div>';
    }
}

const STATUS_PEDIDO = [
    'aguardando_pagamento' => ['Aguardando pagamento', 'pending'],
    'pago' => ['Pago', 'approved'],
    'processando' => ['Processando', 'pending'],
    'enviado' => ['Enviado', 'shipped'],
    'entregue' => ['Entregue', 'approved'],
    'cancelado' => ['Cancelado', 'cancelled'],
];
