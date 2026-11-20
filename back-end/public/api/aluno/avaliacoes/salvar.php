<?php
declare(strict_types=1);

require dirname(__DIR__, 4) . '/src/bootstrap.php';

exigir_metodo('POST');
$alunoId = exigir_identidade('aluno');
$dataId = inteiro_da_query('data_id');
$grupoId = inteiro_da_query('grupo_id');

$corpo = ler_json();
$notas = validar_notas($corpo['notas'] ?? null);
$feedback = validar_feedback($corpo['feedback'] ?? null);

responder_sucesso(salvar_avaliacao_aluno($pdo, $alunoId, $dataId, $grupoId, $notas, $feedback));
