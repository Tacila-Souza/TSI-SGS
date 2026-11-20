<?php
declare(strict_types=1);

require_once __DIR__ . '/config/ambiente.php';
carregar_ambiente(dirname(__DIR__) . '/.env');

require_once __DIR__ . '/http/resposta.php';
require_once __DIR__ . '/dominio/excecao.php';
require_once __DIR__ . '/http/erro.php';
registrar_handler_erros();

require_once __DIR__ . '/config/conexao.php';
require_once __DIR__ . '/http/cors.php';
require_once __DIR__ . '/http/requisicao.php';
require_once __DIR__ . '/auth/identidade.php';
require_once __DIR__ . '/validacao/validadores.php';

require_once __DIR__ . '/contrato/seminario.php';
require_once __DIR__ . '/contrato/resultado.php';
require_once __DIR__ . '/consultas/seminario.php';
require_once __DIR__ . '/dominio/avaliacao.php';
require_once __DIR__ . '/dominio/resultados.php';

configurar_cors();
