<?php
declare(strict_types=1);

require dirname(__DIR__, 4) . '/src/bootstrap.php';

exigir_metodo('GET');

$alunoId = exigir_identidade('aluno');
$dataId = query_int('data_id');

if ($dataId === null) {
    responder_erro('O parâmetro data_id é obrigatório.', 400);
}

responder_sucesso(formulario_avaliacao_aluno($pdo, $alunoId, $dataId));
