<?php
declare(strict_types=1);

require dirname(__DIR__, 4) . '/src/bootstrap.php';

exigir_metodo('POST');

$alunoId = exigir_identidade('aluno');
$dataId = query_int('data_id');
$grupoId = query_int('grupo_id');

if ($dataId === null) {
    responder_erro('O parâmetro data_id é obrigatório.', 400);
}

if ($grupoId === null) {
    responder_erro('O parâmetro grupo_id é obrigatório.', 400);
}

$dados = ler_json();

$formulario = salvar_avaliacao_aluno($pdo, $alunoId, $dataId, $grupoId, $dados);

responder_sucesso($formulario, 200, 'Avaliação salva com sucesso.');
