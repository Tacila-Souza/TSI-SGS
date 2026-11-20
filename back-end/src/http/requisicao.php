<?php
declare(strict_types=1);

function exigir_metodo(string $esperado): void
{
    $recebido = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    if ($recebido !== strtoupper($esperado)) {
        throw new RegraNegocioException('Método HTTP não permitido para esta operação.', 400);
    }
}

function ler_json(): array
{
    $bruto = file_get_contents('php://input');
    if ($bruto === false || trim($bruto) === '') {
        throw new RegraNegocioException('Corpo da requisição vazio.', 400);
    }

    $dados = json_decode($bruto, true);
    if (!is_array($dados) || array_is_list($dados)) {
        throw new RegraNegocioException('JSON inválido ou não representando um objeto.', 400);
    }

    return $dados;
}

function parametro_inteiro_opcional(string $chave): ?int
{
    $bruto = $_GET[$chave] ?? null;
    if ($bruto === null || $bruto === '') {
        return null;
    }

    $texto = is_scalar($bruto) ? (string) $bruto : '';
    if (!ctype_digit($texto) || (int) $texto <= 0) {
        throw new RegraNegocioException(
            'Parâmetro inválido.',
            400,
            [$chave => 'Informe um número inteiro positivo.'],
        );
    }

    return (int) $texto;
}

function parametro_inteiro(string $chave, ?int $padrao = null): int
{
    $bruto = $_GET[$chave] ?? null;

    if (($bruto === null || $bruto === '') && $padrao !== null) {
        return $padrao;
    }

    $texto = is_scalar($bruto) ? (string) $bruto : '';
    if ($texto === '' || !ctype_digit($texto) || (int) $texto <= 0) {
        throw new RegraNegocioException(
            'Parâmetro inválido.',
            400,
            [$chave => 'Informe um número inteiro positivo.'],
        );
    }

    return (int) $texto;
}
