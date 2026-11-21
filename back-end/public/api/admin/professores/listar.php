<?php
declare(strict_types=1);

require dirname(__DIR__, 4) . '/src/bootstrap.php';

exigir_metodo('GET');
exigir_identidade('admin');

$busca = query_texto('busca');
$incluirInativos = query_texto('incluir_inativos') === '1';

$linhas = listar_professores($pdo, $busca, $incluirInativos);
responder_sucesso(array_map('serializar_professor', $linhas));
