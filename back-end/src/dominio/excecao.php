<?php
declare(strict_types=1);

/**
 * Erro de regra de negócio com código HTTP e erros por campo.
 * O handler global converte esta exceção no envelope padrão da API.
 */
class RegraNegocioException extends RuntimeException
{
    public function __construct(
        string $mensagem,
        public readonly int $codigoHttp = 400,
        public readonly array $erros = [],
    ) {
        parent::__construct($mensagem);
    }
}
