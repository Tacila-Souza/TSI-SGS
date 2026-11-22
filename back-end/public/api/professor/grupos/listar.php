<?php
declare(strict_types=1);

require dirname(__DIR__, 4) . '/src/bootstrap.php';

exigir_metodo('GET');

$professorId = exigir_identidade('professor');

$dataId = query_int('data_id');

if ($dataId === null) {
    responder_erro('O parâmetro data_id é obrigatório.', 400);
}

$grupos = listar_grupos_sessao($pdo, $dataId, $professorId);

responder_sucesso(
    array_map('serializar_grupo', $grupos)
);
