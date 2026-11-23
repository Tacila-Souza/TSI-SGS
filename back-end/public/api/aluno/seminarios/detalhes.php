<?php
declare(strict_types=1);

require dirname(__DIR__, 4) . '/src/bootstrap.php';

exigir_metodo('GET');

$alunoId = exigir_identidade('aluno');
$seminarioId = query_int('id');

if ($seminarioId === null) {
    responder_erro('O parâmetro id é obrigatório.', 400);
}

$seminario = buscar_seminario($pdo, $seminarioId);
if ($seminario === null) {
    responder_erro('Seminário não encontrado.', 404);
}

validar_matricula_seminario($pdo, $alunoId, $seminarioId);

responder_sucesso(serializar_seminario($seminario));
