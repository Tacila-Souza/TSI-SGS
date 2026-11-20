<?php
declare(strict_types=1);

/**
 * Validação de método, leitura do corpo JSON e parâmetros da query string.
 * A query string segue a convenção snake_case (contrato, seção 4).
 */
function exigir_metodo(string $metodo): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== $metodo) {
        responder_erro('Método HTTP não permitido para esta operação.', 400);
    }
}

function ler_json(): array
{
    $corpo = file_get_contents('php://input');
    if ($corpo === false || trim($corpo) === '') {
        responder_erro('Corpo JSON obrigatório.', 400);
    }

    $dados = json_decode($corpo, true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($dados)) {
        responder_erro('JSON inválido.', 400);
    }

    return $dados;
}

function inteiro_da_query(string $chave, bool $obrigatorio = true): ?int
{
    $valor = $_GET[$chave] ?? null;

    if ($valor === null || $valor === '') {
        if ($obrigatorio) {
            responder_erro("O parâmetro '{$chave}' é obrigatório.", 400);
        }
        return null;
    }

    $texto = (string) $valor;
    if (!ctype_digit($texto) || (int) $texto <= 0) {
        responder_erro("O parâmetro '{$chave}' é inválido.", 400);
    }

    return (int) $texto;
}
