<?php
declare(strict_types=1);

require dirname(__DIR__, 4) . '/src/bootstrap.php';

exigir_metodo('PUT');

$professorId = exigir_identidade('professor');

$dataId = query_int('data_id');

if ($dataId === null) {
    responder_erro('O parâmetro data_id é obrigatório.', 400);
}

$dados = ler_json();

$grupos = sincronizar_grupos($pdo, $professorId, $dataId, $dados);

responder_sucesso(
    array_map('serializar_grupo', $grupos),
    200,
    'Grupos atualizados com sucesso.'
);
