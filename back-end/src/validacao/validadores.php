<?php
declare(strict_types=1);

function campo_texto(array $dados, string $chave, int $max = 255): string
{
    $valor = isset($dados[$chave]) ? trim((string) $dados[$chave]) : '';
    if ($valor === '') {
        throw new HttpErro("O campo '{$chave}' é obrigatório.", 400, [
            $chave => 'Campo obrigatório.',
        ]);
    }
    if (strlen($valor) > $max) {
        throw new HttpErro("O campo '{$chave}' é longo demais.", 400, [
            $chave => "Máximo de {$max} caracteres.",
        ]);
    }
    return $valor;
}

function campo_email(array $dados, string $chave = 'email'): string
{
    $valor = campo_texto($dados, $chave, 150);
    if (filter_var($valor, FILTER_VALIDATE_EMAIL) === false) {
        throw new HttpErro("O campo '{$chave}' deve ser um e-mail válido.", 400, [
            $chave => 'E-mail inválido.',
        ]);
    }
    return $valor;
}

function campo_int(array $dados, string $chave, int $min = 1): int
{
    $valor = filter_var($dados[$chave] ?? null, FILTER_VALIDATE_INT);
    if ($valor === false || $valor < $min) {
        throw new HttpErro("O campo '{$chave}' deve ser um inteiro >= {$min}.", 400, [
            $chave => 'Valor inválido.',
        ]);
    }
    return $valor;
}

function campo_ids(array $dados, string $chave): array
{
    $bruto = $dados[$chave] ?? null;
    if (!is_array($bruto) || $bruto === []) {
        throw new HttpErro("O campo '{$chave}' deve ser um array de IDs.", 400, [
            $chave => 'Array de IDs obrigatório.',
        ]);
    }

    $ids = [];
    foreach ($bruto as $item) {
        $id = filter_var($item, FILTER_VALIDATE_INT);
        if ($id === false || $id <= 0) {
            throw new HttpErro("O campo '{$chave}' contém um ID inválido.", 400, [
                $chave => 'Somente inteiros positivos.',
            ]);
        }
        $ids[] = $id;
    }

    return array_values(array_unique($ids));
}

function campo_ids_opcional(array $dados, string $chave): array
{
    $bruto = $dados[$chave] ?? [];
    if (!is_array($bruto)) {
        throw new HttpErro("O campo '{$chave}' deve ser um array de IDs.", 400, [
            $chave => 'Array de IDs inválido.',
        ]);
    }

    $ids = [];
    foreach ($bruto as $item) {
        $id = filter_var($item, FILTER_VALIDATE_INT);
        if ($id === false || $id <= 0) {
            throw new HttpErro("O campo '{$chave}' contém um ID inválido.", 400);
        }
        $ids[] = $id;
    }

    return array_values(array_unique($ids));
}

function campo_data(array $dados, string $chave): string
{
    $valor = (string) ($dados[$chave] ?? '');
    $partes = explode('-', $valor);
    if (count($partes) !== 3 || !checkdate((int) $partes[1], (int) $partes[2], (int) $partes[0])) {
        throw new HttpErro("O campo '{$chave}' deve ser uma data YYYY-MM-DD.", 400, [
            $chave => 'Data inválida.',
        ]);
    }
    return $valor;
}

function campo_hora(array $dados, string $chave): string
{
    $valor = (string) ($dados[$chave] ?? '');
    if (preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $valor) !== 1) {
        throw new HttpErro("O campo '{$chave}' deve ser HH:mm.", 400, [
            $chave => 'Horário inválido.',
        ]);
    }
    return $valor . ':00';
}

function campo_lista(array $dados, string $chave): array
{
    $valor = $dados[$chave] ?? null;
    if (!is_array($valor) || $valor === []) {
        throw new HttpErro("O campo '{$chave}' deve ser um array não vazio.", 400, [
            $chave => 'Array obrigatório.',
        ]);
    }
    return $valor;
}

function campo_enum(array $dados, string $chave, array $permitidos): string
{
    $valor = (string) ($dados[$chave] ?? '');
    if (!in_array($valor, $permitidos, true)) {
        throw new HttpErro("Valor inválido para '{$chave}'.", 400, [
            $chave => 'Valores aceitos: ' . implode(', ', $permitidos),
        ]);
    }
    return $valor;
}
