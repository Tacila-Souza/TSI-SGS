<?php

declare(strict_types=1);

function buscar_sessao_do_professor(
    PDO $pdo,
    int $professorId,
    int $dataId
): array {

    // 1. Buscar a sessão e o seminário dela

    $consulta = $pdo->prepare(
        'SELECT
            sd.id,
            sd.status,
            s.disciplina_id,
            s.professor_id
         FROM seminario_datas sd
         INNER JOIN seminarios s
            ON s.id = sd.seminario_id
         WHERE sd.id = ?'
    );

    $consulta->execute([$dataId]);

    $sessao = $consulta->fetch();

    if (!$sessao) {
        responder_erro('Sessão não encontrada.', 404);
    }

    // 2. Verificar se a sessão é deste professor

    if ((int) $sessao['professor_id'] !== $professorId) {
        responder_erro(
            'Você não possui acesso a esta sessão.',
            403
        );
    }

    return $sessao;
}


function listar_presencas(
    PDO $pdo,
    int $professorId,
    int $dataId
): array {

    // 1. Conferir a sessão e o dono dela

    $sessao = buscar_sessao_do_professor(
        $pdo,
        $professorId,
        $dataId
    );

    // 2. Buscar os alunos ativos da disciplina,
    //    com a presença já salva (se existir)

    $consulta = $pdo->prepare(
        'SELECT
            a.id,
            a.nome_completo,
            COALESCE(p.presente, 0) AS presente
         FROM alunos a
         INNER JOIN disciplina_aluno da
            ON da.aluno_id = a.id
         LEFT JOIN presencas p
            ON p.aluno_id = a.id
           AND p.seminario_data_id = ?
         WHERE da.disciplina_id = ?
           AND a.status = ?
         ORDER BY a.nome_completo ASC'
    );

    $consulta->execute([
        $dataId,
        (int) $sessao['disciplina_id'],
        'ativo'
    ]);

    // 3. Montar a resposta no formato do contrato

    $lista = [];

    foreach ($consulta->fetchAll() as $aluno) {

        $lista[] = [
            'alunoId' => (int) $aluno['id'],
            'nome' => $aluno['nome_completo'],
            'presente' => (bool) (int) $aluno['presente']
        ];
    }

    return $lista;
}

function salvar_presencas(
    PDO $pdo,
    int $professorId,
    int $dataId,
    array $dados
): array {

    // 1. Validar a lista enviada

    $itens = validar_lista(
        $dados['presencas'] ?? null,
        'presencas'
    );

    $alunosVistos = [];

    foreach ($itens as $item) {

        if (!is_array($item)) {
            responder_erro('Item de presença inválido.', 400);
        }

        $alunoId = validar_inteiro_positivo(
            $item['alunoId'] ?? null,
            'alunoId'
        );

        if (!isset($item['presente']) || !is_bool($item['presente'])) {
            responder_erro(
                'O campo presente deve ser verdadeiro ou falso.',
                400
            );
        }

        if (isset($alunosVistos[$alunoId])) {
            responder_erro(
                "O aluno {$alunoId} aparece mais de uma vez.",
                400
            );
        }

        $alunosVistos[$alunoId] = true;
    }

    // 2. Conferir a sessão e o dono dela

    $sessao = buscar_sessao_do_professor(
        $pdo,
        $professorId,
        $dataId
    );

    if (!in_array($sessao['status'], ['agendada', 'em_andamento'], true)) {
        responder_erro(
            'Esta sessão não permite alterar a presença.',
            409
        );
    }

    // 3. Conferir se cada aluno é ativo e da disciplina

    $disciplinaId = (int) $sessao['disciplina_id'];

    $consultaAluno = $pdo->prepare(
        'SELECT a.id
         FROM alunos a
         INNER JOIN disciplina_aluno da
            ON da.aluno_id = a.id
         WHERE a.id = ?
           AND da.disciplina_id = ?
           AND a.status = ?'
    );

    foreach ($itens as $item) {

        $consultaAluno->execute([
            $item['alunoId'],
            $disciplinaId,
            'ativo'
        ]);

        if (!$consultaAluno->fetchColumn()) {
            responder_erro(
                "O aluno {$item['alunoId']} não está ativo " .
                "ou matriculado nesta disciplina.",
                400
            );
        }
    }

    // 4. Gravar tudo de uma vez (transação)

    $gravar = $pdo->prepare(
        'INSERT INTO presencas (seminario_data_id, aluno_id, presente)
         VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE presente = VALUES(presente)'
    );

    try {

        $pdo->beginTransaction();

        foreach ($itens as $item) {
            $gravar->execute([
                $dataId,
                $item['alunoId'],
                $item['presente'] ? 1 : 0
            ]);
        }

        $pdo->commit();

    } catch (Throwable $erro) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        throw $erro;
    }

    // 5. Devolver a lista atualizada

    return listar_presencas($pdo, $professorId, $dataId);
}