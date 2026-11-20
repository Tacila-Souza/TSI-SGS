<?php
declare(strict_types=1);

/**
 * Validação de entrada do endpoint de avaliação do aluno.
 * O backend não confia na validação feita no React.
 */

function inteiro_positivo(mixed $valor, string $campo): int
{
    if (!(is_int($valor) || (is_string($valor) && ctype_digit($valor))) || (int) $valor <= 0) {
        falhar("O campo '{$campo}' deve ser um inteiro positivo.", 400, [$campo => 'Valor inválido.']);
    }
    return (int) $valor;
}

function validar_notas(mixed $notas): array
{
    if (!is_array($notas) || $notas === []) {
        falhar('Informe ao menos uma nota.', 400, ['notas' => 'A lista de notas é obrigatória.']);
    }

    $resultado = [];
    foreach ($notas as $nota) {
        if (!is_array($nota) || !array_key_exists('criterioId', $nota)
            || !array_key_exists('alunoId', $nota) || !array_key_exists('nota', $nota)) {
            falhar('Nota inválida.', 400, ['notas' => 'Cada nota deve ter criterioId, alunoId e nota.']);
        }

        $criterioId = inteiro_positivo($nota['criterioId'], 'criterioId');
        $alunoId = null;
        if ($nota['alunoId'] !== null && $nota['alunoId'] !== '') {
            $alunoId = inteiro_positivo($nota['alunoId'], 'alunoId');
        }

        $valor = $nota['nota'];
        if (!(is_int($valor) || is_float($valor)) || !is_finite((float) $valor)
            || (float) $valor < 0 || (float) $valor > 10) {
            falhar('A nota deve estar entre 0 e 10.', 400, ['notas' => 'Nota fora do intervalo permitido.']);
        }

        $resultado[] = [
            'criterioId' => $criterioId,
            'alunoId' => $alunoId,
            'nota' => round((float) $valor, 2),
        ];
    }

    return $resultado;
}

function validar_feedback(mixed $feedback): string
{
    if ($feedback === null) {
        return '';
    }
    if (!is_string($feedback)) {
        falhar('O feedback deve ser um texto.', 400, ['feedback' => 'Tipo inválido.']);
    }
    $texto = trim($feedback);
    if (mb_strlen($texto) > 65535) {
        falhar('O feedback é longo demais.', 400, ['feedback' => 'Máximo de 65535 caracteres.']);
    }
    return $texto;
}
