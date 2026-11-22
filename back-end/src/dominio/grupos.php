<?php
declare(strict_types=1);

function validar_alunos_disciplina(PDO $pdo, array $alunoIds, int $disciplinaId): void
{
    if ($alunoIds === []) {
        throw new HttpErro('Informe ao menos um integrante.', 400, [
            'integrantesIds' => 'Array vazio.',
        ]);
    }

    $marcadores = implode(',', array_fill(0, count($alunoIds), '?'));
    $params = array_merge([$disciplinaId], $alunoIds);

    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM disciplina_aluno da
         INNER JOIN alunos a ON a.id = da.aluno_id
         WHERE da.disciplina_id = ? AND a.status = 'ativo'
         AND a.id IN ({$marcadores})"
    );

    $stmt->execute($params);

    if ((int) $stmt->fetchColumn() !== count($alunoIds)) {
        throw new HttpErro(
            'Um ou mais integrantes não estão matriculados nesta disciplina.',
            400,
            ['integrantesIds' => 'IDs inválidos.']
        );
    }
}

function criar_grupo(PDO $pdo, int $professorId, int $dataId, array $dados): array
{
    $data = validar_propriedade_data($pdo, $dataId, $professorId);
    $seminario = validar_propriedade_seminario(
        $pdo,
        (int) $data['seminario_id'],
        $professorId
    );

    $tema = campo_texto($dados, 'tema', 200);
    $integrantes = campo_ids($dados, 'integrantesIds');

    validar_alunos_disciplina(
        $pdo,
        $integrantes,
        (int) $seminario['disciplina_id']
    );

    $pdo->beginTransaction();

    try {
        $stmt = $pdo->prepare(
            'INSERT INTO grupos (seminario_data_id, tema, status)
             VALUES (?, ?, ?)'
        );

        $stmt->execute([$dataId, $tema, 'pendente']);
        $grupoId = (int) $pdo->lastInsertId();

        $stmtVinculo = $pdo->prepare(
            'INSERT INTO grupo_alunos (grupo_id, aluno_id)
             VALUES (?, ?)'
        );

        foreach ($integrantes as $alunoId) {
            $stmtVinculo->execute([$grupoId, $alunoId]);
        }

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }

    marcar_seminario_agendado($pdo, (int) $seminario['id']);

    return buscar_grupo($pdo, $grupoId);
}

function buscar_grupo(PDO $pdo, int $grupoId): array
{
    $stmt = $pdo->prepare(
        'SELECT * FROM grupos WHERE id = ?'
    );

    $stmt->execute([$grupoId]);
    $grupo = $stmt->fetch();

    $stmtAlunos = $pdo->prepare(
        'SELECT aluno_id FROM grupo_alunos
         WHERE grupo_id = ?
         ORDER BY aluno_id'
    );

    $stmtAlunos->execute([$grupoId]);

    $grupo['integrantes_ids'] = array_column(
        $stmtAlunos->fetchAll(),
        'aluno_id'
    );

    return $grupo;
}

function editar_grupo(PDO $pdo, int $professorId, int $grupoId, array $dados): array
{
    $grupo = validar_propriedade_grupo(
        $pdo,
        $grupoId,
        $professorId
    );

    $data = validar_propriedade_data(
        $pdo,
        (int) $grupo['seminario_data_id'],
        $professorId
    );

    $seminario = validar_propriedade_seminario(
        $pdo,
        (int) $data['seminario_id'],
        $professorId
    );

    $tema = campo_texto($dados, 'tema', 200);
    $integrantes = campo_ids($dados, 'integrantesIds');

    validar_alunos_disciplina(
        $pdo,
        $integrantes,
        (int) $seminario['disciplina_id']
    );

    $pdo->beginTransaction();

    try {
        $stmt = $pdo->prepare(
            'UPDATE grupos SET tema = ? WHERE id = ?'
        );
        $stmt->execute([$tema, $grupoId]);

        $stmt = $pdo->prepare(
            'DELETE FROM grupo_alunos WHERE grupo_id = ?'
        );
        $stmt->execute([$grupoId]);

        $stmtVinculo = $pdo->prepare(
            'INSERT INTO grupo_alunos (grupo_id, aluno_id)
             VALUES (?, ?)'
        );

        foreach ($integrantes as $alunoId) {
            $stmtVinculo->execute([$grupoId, $alunoId]);
        }

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }

    marcar_seminario_agendado($pdo, (int) $seminario['id']);

    return buscar_grupo($pdo, $grupoId);
}

function excluir_grupo(PDO $pdo, int $professorId, int $grupoId): void
{
    validar_propriedade_grupo(
        $pdo,
        $grupoId,
        $professorId
    );

    foreach (
        [
            'avaliacoes_notas' => 'grupo_id',
            'avaliacoes_feedbacks' => 'grupo_id'
        ] as $tabela => $coluna
    ) {
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) FROM {$tabela} WHERE {$coluna} = ?"
        );

        $stmt->execute([$grupoId]);

        if ((int) $stmt->fetchColumn() > 0) {
            throw new HttpErro(
                'Este grupo possui avaliações registradas e não pode ser excluído.',
                409
            );
        }
    }

    $stmt = $pdo->prepare(
        'DELETE FROM grupos WHERE id = ?'
    );

    $stmt->execute([$grupoId]);
}

/*
|--------------------------------------------------------------------------
| SORTEIO DOS GRUPOS - MODIFICADO
|--------------------------------------------------------------------------
| Agora os IDs são embaralhados antes de salvar a ordem.
|--------------------------------------------------------------------------
*/

