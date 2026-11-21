<?php

declare(strict_types=1);

function responder_sucesso(
    mixed $dados = null,
    int $codigo = 200,
    ?string $mensagem = null
): void {
    http_response_code($codigo);

    header('Content-Type: application/json; charset=utf-8');

    $resposta = [
        'sucesso' => true,
        'dados' => $dados
    ];

    if ($mensagem !== null) {
        $resposta['mensagem'] = $mensagem;
    }

    echo json_encode($resposta, JSON_UNESCAPED_UNICODE);
    exit;
}

function responder_erro(
    string $mensagem,
    int $codigo = 400,
    array $erros = []
): void {
    http_response_code($codigo);

    header('Content-Type: application/json; charset=utf-8');

    $resposta = [
        'sucesso' => false,
        'mensagem' => $mensagem
    ];

    if (!empty($erros)) {
        $resposta['erros'] = $erros;
    }

    echo json_encode($resposta, JSON_UNESCAPED_UNICODE);
    exit;
}
