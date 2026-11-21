<?php
declare(strict_types=1);

function responder_sucesso(mixed $dados = null, int $codigo = 200, ?string $mensagem = null): never
{
    http_response_code($codigo);
    header('Content-Type: application/json; charset=utf-8');

    $corpo = [
        'sucesso' => true,
        'dados' => $dados,
    ];

    if ($mensagem !== null) {
        $corpo['mensagem'] = $mensagem;
    }

    echo json_encode(
        $corpo,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
    );
    exit;
}

function responder_erro(
    string $mensagem,
    int $codigo = 400,
    ?array $erros = null,
    ?string $detalhe = null
): never {
    http_response_code($codigo);
    header('Content-Type: application/json; charset=utf-8');

    $corpo = [
        'sucesso' => false,
        'mensagem' => $mensagem,
    ];

    if ($erros !== null) {
        $corpo['erros'] = $erros;
    }

    if ($detalhe !== null && $codigo >= 500) {
        // Em produção o detalhe vai para o log; nunca para o cliente.
        error_log('[SGS] ' . $detalhe);
    }

    echo json_encode(
        $corpo,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
    );
    exit;
}
