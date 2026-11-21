<?php
declare(strict_types=1);

require dirname(__DIR__, 4) . '/src/bootstrap.php';

exigir_metodo('DELETE');
exigir_identidade('admin');
$id = query_int('id');

if (buscar_disciplina($pdo, $id) === null) {
    responder_erro('Disciplina não encontrada.', 404);
}

$stmt = $pdo->prepare('SELECT COUNT(*) FROM seminarios WHERE disciplina_id = ?');
$stmt->execute([$id]);
if ((int) $stmt->fetchColumn() > 0) {
    responder_erro(
        'Não é possível excluir uma disciplina com seminários vinculados.',
        409
    );
}

$pdo->beginTransaction();
try {
    $stmt = $pdo->prepare('DELETE FROM disciplina_aluno WHERE disciplina_id = ?');
    $stmt->execute([$id]);

    $stmt = $pdo->prepare('DELETE FROM disciplinas WHERE id = ?');
    $stmt->execute([$id]);

    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    throw $e;
}

responder_sucesso(['id' => $id], 200, 'Disciplina excluída.');
