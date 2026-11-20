<?php
declare(strict_types=1);

function responder_json(array $corpo, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($corpo, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function responder_sucesso(mixed $dados, int $status = 200, ?string $mensagem = null): never
{
    $corpo = ['sucesso' => true, 'dados' => $dados];
    if ($mensagem !== null) {
        $corpo['mensagem'] = $mensagem;
    }
    responder_json($corpo, $status);
}

function responder_erro(string $mensagem, int $status = 400, array $erros = []): never
{
    $corpo = ['sucesso' => false, 'mensagem' => $mensagem];
    if ($erros !== []) {
        $corpo['erros'] = $erros;
    }
    responder_json($corpo, $status);
}
