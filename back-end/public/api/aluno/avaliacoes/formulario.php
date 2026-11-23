<?php
declare(strict_types=1);

require dirname(__DIR__, 4) . '/src/bootstrap.php';

exigir_metodo('GET');
$alunoId = exigir_identidade('aluno');
$dataId = query_int('data_id');

responder_sucesso(formulario_avaliacao_aluno($pdo, $alunoId, $dataId));
