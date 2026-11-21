<?php

declare(strict_types=1);

$host = ambiente('DB_HOST', '127.0.0.1');
$porta = ambiente('DB_PORT', '3306');
$dbname = ambiente('DB_NAME', 'gestao_seminarios');
$usuario = ambiente('DB_USER', 'root');
$senha = ambiente('DB_PASS', '');

$pdo = new PDO(
    "mysql:host={$host};port={$porta};dbname={$dbname};charset=utf8mb4",
    $usuario,
    $senha,
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]
);
