<?php
declare(strict_types=1);

/**
 * Carrega o arquivo .env da raiz do backend para o ambiente do processo.
 * Não há dependência de bibliotecas externas.
 */
function carregar_ambiente(string $caminho): void
{
    if (!is_file($caminho)) {
        return;
    }

    $linhas = file($caminho, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($linhas === false) {
        return;
    }

    foreach ($linhas as $linha) {
        $linha = trim($linha);
        if ($linha === '' || str_starts_with($linha, '#')) {
            continue;
        }

        $partes = explode('=', $linha, 2);
        if (count($partes) !== 2) {
            continue;
        }

        $chave = trim($partes[0]);
        $valor = trim($partes[1]);
        if ($chave === '') {
            continue;
        }

        putenv("{$chave}={$valor}");
        $_ENV[$chave] = $valor;
    }
}
