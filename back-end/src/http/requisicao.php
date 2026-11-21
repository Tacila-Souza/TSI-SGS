<?php

declare(strict_types=1);

function exigir_metodo(string $metodo): void
{
    $metodoAtual = $_SERVER['REQUEST_METHOD'] ?? '';

    if ($metodoAtual !== $metodo) {
        header('Allow: ' . $metodo);
        responder_erro('Método HTTP não permitido.', 405);
    }
}

function ler_json(): array
{
    $conteudo = file_get_contents('php://input');

    if ($conteudo === false || trim($conteudo) === '') {
        responder_erro('Corpo da requisição vazio.', 400);
    }

    try {
        $objeto = json_decode(
            $conteudo,
            false,
            512,
            JSON_THROW_ON_ERROR
        );

        if (!is_object($objeto)) {
            responder_erro('O JSON deve ser um objeto.', 400);
        }

        return json_decode(
            $conteudo,
            true,
            512,
            JSON_THROW_ON_ERROR
        );

    } catch (JsonException $erro) {
        responder_erro('JSON inválido.', 400);
    }
}
