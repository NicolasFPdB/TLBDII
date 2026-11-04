<?php
declare(strict_types=1);

// Configure estas variáveis no ambiente. Os valores abaixo preservam o setup
// local que o projeto já utilizava, mas não devem ser usados em produção.
const DB_HOST_DEFAULT = 'localhost';
const DB_PORT_DEFAULT = '3309';
const DB_NAME_DEFAULT = 'lrc1970';
const DB_USER_DEFAULT = 'root';
const DB_PASS_DEFAULT = '';

function db(): PDO
{
    static $connection = null;
    if ($connection instanceof PDO) return $connection;

    $host = getenv('DB_HOST') ?: DB_HOST_DEFAULT;
    $port = getenv('DB_PORT') ?: DB_PORT_DEFAULT;
    $name = getenv('DB_NAME') ?: DB_NAME_DEFAULT;
    $user = getenv('DB_USER') ?: DB_USER_DEFAULT;
    $pass = getenv('DB_PASS');
    if ($pass === false) $pass = DB_PASS_DEFAULT;

    $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";
    $connection = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    return $connection;
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function brl(mixed $value): string
{
    return 'R$ ' . number_format((float) $value, 2, ',', '.');
}
