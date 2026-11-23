<?php
declare(strict_types=1);

require dirname(__DIR__, 4) . '/src/bootstrap.php';

exigir_metodo('POST');

$professorId = exigir_identidade('professor');
$dataId = query_int('data_id');
$grupoId = query_int('grupo_id');

if ($dataId === null) {
    responder_erro('O parâmetro data_id é obrigatório.', 400);
}

if ($grupoId === null) {
    responder_erro('O parâmetro grupo_id é obrigatório.', 400);
}

$dados = ler_json();

$apresentacao = salvar_avaliacao_professor($pdo, $professorId, $dataId, $grupoId, $dados);

responder_sucesso($apresentacao, 200, 'Avaliação salva com sucesso.');
