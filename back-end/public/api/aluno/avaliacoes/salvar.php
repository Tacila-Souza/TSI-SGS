<?php
declare(strict_types=1);

require dirname(__DIR__, 4) . '/src/bootstrap.php';

exigir_metodo('POST');
$alunoId = exigir_identidade('aluno');
$dataId = parametro_inteiro('data_id');
$grupoId = parametro_inteiro('grupo_id');
$dados = ler_json();

responder_sucesso(salvar_avaliacao_aluno($pdo, $alunoId, $dataId, $grupoId, $dados));
