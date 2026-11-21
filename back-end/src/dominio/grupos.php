<?php

declare(strict_types=1);

function criar_grupo(
    PDO $pdo,
    int $professorId,
    int $dataId,
    array $dados
): array {

    // 1. Validar tema

    $tema = validar_texto(
        $dados['tema'] ?? null,
        'tema',
        200
    );

    // 2. Validar integrantes

    $integrantes = $dados['integrantesIds'] ?? null;

    if (
        !is_array($integrantes)
        || !array_is_list($integrantes)
    ) {
        responder_erro(
            'Lista de integrantes inválida.',
            400
        );
    }

    foreach ($integrantes as $alunoId) {

        validar_inteiro_positivo(
            $alunoId,
            'alunoId'
        );
    }

    if (
        count($integrantes)
        !== count(array_unique($integrantes))
    ) {
        responder_erro(
            'Existem alunos duplicados na lista.',
            400
        );
    }

    // 3. Iniciar transação

    $pdo->beginTransaction();

    try {

        // Buscar e bloquear a sessão durante o cadastro

        $consulta = $pdo->prepare(
            'SELECT
                sd.id,
                sd.status,
                s.disciplina_id,
                s.professor_id
             FROM seminario_datas sd
             INNER JOIN seminarios s
                ON s.id = sd.seminario_id
             WHERE sd.id = ?
             FOR UPDATE'
        );

        $consulta->execute([$dataId]);

        $sessao = $consulta->fetch();

        if (!$sessao) {

            $pdo->rollBack();

            responder_erro(
                'Sessão não encontrada.',
                404
            );
        }

        // 4. Verificar propriedade

        if (
            (int) $sessao['professor_id']
            !== $professorId
        ) {

            $pdo->rollBack();

            responder_erro(
                'Você não possui acesso a esta sessão.',
                403
            );
        }

        // 5. Verificar status da sessão

        if ($sessao['status'] !== 'agendada') {

            $pdo->rollBack();

            responder_erro(
                'Esta sessão não permite novos grupos.',
                409
            );
        }

        $disciplinaId = (int) $sessao['disciplina_id'];

        // 6. Verificar matrícula do aluno

        $consultaAluno = $pdo->prepare(
            'SELECT a.id
             FROM alunos a
             INNER JOIN disciplina_aluno da
                ON da.aluno_id = a.id
             WHERE a.id = ?
               AND da.disciplina_id = ?
               AND a.status = ?'
        );

        // 7. Verificar exclusividade do aluno

        $consultaGrupo = $pdo->prepare(
            'SELECT ga.aluno_id
             FROM grupo_alunos ga
             INNER JOIN grupos g
                ON g.id = ga.grupo_id
             WHERE ga.aluno_id = ?
               AND g.seminario_data_id = ?'
        );

        foreach ($integrantes as $alunoId) {

            $consultaAluno->execute([
                $alunoId,
                $disciplinaId,
                'ativo'
            ]);

            if (!$consultaAluno->fetchColumn()) {

                $pdo->rollBack();

                responder_erro(
                    "O aluno {$alunoId} não está ativo " .
                    "ou matriculado nesta disciplina.",
                    400
                );
            }

            $consultaGrupo->execute([
                $alunoId,
                $dataId
            ]);

            if ($consultaGrupo->fetchColumn()) {

                $pdo->rollBack();

                responder_erro(
                    "O aluno {$alunoId} já pertence " .
                    "a outro grupo desta sessão.",
                    400
                );
            }
        }

        // 8. Cadastrar grupo

        $consulta = $pdo->prepare(
            'INSERT INTO grupos (
                seminario_data_id,
                tema,
                ordem_apresentacao,
                status
            ) VALUES (?, ?, NULL, ?)'
        );

        $consulta->execute([
            $dataId,
            $tema,
            'pendente'
        ]);

        $grupoId = (int) $pdo->lastInsertId();

        $consulta = $pdo->prepare(
            'INSERT INTO grupo_alunos (
                grupo_id,
                aluno_id
            ) VALUES (?, ?)'
        );

        foreach ($integrantes as $alunoId) {

            $consulta->execute([
                $grupoId,
                $alunoId
            ]);
        }

        // 9. Atualizar status do seminário

        $consulta = $pdo->prepare(
            'UPDATE seminarios s
             INNER JOIN seminario_datas sd
                ON sd.seminario_id = s.id
             SET s.status = ?
             WHERE sd.id = ?
               AND s.status = ?'
        );

        $consulta->execute([
            'agendado',
            $dataId,
            'pendente'
        ]);

        // 10. Confirmar alterações

        $pdo->commit();

        return [
            'id' => $grupoId,
            'nome' => "Grupo {$grupoId}",
            'tema' => $tema,
            'integrantesIds' => $integrantes,
            'ordem' => null,
            'status' => 'pendente'
        ];

    } catch (Throwable $erro) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        throw $erro;
    }
}
function sincronizar_grupos(
    PDO $pdo,
    int $professorId,
    int $dataId,
    array $dados
): array {

    // 1. Validar lista de grupos

    $grupos = $dados['grupos'] ?? null;

    if (!is_array($grupos) || !array_is_list($grupos)) {
        responder_erro('Lista de grupos inválida.', 400);
    }

    $gruposValidados = [];
    $idsInformados = [];
    $alunosUtilizados = [];

    foreach ($grupos as $grupo) {

        if (!is_array($grupo)) {
            responder_erro('Grupo inválido.', 400);
        }

        $grupoId = $grupo['id'] ?? null;

        if ($grupoId !== null) {

            validar_inteiro_positivo($grupoId, 'id do grupo');

            if (in_array($grupoId, $idsInformados, true)) {
                responder_erro('Grupo duplicado.', 400);
            }

            $idsInformados[] = $grupoId;
        }

        $tema = validar_texto(
            $grupo['tema'] ?? null,
            'tema',
            200
        );

        $integrantes = $grupo['integrantesIds'] ?? null;

        if (
            !is_array($integrantes)
            || !array_is_list($integrantes)
        ) {
            responder_erro('Integrantes inválidos.', 400);
        }

        foreach ($integrantes as $alunoId) {

            validar_inteiro_positivo($alunoId, 'alunoId');

            if (isset($alunosUtilizados[$alunoId])) {

                responder_erro(
                    "O aluno {$alunoId} está duplicado.",
                    400
                );
            }

            $alunosUtilizados[$alunoId] = true;
        }

        $gruposValidados[] = [
            'id' => $grupoId,
            'tema' => $tema,
            'integrantesIds' => $integrantes
        ];
    }

    // 2. Iniciar transação

    $pdo->beginTransaction();

    try {

        // Buscar sessão e verificar propriedade

        $consulta = $pdo->prepare(
            'SELECT
                sd.id,
                sd.status,
                sd.seminario_id,
                s.professor_id,
                s.disciplina_id
             FROM seminario_datas sd
             INNER JOIN seminarios s
                ON s.id = sd.seminario_id
             WHERE sd.id = ?
             FOR UPDATE'
        );

        $consulta->execute([$dataId]);

        $sessao = $consulta->fetch();

        if (!$sessao) {

            $pdo->rollBack();

            responder_erro('Sessão não encontrada.', 404);
        }

        if ((int) $sessao['professor_id'] !== $professorId) {

            $pdo->rollBack();

            responder_erro('Acesso negado à sessão.', 403);
        }

        if ($sessao['status'] !== 'agendada') {

            $pdo->rollBack();

            responder_erro(
                'Esta sessão não permite alterações.',
                409
            );
        }

        // 3. Buscar grupos existentes

        $consulta = $pdo->prepare(
            'SELECT id, tema, ordem_apresentacao, status
             FROM grupos
             WHERE seminario_data_id = ?
             FOR UPDATE'
        );

        $consulta->execute([$dataId]);

        $gruposExistentes = [];

        foreach ($consulta->fetchAll() as $grupo) {
            $gruposExistentes[(int) $grupo['id']] = $grupo;
        }

        // Verificar IDs enviados

        foreach ($idsInformados as $grupoId) {

            if (!isset($gruposExistentes[$grupoId])) {

                $pdo->rollBack();

                responder_erro(
                    "O grupo {$grupoId} não pertence à sessão.",
                    400
                );
            }
        }

        // 4. Verificar alunos matriculados

        $consultaAluno = $pdo->prepare(
            'SELECT a.id
             FROM alunos a
             INNER JOIN disciplina_aluno da
                ON da.aluno_id = a.id
             WHERE a.id = ?
               AND da.disciplina_id = ?
               AND a.status = ?'
        );

        foreach (array_keys($alunosUtilizados) as $alunoId) {

            $consultaAluno->execute([
                $alunoId,
                $sessao['disciplina_id'],
                'ativo'
            ]);

            if (!$consultaAluno->fetchColumn()) {

                $pdo->rollBack();

                responder_erro(
                    "O aluno {$alunoId} não está ativo " .
                    "ou matriculado na disciplina.",
                    400
                );
            }
        }

        // 5. Verificar histórico de avaliações

        $consultaHistorico = $pdo->prepare(
            'SELECT (
                EXISTS (
                    SELECT 1
                    FROM avaliacoes_notas
                    WHERE grupo_id = ?
                )
                OR EXISTS (
                    SELECT 1
                    FROM avaliacoes_feedbacks
                    WHERE grupo_id = ?
                )
            ) AS possui_historico'
        );

        foreach ($gruposExistentes as $grupoId => $grupo) {

            $consultaHistorico->execute([
                $grupoId,
                $grupoId
            ]);

            if (
                (int) $consultaHistorico->fetchColumn() === 1
                || $grupo['status'] !== 'pendente'
            ) {

                $pdo->rollBack();

                responder_erro(
                    "O grupo {$grupoId} possui histórico " .
                    "ou não permite alterações.",
                    409
                );
            }
        }

        // 6. Remover vínculos antigos da sessão

        $consulta = $pdo->prepare(
            'DELETE ga
             FROM grupo_alunos ga
             INNER JOIN grupos g
                ON g.id = ga.grupo_id
             WHERE g.seminario_data_id = ?'
        );

        $consulta->execute([$dataId]);

        // 7. Excluir grupos que não foram enviados

        $excluirGrupo = $pdo->prepare(
            'DELETE FROM grupos
             WHERE id = ?
               AND seminario_data_id = ?'
        );

        foreach ($gruposExistentes as $grupoId => $grupo) {

            if (!in_array($grupoId, $idsInformados, true)) {

                $excluirGrupo->execute([
                    $grupoId,
                    $dataId
                ]);
            }
        }

        // 8. Preparar inserções e atualizações

        $inserirGrupo = $pdo->prepare(
            'INSERT INTO grupos (
                seminario_data_id,
                tema,
                ordem_apresentacao,
                status
            ) VALUES (?, ?, NULL, ?)'
        );

        $atualizarGrupo = $pdo->prepare(
            'UPDATE grupos
             SET tema = ?
             WHERE id = ?
               AND seminario_data_id = ?'
        );

        $vincularAluno = $pdo->prepare(
            'INSERT INTO grupo_alunos (
                grupo_id,
                aluno_id
            ) VALUES (?, ?)'
        );

        $resultado = [];

        // 9. Salvar grupos e integrantes

        foreach ($gruposValidados as $grupo) {

            $grupoId = $grupo['id'];

            if ($grupoId === null) {

                $inserirGrupo->execute([
                    $dataId,
                    $grupo['tema'],
                    'pendente'
                ]);

                $grupoId = (int) $pdo->lastInsertId();

                $ordem = null;
                $status = 'pendente';

            } else {

                $atualizarGrupo->execute([
                    $grupo['tema'],
                    $grupoId,
                    $dataId
                ]);

                $existente = $gruposExistentes[$grupoId];

                $ordem = $existente['ordem_apresentacao'] !== null
                    ? (int) $existente['ordem_apresentacao']
                    : null;

                $status = $existente['status'];
            }

            foreach ($grupo['integrantesIds'] as $alunoId) {

                $vincularAluno->execute([
                    $grupoId,
                    $alunoId
                ]);
            }

            $resultado[] = [
                'id' => $grupoId,
                'nome' => 'Grupo ' . ($ordem ?? $grupoId),
                'tema' => $grupo['tema'],
                'integrantesIds' => $grupo['integrantesIds'],
                'ordem' => $ordem,
                'status' => $status
            ];
        }

        // 10. Atualizar status do seminário

        if (count($resultado) > 0) {

            $consulta = $pdo->prepare(
                'UPDATE seminarios
                 SET status = ?
                 WHERE id = ?
                AND status = ?'
            );

            $consulta->execute([
                'agendado',
                $sessao['seminario_id'],
                'pendente'
            ]);
        }

        $pdo->commit();

        return $resultado;

    } catch (Throwable $erro) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        throw $erro;
    }
}

