<?php
declare(strict_types=1);

/**
 * Serializa o DTO de resultados (contrato 7.10).
 * Os valores já vêm calculados e arredondados pelo domínio.
 */
function serializar_resultado(array $resultado): array
{
    return [
        'seminarioId' => (int) $resultado['seminarioId'],
        'dataId' => (int) $resultado['dataId'],
        'dataSessao' => (string) $resultado['dataSessao'],
        'semAvaliadores' => (bool) $resultado['semAvaliadores'],
        'criterios' => array_map('serializar_criterio', $resultado['criterios']),
        'ranking' => $resultado['ranking'],
        'feedbacks' => $resultado['feedbacks'],
    ];
}
