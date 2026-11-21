<?php
declare(strict_types=1);

function carregar_apresentacao(PDO $pdo, int $professorId, int $dataId): array
{
    $data = validar_propriedade_data($pdo, $dataId, $professorId);
    $seminario = validar_propriedade_seminario($pdo, (int) $data['seminario_id'], $professorId);

    $grupos = carregar_grupos_sessao($pdo, $dataId);
    $gruposAvaliados = 0;
    foreach ($grupos as &$grupo) {
        $grupoId = (int) $grupo['id'];

        $stmtNotas = $pdo->prepare(
            "SELECT criterio_id, aluno_avaliado_id, nota
             FROM avaliacoes_notas
             WHERE seminario_data_id = ? AND grupo_id = ?
               AND tipo_avaliador = 'professor' AND avaliador_id = ?
             ORDER BY criterio_id, aluno_avaliado_id"
        );
        $stmtNotas->execute([$dataId, $grupoId, $professorId]);
        $notas = [];
        foreach ($stmtNotas->fetchAll() as $nota) {
            $notas[] = [
                'criterioId' => (int) $nota['criterio_id'],
                'alunoId' => isset($nota['aluno_avaliado_id']) ? (int) $nota['aluno_avaliado_id'] : null,
                'nota' => (float) $nota['nota'],
            ];
        }

        $stmtFeedback = $pdo->prepare(
            "SELECT feedback_texto FROM avaliacoes_feedbacks
             WHERE seminario_data_id = ? AND grupo_id = ?
               AND tipo_avaliador = 'professor' AND avaliador_id = ?"
        );
        $stmtFeedback->execute([$dataId, $grupoId, $professorId]);
        $feedback = $stmtFeedback->fetchColumn();

        $grupo['avaliado'] = $notas !== [];
        $grupo['notas'] = $notas;
        $grupo['feedback'] = $feedback !== false ? (string) $feedback : '';
        if ($grupo['avaliado']) {
            $gruposAvaliados++;
        }
    }

    return [
        'seminarioId' => (int) $seminario['id'],
        'dataId' => $dataId,
        'dataSessao' => (string) $data['data_apresentacao'],
        'status' => (string) $data['status'],
        'grupos' => $grupos,
        'gruposAvaliados' => $gruposAvaliados,
        'totalGrupos' => count($grupos),
    ];
}

function iniciar_apresentacao(PDO $pdo, int $professorId, int $dataId): array
{
    $data = validar_propriedade_data($pdo, $dataId, $professorId);

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("UPDATE seminario_datas SET status = 'em_andamento' WHERE id = ?");
        $stmt->execute([$dataId]);

        $stmt = $pdo->prepare("UPDATE seminarios SET status = 'em_andamento' WHERE id = ? AND status = 'pendente'");
        $stmt->execute([(int) $data['seminario_id']]);

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }

    return carregar_apresentacao($pdo, $professorId, $dataId);
}

function encerrar_apresentacao(PDO $pdo, int $professorId, int $dataId): array
{
    validar_propriedade_data($pdo, $dataId, $professorId);

    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM grupos
         WHERE seminario_data_id = ? AND status <> 'avaliado'"
    );
    $stmt->execute([$dataId]);
    $pendentes = (int) $stmt->fetchColumn();

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("UPDATE seminario_datas SET status = 'concluida' WHERE id = ?");
        $stmt->execute([$dataId]);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }

    return [
        'dataId' => $dataId,
        'status' => 'finalizado',
        'gruposPendentes' => $pendentes,
    ];
}

