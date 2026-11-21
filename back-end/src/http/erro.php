<?php

declare(strict_types=1);

error_reporting(E_ALL);

ini_set('display_errors', '0');
ini_set('log_errors', '1');

set_error_handler(
    function (
        int $nivel,
        string $mensagem,
        string $arquivo,
        int $linha
    ): bool {
        if (!(error_reporting() & $nivel)) {
            return false;
        }

        throw new ErrorException(
            $mensagem,
            0,
            $nivel,
            $arquivo,
            $linha
        );
    }
);

set_exception_handler(function (Throwable $erro): void {
    error_log((string) $erro);

    responder_erro(
        'Erro interno do servidor.',
        500
    );
});
