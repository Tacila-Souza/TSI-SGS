<?php
declare(strict_types=1);

/**
 * Envelope padrão de resposta (seção 6.3.10 / contrato, seção 2).
 */
function responder_sucesso(mixed $dados, int $codigo = 200, ?string $mensagem = null): never
{
    http_response_code($codigo);
    header('Content-Type: application/json; charset=utf-8');

    $corpo = ['sucesso' => true, 'dados' => $dados];
    if ($mensagem !== null) {
        $corpo['mensagem'] = $mensagem;
    }

    echo json_encode($corpo, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function responder_erro(string $mensagem, int $codigo = 400, ?array $erros = null): never
{
    http_response_code($codigo);
    header('Content-Type: application/json; charset=utf-8');

    $corpo = ['sucesso' => false, 'mensagem' => $mensagem];
    if ($erros !== null && $erros !== []) {
        $corpo['erros'] = $erros;
    }

    echo json_encode($corpo, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
