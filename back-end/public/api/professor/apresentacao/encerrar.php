<?php
declare(strict_types=1);

require dirname(__DIR__, 4) . '/src/bootstrap.php';

exigir_metodo('POST');

$professorId = exigir_identidade('professor');
$dataId = query_int('data_id');

if ($dataId === null) {
    responder_erro('O parâmetro data_id é obrigatório.', 400);
}

$resultado = encerrar_apresentacao($pdo, $professorId, $dataId);

responder_sucesso($resultado, 200, 'Apresentação encerrada.');
