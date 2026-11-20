<?php
declare(strict_types=1);

require dirname(__DIR__, 4) . '/src/bootstrap.php';

exigir_metodo('GET');
$alunoId = exigir_identidade('aluno');
$dataId = parametro_inteiro('data_id');

responder_sucesso(compilar_resultados($pdo, $alunoId, $dataId));
