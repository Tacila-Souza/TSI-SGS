<?php
declare(strict_types=1);

/**
 * Carrega variáveis de back-end/.env para o ambiente de desenvolvimento.
 * Variáveis já definidas no ambiente do sistema não são sobrescritas.
 */
function carregar_ambiente(): void
{
    $arquivo = dirname(__DIR__, 2) . '/.env';

    if (!is_file($arquivo)) {
        return;
    }

    $linhas = file($arquivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($linhas === false) {
        return;
    }

    foreach ($linhas as $linha) {
        $linha = trim($linha);
        if ($linha === '' || str_starts_with($linha, '#')) {
            continue;
        }

        $partes = explode('=', $linha, 2);
        $chave = trim($partes[0]);
        $valor = isset($partes[1]) ? trim($partes[1]) : '';
        $valor = trim($valor, "\"'");

        if ($chave === '' || getenv($chave) !== false) {
            continue;
        }

        putenv("{$chave}={$valor}");
        $_ENV[$chave] = $valor;
    }
}

carregar_ambiente();

/** Identidade padrão do MVP. O frontend envia esses IDs na query string. */
if (!defined('PROFESSOR_PADRAO')) {
    define('PROFESSOR_PADRAO', (int) (getenv('PROFESSOR_PADRAO') ?: 1));
}
if (!defined('ALUNO_PADRAO')) {
    define('ALUNO_PADRAO', (int) (getenv('ALUNO_PADRAO') ?: 1));
}
