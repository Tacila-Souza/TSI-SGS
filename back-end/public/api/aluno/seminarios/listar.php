<?php
declare(strict_types=1);

require dirname(__DIR__, 4) . '/src/bootstrap.php';

exigir_metodo('GET');

$alunoId = exigir_identidade('aluno');

$seminarios = listar_seminarios_aluno($pdo, $alunoId);

responder_sucesso(array_map('serializar_seminario', $seminarios));
