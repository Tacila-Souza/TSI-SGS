<?php
declare(strict_types=1);

class HttpErro extends RuntimeException
{
    public function __construct(
        string $mensagem,
        public readonly int $codigoHttp = 400,
        public readonly ?array $erros = null
    ) {
        parent::__construct($mensagem);
    }
}

function registrar_tratamento_erros(): void
{
    set_exception_handler(function (Throwable $e): void {
        if ($e instanceof HttpErro) {
            responder_erro($e->getMessage(), $e->codigoHttp, $e->erros);
        }

        // SIGNAL SQLSTATE '45000' dos triggers representa regra de negócio.
        if ($e instanceof PDOException && ($e->errorInfo[0] ?? null) === '45000') {
            responder_erro($e->errorInfo[2] ?? 'Regra de negócio violada.', 400);
        }

        error_log('[SGS] Erro não previsto: ' . $e->getMessage());
        responder_erro(
            'Erro interno do servidor.',
            500,
            null,
            $e->getMessage()
        );
    });
}
