<?php
declare(strict_types=1);

function exigir_metodo(string ...$metodos): void
{
    $atual = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    $aceitos = array_map('strtoupper', $metodos);

    if (!in_array($atual, $aceitos, true)) {
        responder_erro('Método HTTP não permitido.', 405);
    }
}

function ler_json(): array
{
    $bruto = file_get_contents('php://input');
    if ($bruto === false || trim($bruto) === '') {
        return [];
    }

    try {
        $dados = json_decode($bruto, true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException) {
        responder_erro('Corpo da requisição não é um JSON válido.', 400);
    }

    if (!is_array($dados)) {
        responder_erro('O corpo da requisição deve ser um objeto JSON.', 400);
    }

    return $dados;
}

function query_int(string $chave, ?int $padrao = null): ?int
{
    if (!isset($_GET[$chave]) || $_GET[$chave] === '') {
        return $padrao;
    }

    $valor = filter_var($_GET[$chave], FILTER_VALIDATE_INT);
    if ($valor === false || $valor <= 0) {
        responder_erro("Parâmetro '{$chave}' deve ser um inteiro positivo.", 400);
    }

    return $valor;
}

function query_texto(string $chave, string $padrao = ''): string
{
    if (!isset($_GET[$chave])) {
        return $padrao;
    }

    return trim((string) $_GET[$chave]);
}
