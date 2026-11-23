<?php
declare(strict_types=1);

function validar_matricula_seminario(PDO $pdo, int $alunoId, int $seminarioId): array
{
    $stmt = $pdo->prepare(
        'SELECT s.* FROM seminarios s
         INNER JOIN disciplina_aluno da ON da.disciplina_id = s.disciplina_id
         WHERE s.id = ? AND da.aluno_id = ?'
    );
    $stmt->execute([$seminarioId, $alunoId]);
    $seminario = $stmt->fetch();
    if ($seminario === false) {
        responder_erro('Este seminário não está vinculado às suas disciplinas.', 403);
    }
    return $seminario;
}

function listar_seminarios_aluno(PDO $pdo, int $alunoId): array
{
    $stmt = $pdo->prepare(
        'SELECT s.* FROM seminarios s
         INNER JOIN disciplina_aluno da ON da.disciplina_id = s.disciplina_id
         WHERE da.aluno_id = ?
         ORDER BY s.id DESC'
    );
    $stmt->execute([$alunoId]);
    $seminarios = $stmt->fetchAll();

    foreach ($seminarios as &$seminario) {
        carregar_detalhes_seminario($pdo, $seminario);
    }
    return $seminarios;
}

function buscar_seminario(PDO $pdo, int $seminarioId): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM seminarios WHERE id = ?');
    $stmt->execute([$seminarioId]);
    $seminario = $stmt->fetch();
    if ($seminario === false) {
        return null;
    }

    carregar_detalhes_seminario($pdo, $seminario);
    return $seminario;
}

function listar_datas_aluno(PDO $pdo, int $alunoId, int $seminarioId): array
{
    validar_matricula_seminario($pdo, $alunoId, $seminarioId);
    $stmt = $pdo->prepare(
        'SELECT sd.* FROM seminario_datas sd WHERE sd.seminario_id = ? ORDER BY data_apresentacao, horario, id'
    );
    $stmt->execute([$seminarioId]);
    return $stmt->fetchAll();
}

function formulario_avaliacao_aluno(PDO $pdo, int $alunoId, int $dataId): array
{
    $stmt = $pdo->prepare(
        'SELECT sd.*, s.id AS seminario_id, s.disciplina_id
         FROM seminario_datas sd
         INNER JOIN seminarios s ON s.id = sd.seminario_id
         WHERE sd.id = ?'
    );
    $stmt->execute([$dataId]);
    $data = $stmt->fetch();
    if ($data === false) {
        responder_erro('Sessão não encontrada.', 404);
    }
    if ((string) $data['status'] !== 'em_andamento') {
        responder_erro('A avaliação está disponível apenas durante a sessão em andamento.', 400);
    }
    validar_matricula_seminario($pdo, $alunoId, (int) $data['seminario_id']);

    $stmt = $pdo->prepare('SELECT presente FROM presencas WHERE seminario_data_id = ? AND aluno_id = ?');
    $stmt->execute([$dataId, $alunoId]);
    $presente = $stmt->fetchColumn();
    if ($presente === false || (int) $presente !== 1) {
        responder_erro('Aluno não consta na chamada desta sessão.', 403);
    }

    $stmt = $pdo->prepare('SELECT id, nome_criterio, peso_criterio, tipo_avaliacao FROM seminario_criterios WHERE seminario_id = ? ORDER BY id');
    $stmt->execute([(int) $data['seminario_id']]);
    $criterios = $stmt->fetchAll();

    $grupos = carregar_grupos_sessao($pdo, $dataId);
    $grupos = array_values(array_filter($grupos, static function (array $grupo) use ($alunoId): bool {
        return !in_array($alunoId, $grupo['integrantes_ids'], true)
            && ($grupo['status'] ?? 'pendente') !== 'avaliado'
            && !empty($grupo['integrantes_ids']);
    }));

    $stmtAvaliacoes = $pdo->prepare(
        'SELECT n.grupo_id, n.criterio_id, n.aluno_avaliado_id, n.nota, f.feedback_texto
         FROM avaliacoes_notas n
         LEFT JOIN avaliacoes_feedbacks f
           ON f.seminario_data_id = n.seminario_data_id
          AND f.grupo_id = n.grupo_id
          AND f.avaliador_id = n.avaliador_id
          AND f.tipo_avaliador = n.tipo_avaliador
         WHERE n.seminario_data_id = ? AND n.avaliador_id = ? AND n.tipo_avaliador = ?
         ORDER BY n.grupo_id, n.criterio_id, n.aluno_avaliado_id'
    );
    $stmtAvaliacoes->execute([$dataId, $alunoId, 'aluno']);
    $minhasAvaliacoes = [];
    foreach ($stmtAvaliacoes->fetchAll() as $linha) {
        $grupoId = (int) $linha['grupo_id'];
        if (!isset($minhasAvaliacoes[$grupoId])) {
            $minhasAvaliacoes[$grupoId] = [
                'grupoId' => $grupoId,
                'notas' => [],
                'feedback' => (string) ($linha['feedback_texto'] ?? ''),
            ];
        }
        $minhasAvaliacoes[$grupoId]['notas'][] = [
            'criterioId' => (int) $linha['criterio_id'],
            'alunoId' => isset($linha['aluno_avaliado_id']) ? (int) $linha['aluno_avaliado_id'] : null,
            'nota' => (float) $linha['nota'],
        ];
    }

    return [
        'seminarioId' => (int) $data['seminario_id'],
        'dataId' => $dataId,
        'dataSessao' => (string) $data['data_apresentacao'],
        'criterios' => array_map('serializar_criterio', $criterios),
        'grupos' => array_map('serializar_grupo', $grupos),
        'minhasAvaliacoes' => array_values($minhasAvaliacoes),
    ];
}
