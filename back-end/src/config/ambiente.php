<?php
declare(strict_types=1);

/**
 * Carrega as variáveis de ambiente do arquivo back-end/.env (não versionado).
 * Variáveis já definidas no processo não são sobrescritas.
 */
function carregar_ambiente(): void
{
    $arquivo = dirname(__DIR__, 2) . '/.env';

    if (is_file($arquivo)) {
        $linhas = file($arquivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
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
            $valor = trim(trim($partes[1]), "\"'");
            if ($chave !== '' && getenv($chave) === false) {
                putenv("{$chave}={$valor}");
                $_ENV[$chave] = $valor;
            }
        }
    }

    if (!defined('PROFESSOR_PADRAO')) {
        define('PROFESSOR_PADRAO', (int) (getenv('PROFESSOR_PADRAO') ?: 1));
    }
    if (!defined('ALUNO_PADRAO')) {
        define('ALUNO_PADRAO', (int) (getenv('ALUNO_PADRAO') ?: 1));
    }
}
