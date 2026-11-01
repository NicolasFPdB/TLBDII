<?php
declare(strict_types=1);
const DB_HOST = 'localhost';
const DB_NAME = 'lrc1970';
const DB_USER = 'root';
const DB_PASS = '';
function db(): PDO
{
    static $p = null;
    if ($p)
        return $p;
    $p = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false]);
    return $p;
}
function e($v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}
function brl($v): string
{
    return 'R$ ' . number_format((float) $v, 2, ',', '.');
}