function sortear_grupos(PDO $pdo, int $professorId, int $dataId, array $dados): array
{
    validar_propriedade_data($pdo, $dataId, $professorId);

    $ids = campo_ids($dados, 'gruposIds');

    $stmt = $pdo->prepare(
        'SELECT id FROM grupos WHERE seminario_data_id = ?'
    );

    $stmt->execute([$dataId]);

    $validos = array_map(
        'intval',
        array_column($stmt->fetchAll(), 'id')
    );

    foreach ($ids as $id) {
        if (!in_array($id, $validos, true)) {
            throw new HttpErro(
                'Um dos grupos informados não pertence a esta sessão.',
                400
            );
        }
    }

    if (
        count($ids) !== count($validos) ||
        count(array_unique($ids)) !== count($ids)
    ) {
        throw new HttpErro(
            'O sorteio deve incluir todos os grupos da sessão, sem repetições.',
            400
        );
    }

    if (count($ids) < 2) {
        throw new HttpErro(
            'É necessário ter pelo menos dois grupos para realizar o sorteio.',
            400
        );
    }

    // NOVO: embaralha aleatoriamente os grupos
    shuffle($ids);

    $pdo->beginTransaction();

    try {
        $stmt = $pdo->prepare(
            'UPDATE grupos
             SET ordem_apresentacao = ?
             WHERE id = ? AND seminario_data_id = ?'
        );

        foreach ($ids as $indice => $id) {
            $ordem = $indice + 1;

            $stmt->execute([
                $ordem,
                $id,
                $dataId
            ]);
        }

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        throw $e;
    }

    return listar_grupos_sessao(
        $pdo,
        $dataId,
        $professorId
    );
}

function sincronizar_grupos(PDO $pdo, int $professorId, int $dataId, array $dados): array
{
    $data = validar_propriedade_data(
        $pdo,
        $dataId,
        $professorId
    );

    $seminario = validar_propriedade_seminario(
        $pdo,
        (int) $data['seminario_id'],
        $professorId
    );

    $grupos = campo_lista($dados, 'grupos');

    $itens = [];
    $idsEnviados = [];

    foreach ($grupos as $grupo) {
        if (!is_array($grupo)) {
            throw new HttpErro(
                'Cada grupo deve ser um objeto.',
                400
            );
        }

        $id = isset($grupo['id'])
            ? (int) $grupo['id']
            : null;

        if ($id !== null && $id <= 0) {
            $id = null;
        }

        $itens[] = [
            'id' => $id,
            'tema' => campo_texto($grupo, 'tema', 200),
            'integrantesIds' => campo_ids($grupo, 'integrantesIds'),
        ];

        if ($id !== null) {
            $idsEnviados[] = $id;
        }
    }

    $stmtAtuais = $pdo->prepare(
        'SELECT id FROM grupos WHERE seminario_data_id = ?'
    );

    $stmtAtuais->execute([$dataId]);

    $idsAtuais = array_map(
        'intval',
        array_column($stmtAtuais->fetchAll(), 'id')
    );

    foreach ($idsEnviados as $id) {
        if (!in_array($id, $idsAtuais, true)) {
            throw new HttpErro(
                "Grupo {$id} não pertence a esta sessão.",
                400
            );
        }
    }

    foreach ($itens as $item) {
        validar_alunos_disciplina(
            $pdo,
            $item['integrantesIds'],
            (int) $seminario['disciplina_id']
        );
    }

    $pdo->beginTransaction();

    try {
        // Limpa os vínculos antigos dos alunos.
        $stmtLimpar = $pdo->prepare(
            'DELETE FROM grupo_alunos
             WHERE grupo_id IN (
                 SELECT id FROM grupos
                 WHERE seminario_data_id = ?
             )'
        );

        $stmtLimpar->execute([$dataId]);

        foreach (array_diff($idsAtuais, $idsEnviados) as $idRemover) {
            $stmtHistorico = $pdo->prepare(
                'SELECT
                    (SELECT COUNT(*) FROM avaliacoes_notas WHERE grupo_id = ?) +
                    (SELECT COUNT(*) FROM avaliacoes_feedbacks WHERE grupo_id = ?) AS total'
            );

            $stmtHistorico->execute([
                $idRemover,
                $idRemover
            ]);

            if ((int) $stmtHistorico->fetchColumn() > 0) {
                throw new HttpErro(
                    "O grupo {$idRemover} possui avaliações e não pode ser removido.",
                    409
                );
            }

            $stmtDel = $pdo->prepare(
                'DELETE FROM grupos WHERE id = ?'
            );

            $stmtDel->execute([$idRemover]);
        }

        $stmtUp = $pdo->prepare(
            'UPDATE grupos SET tema = ? WHERE id = ?'
        );

        $stmtNew = $pdo->prepare(
            'INSERT INTO grupos (seminario_data_id, tema, status)
             VALUES (?, ?, ?)'
        );

        $stmtVinculo = $pdo->prepare(
            'INSERT INTO grupo_alunos (grupo_id, aluno_id)
             VALUES (?, ?)'
        );

        foreach ($itens as $item) {
            if ($item['id'] !== null) {
                $grupoId = $item['id'];

                $stmtUp->execute([
                    $item['tema'],
                    $grupoId
                ]);
            } else {
                $stmtNew->execute([
                    $dataId,
                    $item['tema'],
                    'pendente'
                ]);

                $grupoId = (int) $pdo->lastInsertId();
            }

            foreach ($item['integrantesIds'] as $alunoId) {
                $stmtVinculo->execute([
                    $grupoId,
                    $alunoId
                ]);
            }
        }

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        throw $e;
    }

    marcar_seminario_agendado(
        $pdo,
        (int) $seminario['id']
    );

    return listar_grupos_sessao(
        $pdo,
        $dataId,
        $professorId
    );
}
