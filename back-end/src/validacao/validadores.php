<?php

declare(strict_types=1);

function validar_texto(
    mixed $valor,
    string $campo,
    int $limite = 255
): string {
    if (!is_string($valor) || trim($valor) === '') {
        responder_erro("O campo {$campo} é obrigatório.", 400);
    }

    $valor = trim($valor);

    if (mb_strlen($valor, 'UTF-8') > $limite) {
        responder_erro(
            "O campo {$campo} excede o limite de caracteres.",
            400
        );
    }

    return $valor;
}

function validar_inteiro_positivo(
    mixed $valor,
    string $campo
): int {
    if (!is_int($valor) || $valor <= 0) {
        responder_erro(
            "O campo {$campo} deve ser um inteiro positivo.",
            400
        );
    }

    return $valor;
}

function validar_lista(
    mixed $valor,
    string $campo
): array {
    if (!is_array($valor) || !array_is_list($valor) || empty($valor)) {
        responder_erro(
            "O campo {$campo} deve conter pelo menos um item.",
            400
        );
    }

    return $valor;
}

function validar_opcao(
    mixed $valor,
    string $campo,
    array $opcoes
): string {
    if (!is_string($valor) || !in_array($valor, $opcoes, true)) {
        responder_erro(
            "O campo {$campo} possui um valor inválido.",
            400
        );
    }

    return $valor;
}
