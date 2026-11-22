<?php
declare(strict_types=1);

require_once __DIR__ . '/config/ambiente.php';
require_once __DIR__ . '/http/cors.php';
require_once __DIR__ . '/http/resposta.php';
require_once __DIR__ . '/http/erro.php';
require_once __DIR__ . '/http/requisicao.php';
require_once __DIR__ . '/config/conexao.php';
require_once __DIR__ . '/auth/identidade.php';
require_once __DIR__ . '/validacao/validadores.php';
require_once __DIR__ . '/contrato/serializadores.php';
require_once __DIR__ . '/consultas/admin.php';
require_once __DIR__ . '/consultas/professor.php';
require_once __DIR__ . '/consultas/aluno.php';
require_once __DIR__ . '/dominio/seminarios.php';
require_once __DIR__ . '/dominio/grupos.php';
require_once __DIR__ . '/dominio/presencas.php';
require_once __DIR__ . '/dominio/avaliacao.php';
require_once __DIR__ . '/dominio/resultados.php';
require_once __DIR__ . '/dominio/elegibilidade.php';

registrar_tratamento_erros();