function salvar_avaliacao_aluno(
    PDO $pdo,
    int $alunoId,
    int $dataId,
    int $grupoId,
    array $dados
): array {
    $stmt = $pdo->prepare(
        'SELECT sd.*, s.id AS seminario_id
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

    $stmtGrupo = $pdo->prepare('SELECT * FROM grupos WHERE id = ?');
    $stmtGrupo->execute([$grupoId]);
    $grupo = $stmtGrupo->fetch();
    if ($grupo === false) {
        responder_erro('Grupo não encontrado.', 404);
    }
    if ((int) $grupo['seminario_data_id'] !== $dataId) {
        responder_erro('Grupo não pertence a esta sessão.', 400);
    }

    $stmt = $pdo->prepare('SELECT aluno_id FROM grupo_alunos WHERE grupo_id = ?');
    $stmt->execute([$grupoId]);
    $integrantes = array_map('intval', array_column($stmt->fetchAll(), 'aluno_id'));
    if (in_array($alunoId, $integrantes, true)) {
        responder_erro('Você não pode avaliar o próprio grupo.', 403);
    }

    $stmt = $pdo->prepare('SELECT presente FROM presencas WHERE seminario_data_id = ? AND aluno_id = ?');
    $stmt->execute([$dataId, $alunoId]);
    $presente = $stmt->fetchColumn();
    if ($presente === false || (int) $presente !== 1) {
        responder_erro('Somente alunos presentes na chamada podem avaliar.', 403);
    }

    $notas = validar_notas_avaliacao($pdo, (int) $data['seminario_id'], campo_lista($dados, 'notas'), $integrantes);
    $feedback = isset($dados['feedback']) ? trim((string) $dados['feedback']) : '';

    $pdo->beginTransaction();
    try {
        $stmtNota = $pdo->prepare(
            'INSERT INTO avaliacoes_notas
             (seminario_data_id, grupo_id, criterio_id, avaliador_id, tipo_avaliador, aluno_avaliado_id, nota)
             VALUES (?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE nota = VALUES(nota)'
        );
        foreach ($notas as $nota) {
            $stmtNota->execute([
                $dataId,
                $grupoId,
                $nota['criterioId'],
                $alunoId,
                'aluno',
                $nota['alunoId'],
                $nota['nota'],
            ]);
        }

        $stmtFeedback = $pdo->prepare(
            'INSERT INTO avaliacoes_feedbacks
             (seminario_data_id, grupo_id, avaliador_id, tipo_avaliador, feedback_texto)
             VALUES (?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE feedback_texto = VALUES(feedback_texto)'
        );
        $stmtFeedback->execute([$dataId, $grupoId, $alunoId, 'aluno', $feedback]);

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }

    return formulario_avaliacao_aluno($pdo, $alunoId, $dataId);
}

function validar_notas_avaliacao(PDO $pdo, int $seminarioId, array $notas, array $integrantes): array
{
    if ($notas === []) {
        throw new HttpErro('Informe ao menos uma nota.', 400);
    }

    $stmt = $pdo->prepare('SELECT id, tipo_avaliacao FROM seminario_criterios WHERE seminario_id = ?');
    $stmt->execute([$seminarioId]);
    $criterios = [];
    foreach ($stmt->fetchAll() as $criterio) {
        $criterios[(int) $criterio['id']] = $criterio['tipo_avaliacao'];
    }

    $validas = [];
    foreach ($notas as $nota) {
        if (!is_array($nota)) {
            throw new HttpErro('Cada nota deve ser um objeto.', 400);
        }
        $criterioId = campo_int($nota, 'criterioId');
        if (!isset($criterios[$criterioId])) {
            throw new HttpErro('Critério informado não pertence a este seminário.', 400);
        }
        $valor = filter_var($nota['nota'] ?? null, FILTER_VALIDATE_FLOAT);
        if ($valor === false || $valor < 0 || $valor > 10) {
            throw new HttpErro('Nota deve estar entre 0 e 10.', 400);
        }
        $alunoId = isset($nota['alunoId']) && $nota['alunoId'] !== '' ? (int) $nota['alunoId'] : null;
        if ($criterios[$criterioId] === 'individual' && !in_array($alunoId, $integrantes, true)) {
            throw new HttpErro('Nota individual deve referenciar um integrante do grupo.', 400);
        }
        if ($criterios[$criterioId] === 'grupo') {
            $alunoId = null;
        }
        $validas[] = [
            'criterioId' => $criterioId,
            'alunoId' => $alunoId,
            'nota' => $valor,
        ];
    }

    return $validas;
}

function gerar_mock_avaliacoes(PDO $pdo, int $dataId, int $grupoId, int $seminarioId, array $integrantes): void
{
    $stmt = $pdo->prepare(
        'SELECT a.id FROM alunos a
         INNER JOIN presencas p ON p.aluno_id = a.id
         WHERE p.seminario_data_id = ? AND p.presente = 1 AND a.status = ?'
    );
    $stmt->execute([$dataId, 'ativo']);
    $presentes = array_map('intval', array_column($stmt->fetchAll(), 'id'));
    $elegiveis = array_values(array_diff($presentes, $integrantes));

    $stmtCriterios = $pdo->prepare('SELECT id, tipo_avaliacao FROM seminario_criterios WHERE seminario_id = ?');
    $stmtCriterios->execute([$seminarioId]);
    $criterios = $stmtCriterios->fetchAll();

    $stmtExiste = $pdo->prepare(
        'SELECT COUNT(*) FROM avaliacoes_notas
         WHERE seminario_data_id = ? AND grupo_id = ? AND criterio_id = ?
           AND tipo_avaliador = ? AND avaliador_id = ?
           AND COALESCE(aluno_avaliado_id, 0) = ?'
    );
    $stmtNota = $pdo->prepare(
        'INSERT INTO avaliacoes_notas
         (seminario_data_id, grupo_id, criterio_id, avaliador_id, tipo_avaliador, aluno_avaliado_id, nota)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );

    foreach ($elegiveis as $avaliadorId) {
        foreach ($criterios as $criterio) {
            $criterioId = (int) $criterio['id'];
            $alunosAlvo = $criterio['tipo_avaliacao'] === 'individual' ? $integrantes : [null];
            foreach ($alunosAlvo as $alunoAlvo) {
                $stmtExiste->execute([
                    $dataId,
                    $grupoId,
                    $criterioId,
                    'aluno',
                    $avaliadorId,
                    $alunoAlvo ?? 0,
                ]);
                if ((int) $stmtExiste->fetchColumn() > 0) {
                    continue;
                }
                $nota = mt_rand(700, 1000) / 100;
                $stmtNota->execute([
                    $dataId,
                    $grupoId,
                    $criterioId,
                    $avaliadorId,
                    'aluno',
                    $alunoAlvo,
                    $nota,
                ]);
            }
        }
    }
}

function salvar_avaliacao_professor(
    PDO $pdo,
    int $professorId,
    int $dataId,
    int $grupoId,
    array $dados
): array {
    $data = validar_propriedade_data($pdo, $dataId, $professorId);
    $grupo = validar_propriedade_grupo($pdo, $grupoId, $professorId);
    if ((int) $grupo['seminario_data_id'] !== $dataId) {
        responder_erro('Grupo não pertence a esta sessão.', 400);
    }

    $stmtIntegrantes = $pdo->prepare('SELECT aluno_id FROM grupo_alunos WHERE grupo_id = ?');
    $stmtIntegrantes->execute([$grupoId]);
    $integrantes = array_map('intval', array_column($stmtIntegrantes->fetchAll(), 'aluno_id'));

    $notas = validar_notas_avaliacao($pdo, (int) $data['seminario_id'], campo_lista($dados, 'notas'), $integrantes);
    $feedback = isset($dados['feedback']) ? trim((string) $dados['feedback']) : '';

    $pdo->beginTransaction();
    try {
        $stmtNota = $pdo->prepare(
            'INSERT INTO avaliacoes_notas
             (seminario_data_id, grupo_id, criterio_id, avaliador_id, tipo_avaliador, aluno_avaliado_id, nota)
             VALUES (?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE nota = VALUES(nota)'
        );
        foreach ($notas as $nota) {
            $stmtNota->execute([
                $dataId,
                $grupoId,
                $nota['criterioId'],
                $professorId,
                'professor',
                $nota['alunoId'],
                $nota['nota'],
            ]);
        }

        $stmtFeedback = $pdo->prepare(
            'INSERT INTO avaliacoes_feedbacks
             (seminario_data_id, grupo_id, avaliador_id, tipo_avaliador, feedback_texto)
             VALUES (?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE feedback_texto = VALUES(feedback_texto)'
        );
        $stmtFeedback->execute([$dataId, $grupoId, $professorId, 'professor', $feedback]);

        gerar_mock_avaliacoes($pdo, $dataId, $grupoId, (int) $data['seminario_id'], $integrantes);

        $stmtStatus = $pdo->prepare("UPDATE grupos SET status = 'avaliado' WHERE id = ?");
        $stmtStatus->execute([$grupoId]);

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }

    return carregar_apresentacao($pdo, $professorId, $dataId);
}
