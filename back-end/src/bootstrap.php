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
require_once __DIR__ . '/contrato/usuario.php';
require_once __DIR__ . '/contrato/curso.php';
require_once __DIR__ . '/contrato/disciplina.php';
require_once __DIR__ . '/consultas/seminario.php';
require_once __DIR__ . '/consultas/usuario.php';
require_once __DIR__ . '/consultas/curso.php';
require_once __DIR__ . '/consultas/disciplina.php';
require_once __DIR__ . '/dominio/avaliacao.php';
require_once __DIR__ . '/dominio/resultados.php';
require_once __DIR__ . '/dominio/usuario.php';
require_once __DIR__ . '/dominio/disciplina.php';

configurar_cors();
