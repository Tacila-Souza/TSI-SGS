<?php
declare(strict_types=1);

require dirname(__DIR__, 4) . '/src/bootstrap.php';

exigir_metodo('GET');
$alunoId = exigir_identidade('aluno');
$seminarioId = parametro_inteiro('id');

$seminario = carregar_seminario_completo($pdo, $seminarioId);
if ($seminario === null) {
    throw new RegraNegocioException('Seminário não encontrado.', 404);
}
if (!aluno_matriculado($pdo, $alunoId, $seminarioId)) {
    throw new RegraNegocioException('Aluno não matriculado neste seminário.', 403);
}

responder_sucesso($seminario);
