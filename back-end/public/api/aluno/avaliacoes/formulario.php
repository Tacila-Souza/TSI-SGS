<?php
declare(strict_types=1);

require dirname(__DIR__, 4) . '/src/bootstrap.php';

exigir_metodo('GET');
$alunoId = exigir_identidade('aluno');
$dataId = parametro_inteiro('data_id');

$sessao = carregar_sessao($pdo, $dataId);
if ($sessao === null) {
    throw new RegraNegocioException('Sessão não encontrada.', 404);
}
if ((string) $sessao['status'] !== 'em_andamento') {
    throw new RegraNegocioException('A avaliação está disponível apenas durante a sessão em andamento.', 400);
}
if (!aluno_matriculado($pdo, $alunoId, (int) $sessao['seminario_id'])) {
    throw new RegraNegocioException('Aluno não matriculado neste seminário.', 403);
}
if (!aluno_presente($pdo, $alunoId, $dataId)) {
    throw new RegraNegocioException('Aluno não consta na chamada desta sessão.', 403);
}

responder_sucesso(montar_formulario_aluno($pdo, $alunoId, $dataId));
