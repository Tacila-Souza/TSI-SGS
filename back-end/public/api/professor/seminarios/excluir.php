<?php
declare(strict_types=1);

require dirname(__DIR__, 4) . '/src/bootstrap.php';

exigir_metodo('DELETE');

$professorId = exigir_identidade('professor');
$seminarioId = query_int('id');

if ($seminarioId === null || $seminarioId <= 0) {
    responder_erro('O parâmetro id é obrigatório.', 400);
}

try {
    $pdo->beginTransaction();

    // Bloqueia o seminário durante a exclusão.
    $consulta = $pdo->prepare(
        'SELECT id FROM seminarios
         WHERE id = ? AND professor_id = ?
         FOR UPDATE'
    );

    $consulta->execute([$seminarioId, $professorId]);

    if ($consulta->fetchColumn() === false) {
        $pdo->rollBack();
        responder_erro('Seminário não encontrado.', 404);
    }

    // Verifica se existem registros acadêmicos.
    $verificacoes = [
        "SELECT 1
         FROM presencas p
         JOIN seminario_datas d
           ON d.id = p.seminario_data_id
         WHERE d.seminario_id = ?
         LIMIT 1",

        "SELECT 1
         FROM avaliacoes_notas n
         JOIN seminario_datas d
           ON d.id = n.seminario_data_id
         WHERE d.seminario_id = ?
         LIMIT 1",

        "SELECT 1
         FROM avaliacoes_feedbacks f
         JOIN seminario_datas d
           ON d.id = f.seminario_data_id
         WHERE d.seminario_id = ?
         LIMIT 1"
    ];

    foreach ($verificacoes as $sql) {
        $consulta = $pdo->prepare($sql);
        $consulta->execute([$seminarioId]);

        if ($consulta->fetchColumn() !== false) {
            $pdo->rollBack();

            responder_erro(
                'Não é possível excluir um seminário que já possui presenças ou avaliações.',
                409
            );
        }
    }

    $consulta = $pdo->prepare(
        'DELETE FROM seminarios
         WHERE id = ? AND professor_id = ?'
    );

    $consulta->execute([$seminarioId, $professorId]);

    $pdo->commit();

} catch (Throwable $erro) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    throw $erro;
}

responder_sucesso(
    null,
    200,
    'Seminário excluído com sucesso.'
);
