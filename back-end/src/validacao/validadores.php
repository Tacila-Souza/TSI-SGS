<?php
declare(strict_types=1);

function texto_obrigatorio(mixed $valor, string $campo, int $maximo = 5000): string
{
    if (!is_string($valor)) {
        throw new RegraNegocioException('Dados inválidos.', 400, [$campo => 'Deve ser um texto.']);
    }

    $texto = trim($valor);
    if ($texto === '') {
        throw new RegraNegocioException('Dados inválidos.', 400, [$campo => 'Campo obrigatório.']);
    }
    if (mb_strlen($texto) > $maximo) {
        throw new RegraNegocioException('Dados inválidos.', 400, [$campo => "Máximo de {$maximo} caracteres."]);
    }

    return $texto;
}

function texto_opcional(mixed $valor, string $campo, int $maximo = 5000): string
{
    if ($valor === null) {
        return '';
    }
    if (!is_string($valor)) {
        throw new RegraNegocioException('Dados inválidos.', 400, [$campo => 'Deve ser um texto.']);
    }

    $texto = trim($valor);
    if (mb_strlen($texto) > $maximo) {
        throw new RegraNegocioException('Dados inválidos.', 400, [$campo => "Máximo de {$maximo} caracteres."]);
    }

    return $texto;
}

function validar_notas(mixed $bruto, array $criterios, array $integrantesIds): array
{
    if (!is_array($bruto) || !array_is_list($bruto)) {
        throw new RegraNegocioException('Dados inválidos.', 400, ['notas' => 'Envie uma lista de notas.']);
    }

    $porId = [];
    foreach ($criterios as $criterio) {
        $porId[(int) $criterio['id']] = $criterio;
    }

    $integrantes = array_map('intval', $integrantesIds);
    $normalizadas = [];
    $vistas = [];

    foreach ($bruto as $item) {
        if (!is_array($item)) {
            throw new RegraNegocioException('Dados inválidos.', 400, ['notas' => 'Cada nota deve ser um objeto.']);
        }

        $criterioId = filtrar_id($item['criterioId'] ?? null);
        if (!isset($porId[$criterioId])) {
            throw new RegraNegocioException('Dados inválidos.', 400, ['notas' => 'Critério não pertence a este seminário.']);
        }

        $alunoBruto = $item['alunoId'] ?? null;
        $tipo = $porId[$criterioId]['tipo_avaliacao'];
        $alunoId = null;

        if ($tipo === 'grupo') {
            if ($alunoBruto !== null) {
                throw new RegraNegocioException('Dados inválidos.', 400, ['notas' => 'Critério de grupo não admite alunoId.']);
            }
        } else {
            $alunoId = filtrar_id($alunoBruto);
            if (!in_array($alunoId, $integrantes, true)) {
                throw new RegraNegocioException('Dados inválidos.', 400, ['notas' => 'Aluno avaliado não integra o grupo.']);
            }
        }

        $nota = $item['nota'] ?? null;
        if (!is_int($nota) && !is_float($nota)) {
            throw new RegraNegocioException('Dados inválidos.', 400, ['notas' => 'Nota deve ser um número.']);
        }
        if ($nota < 0 || $nota > 10) {
            throw new RegraNegocioException('Dados inválidos.', 400, ['notas' => 'Nota deve estar entre 0 e 10.']);
        }

        $chave = $criterioId . ':' . ($alunoId ?? 'g');
        if (isset($vistas[$chave])) {
            throw new RegraNegocioException('Dados inválidos.', 400, ['notas' => 'Nota duplicada para o mesmo critério/aluno.']);
        }
        $vistas[$chave] = true;

        $normalizadas[] = [
            'criterioId' => $criterioId,
            'alunoId' => $alunoId,
            'nota' => round((float) $nota, 2),
        ];
    }

    if ($normalizadas === []) {
        throw new RegraNegocioException('Dados inválidos.', 400, ['notas' => 'Envie ao menos uma nota.']);
    }

    return $normalizadas;
}

function filtrar_id(mixed $valor): int
{
    if (!is_int($valor) || $valor <= 0) {
        throw new RegraNegocioException('Dados inválidos.', 400, ['id' => 'Identificador inválido.']);
    }

    return $valor;
}

function inteiro_positivo(mixed $valor, string $campo): int
{
    if (!is_int($valor) || $valor <= 0) {
        throw new RegraNegocioException('Dados inválidos.', 400, [$campo => 'Informe um número inteiro positivo.']);
    }

    return $valor;
}

function validar_status(mixed $valor): string
{
    if ($valor === null) {
        return 'ativo';
    }
    if (!is_string($valor) || !in_array($valor, ['ativo', 'inativo'], true)) {
        throw new RegraNegocioException('Dados inválidos.', 400, ['status' => 'Escolha ativo ou inativo.']);
    }

    return $valor;
}

function validar_lista_ids(mixed $bruto, string $campo, bool $permitirVazio = false): array
{
    if (!is_array($bruto) || !array_is_list($bruto)) {
        throw new RegraNegocioException('Dados inválidos.', 400, [$campo => 'Envie uma lista de IDs.']);
    }

    $ids = [];
    $vistos = [];
    foreach ($bruto as $item) {
        if (!is_int($item) || $item <= 0) {
            throw new RegraNegocioException('Dados inválidos.', 400, [$campo => 'IDs devem ser inteiros positivos.']);
        }
        if (isset($vistos[$item])) {
            throw new RegraNegocioException('Dados inválidos.', 400, [$campo => 'IDs duplicados não são permitidos.']);
        }
        $vistos[$item] = true;
        $ids[] = $item;
    }

    if (!$permitirVazio && $ids === []) {
        throw new RegraNegocioException('Dados inválidos.', 400, [$campo => 'Envie ao menos um ID.']);
    }

    return $ids;
}
