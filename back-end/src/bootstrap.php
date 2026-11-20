<?php
declare(strict_types=1);

/**
 * Ponto único de inclusão dos endpoints (seção 6.3.9).
 */
ini_set('display_errors', '0');
error_reporting(E_ALL);

require __DIR__ . '/config/ambiente.php';
carregar_ambiente();

require __DIR__ . '/http/cors.php';
require __DIR__ . '/http/resposta.php';
require __DIR__ . '/http/erro.php';
require __DIR__ . '/http/requisicao.php';
require __DIR__ . '/auth/identidade.php';
require __DIR__ . '/validacao/validadores.php';
require __DIR__ . '/contrato/serializacao.php';
require __DIR__ . '/consultas/aluno.php';
require __DIR__ . '/dominio/avaliacao_aluno.php';
require __DIR__ . '/config/conexao.php';

aplicar_cors();

$pdo = criar_conexao();
