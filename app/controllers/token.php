<?php
declare(strict_types=1);
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
header('Content-Type: application/json; charset=utf-8');
// Sem colunas/tabela de tokens no schema atual, validar um código sem persistência
// não seria seguro. A recuperação é explicitamente desativada, sem tocar no DB.
http_response_code(501);
echo json_encode([
    'status' => ['general' => 'error', 'token' => 'error'],
    'message' => ['general' => 'Redefinição por token indisponível: o schema atual não armazena tokens de recuperação. O banco foi preservado sem alterações.']
], JSON_UNESCAPED_UNICODE);
