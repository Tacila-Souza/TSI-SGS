<?php
declare(strict_types=1);

require dirname(__DIR__, 4) . '/src/bootstrap.php';

exigir_metodo('DELETE');

$professorId = exigir_identidade('professor');
$seminarioId = query_int('id');

if ($seminarioId === null) {
    responder_erro('O parâmetro id é obrigatório.', 400);
}

$pdo->beginTransaction();
try {
    $stmt = $pdo->prepare(
        'SELECT id FROM seminarios WHERE id = ? AND professor_id = ? FOR UPDATE'
    );
    $stmt->execute([$seminarioId, $professorId]);

    if ($stmt->fetchColumn() === false) {
        throw new HttpErro('Seminário não encontrado.', 404);
    }

    $verificacoes = [
        'SELECT 1 FROM presencas p
         INNER JOIN seminario_datas d ON d.id = p.seminario_data_id
         WHERE d.seminario_id = ? LIMIT 1',
        'SELECT 1 FROM avaliacoes_notas n
         INNER JOIN seminario_datas d ON d.id = n.seminario_data_id
         WHERE d.seminario_id = ? LIMIT 1',
        'SELECT 1 FROM avaliacoes_feedbacks f
         INNER JOIN seminario_datas d ON d.id = f.seminario_data_id
         WHERE d.seminario_id = ? LIMIT 1',
    ];

    foreach ($verificacoes as $sql) {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$seminarioId]);
        if ($stmt->fetchColumn() !== false) {
            throw new HttpErro(
                'Não é possível excluir um seminário que já possui presenças ou avaliações.',
                409
            );
        }
    }

    $stmt = $pdo->prepare('DELETE FROM seminarios WHERE id = ? AND professor_id = ?');
    $stmt->execute([$seminarioId, $professorId]);

    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    throw $e;
}

responder_sucesso(null, 200, 'Seminário excluído com sucesso.');
