<?php
declare(strict_types=1);

require dirname(__DIR__, 4) . '/src/bootstrap.php';

exigir_metodo('GET');
$professorId = exigir_identidade('professor');

$dataId = query_int('data_id');
if ($dataId === null) {
    responder_erro("Parâmetro 'data_id' é obrigatório.", 400, [
        'data_id' => 'Informe a sessão.',
    ]);
}

responder_sucesso(carregar_apresentacao($pdo, $professorId, $dataId));