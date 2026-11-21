<?php

declare(strict_types=1);

$origensPermitidas = [
    'http://localhost:3000',
    'http://127.0.0.1:3000'
];

$origem = $_SERVER['HTTP_ORIGIN'] ?? '';

header('Vary: Origin');

if (in_array($origem, $origensPermitidas, true)) {
    header('Access-Control-Allow-Origin: ' . $origem);
}

header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}
