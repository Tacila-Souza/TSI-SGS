<?php
declare(strict_types=1);

require dirname(__DIR__, 4) . '/src/bootstrap.php';

exigir_metodo('GET');

$alunoId = exigir_identidade('aluno');
$dataId = query_int('data_id');

if ($dataId === null) {
    responder_erro('O parâmetro data_id é obrigatório.', 400);
}

$stmt = $pdo->prepare('SELECT seminario_id FROM seminario_datas WHERE id = ?');
$stmt->execute([$dataId]);
$seminarioId = $stmt->fetchColumn();

if ($seminarioId === false) {
    responder_erro('Sessão não encontrada.', 404);
}

// Aluno só consulta resultados de seminários em que está matriculado.
validar_matricula_seminario($pdo, $alunoId, (int) $seminarioId);

responder_sucesso(compilar_resultados($pdo, $dataId, $alunoId));
