<?php
declare(strict_types=1);

/**
 * Converte avisos em exceções e garante que nenhuma saída não-JSON
 * (stack trace, SQL, HTML) seja devolvida pela API.
 */
function registrar_handler_erros(): void
{
    error_reporting(E_ALL);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    date_default_timezone_set('America/Sao_Paulo');

    set_error_handler(static function (int $severidade, string $mensagem, string $arquivo, int $linha): bool {
        if (!(error_reporting() & $severidade)) {
            return false;
        }
        throw new ErrorException($mensagem, 0, $severidade, $arquivo, $linha);
    });

    set_exception_handler(static function (Throwable $erro): void {
        if ($erro instanceof RegraNegocioException) {
            responder_erro($erro->getMessage(), $erro->codigoHttp, $erro->erros);
        }

        error_log('[SGS] ' . $erro->getMessage() . ' em ' . $erro->getFile() . ':' . $erro->getLine());
        responder_erro('Erro interno do servidor.', 500);
    });
}
