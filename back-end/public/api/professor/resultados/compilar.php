<?php
declare(strict_types=1);

require dirname(__DIR__, 4) . '/src/bootstrap.php';

exigir_metodo('GET');

$professorId = exigir_identidade('professor');
$dataId = query_int('data_id');

if ($dataId === null) {
    responder_erro('O parâmetro data_id é obrigatório.', 400);
}

// Garante que a sessão pertence ao professor identificado.
validar_propriedade_data($pdo, $dataId, $professorId);

$resultados = compilar_resultados($pdo, $dataId);

responder_sucesso($resultados);
