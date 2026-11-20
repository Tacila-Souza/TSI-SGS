<?php
declare(strict_types=1);

/**
 * Exceções HTTP e tratamento global (seção 6.3.10 / DP-026).
 * Nenhum detalhe interno (SQL, stack trace) é enviado ao frontend.
 */
class HttpExcecao extends RuntimeException
{
    public function __construct(
        string $mensagem,
        int $codigo = 400,
        public readonly array $erros = []
    ) {
        parent::__construct($mensagem, $codigo);
    }
}

function falhar(string $mensagem, int $codigo = 400, array $erros = []): never
{
    throw new HttpExcecao($mensagem, $codigo, $erros);
}

function tratar_excecao(Throwable $erro): void
{
    if ($erro instanceof HttpExcecao) {
        responder_erro($erro->getMessage(), $erro->getCode(), $erro->erros ?: null);
    }

    if ($erro instanceof PDOException) {
        $estado = $erro->getCode();
        if ($estado === '23000') {
            responder_erro('Conflito de dados. Verifique os campos únicos.', 409);
        }
        if ($estado === '45000') {
            responder_erro($erro->errorInfo[2] ?? 'Regra de negócio violada.', 400);
        }
    }

    error_log('[SGS] ' . $erro->getMessage() . ' em ' . $erro->getFile() . ':' . $erro->getLine());
    responder_erro('Erro interno do servidor.', 500);
}

set_exception_handler('tratar_excecao');
