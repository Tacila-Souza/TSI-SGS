<?php
declare(strict_types=1);

/**
 * Conexão PDO canônica com o MySQL 8.0.13+ (seção 6.3.12 do documento técnico).
 */
function criar_conexao(): PDO
{
    $host = getenv('DB_HOST') ?: '127.0.0.1';
    $porta = getenv('DB_PORT') ?: '3306';
    $dbname = getenv('DB_NAME') ?: 'gestao_seminarios';
    $usuario = getenv('DB_USER') ?: 'root';
    $senha = getenv('DB_PASS') ?: '';

    return new PDO(
        "mysql:host={$host};port={$porta};dbname={$dbname};charset=utf8mb4",
        $usuario,
        $senha,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
}
