<?php
declare(strict_types=1);

/**
 * CORS centralizado (seção 6.3.11 do documento técnico).
 */
function aplicar_cors(): void
{
    $origem = getenv('APP_ORIGIN') ?: 'http://localhost:3000';

    header("Access-Control-Allow-Origin: *");
    header('Vary: Origin');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');

    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}
