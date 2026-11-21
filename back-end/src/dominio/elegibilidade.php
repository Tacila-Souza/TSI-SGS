<?php
declare(strict_types=1);

/**
 * A apresentação só pode ser iniciada quando a sessão está agendada,
 * tem ao menos um grupo e a chamada já foi registrada.
 * Se já estiver em andamento, não há nada a validar.
 */
function exigir_apresentacao_iniciavel(PDO $pdo, int $professorId, int $dataId): void
{
    $data = validar_propriedade_data($pdo, $dataId, $professorId);

    if ($data['status'] === 'em_andamento') {
        return;
    }

    if ($data['status'] !== 'agendada') {
        responder_erro('Esta sessão não pode ser iniciada.', 409);
    }

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM grupos WHERE seminario_data_id = ?');
    $stmt->execute([$dataId]);
    if ((int) $stmt->fetchColumn() === 0) {
        responder_erro('Cadastre ao menos um grupo antes de iniciar.', 409);
    }

    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM presencas WHERE seminario_data_id = ? AND presente = 1'
    );
    $stmt->execute([$dataId]);
    if ((int) $stmt->fetchColumn() === 0) {
        responder_erro('Registre a chamada antes de iniciar a apresentação.', 409);
    }
}

/**
 * O aluno só avalia com a sessão em andamento, matriculado na disciplina
 * do seminário e presente na chamada.
 */
function exigir_aluno_elegivel(PDO $pdo, int $alunoId, int $dataId): void
{
    $stmt = $pdo->prepare(
        'SELECT sd.status, s.id AS seminario_id
         FROM seminario_datas sd
         INNER JOIN seminarios s ON s.id = sd.seminario_id
         WHERE sd.id = ?'
    );
    $stmt->execute([$dataId]);
    $data = $stmt->fetch();
    if ($data === false) {
        responder_erro('Sessão não encontrada.', 404);
    }

    validar_matricula_seminario($pdo, $alunoId, (int) $data['seminario_id']);

    if ($data['status'] !== 'em_andamento') {
        responder_erro('Esta sessão não está aberta para avaliação.', 409);
    }

    $stmt = $pdo->prepare(
        'SELECT presente FROM presencas WHERE seminario_data_id = ? AND aluno_id = ?'
    );
    $stmt->execute([$dataId, $alunoId]);
    $presente = $stmt->fetchColumn();
    if ($presente === false || (int) $presente !== 1) {
        responder_erro('Somente alunos presentes na chamada podem avaliar.', 403);
    }
}