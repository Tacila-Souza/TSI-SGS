<?php

declare(strict_types=1);

function exigir_aluno_elegivel(
    PDO $pdo,
    int $alunoId,
    int $dataId
): array {

    // 1. Buscar a sessão

    $consulta = $pdo->prepare(
        'SELECT
            sd.id,
            sd.status,
            sd.data_apresentacao,
            s.id AS seminario_id,
            s.disciplina_id
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

    // 2. A sessão precisa estar em andamento

    if ($sessao['status'] !== 'em_andamento') {
        responder_erro(
            'Esta sessão não está aberta para avaliação.',
            409
        );
    }

    // 3. O aluno precisa estar matriculado na disciplina

    $consulta = $pdo->prepare(
        'SELECT COUNT(*)
         FROM disciplina_aluno
         WHERE disciplina_id = ?
           AND aluno_id = ?'
    );

    $consulta->execute([
        (int) $sessao['disciplina_id'],
        $alunoId
    ]);

    if ((int) $consulta->fetchColumn() === 0) {
        responder_erro(
            'Você não está matriculado nesta disciplina.',
            403
        );
    }

    // 4. O aluno precisa estar presente na sessão

    $consulta = $pdo->prepare(
        'SELECT COUNT(*)
         FROM presencas
         WHERE seminario_data_id = ?
           AND aluno_id = ?
           AND presente = 1'
    );

    $consulta->execute([$dataId, $alunoId]);

    if ((int) $consulta->fetchColumn() === 0) {
        responder_erro(
            'Você não está presente nesta sessão.',
            403
        );
    }

    return $sessao;
}

function exigir_grupo_avaliavel(
    PDO $pdo,
    int $alunoId,
    int $dataId,
    int $grupoId
): void {

    // 1. O grupo precisa ser desta sessão

    $consulta = $pdo->prepare(
        'SELECT COUNT(*)
         FROM grupos
         WHERE id = ?
           AND seminario_data_id = ?'
    );

    $consulta->execute([$grupoId, $dataId]);

    if ((int) $consulta->fetchColumn() === 0) {
        responder_erro(
            'Grupo não encontrado nesta sessão.',
            404
        );
    }

    // 2. O aluno não pode avaliar o próprio grupo

    $consulta = $pdo->prepare(
        'SELECT COUNT(*)
         FROM grupo_alunos
         WHERE grupo_id = ?
           AND aluno_id = ?'
    );

    $consulta->execute([$grupoId, $alunoId]);

    if ((int) $consulta->fetchColumn() > 0) {
        responder_erro(
            'Você não pode avaliar o próprio grupo.',
            403
        );
    }
}


function carregar_formulario_aluno(
    PDO $pdo,
    int $alunoId,
    int $dataId
): array {

    $sessao = exigir_aluno_elegivel($pdo, $alunoId, $dataId);

    // 1. Critérios do seminário

    $consulta = $pdo->prepare(
        'SELECT id, nome_criterio, peso_criterio, tipo_avaliacao
         FROM seminario_criterios
         WHERE seminario_id = ?
         ORDER BY id'
    );

    $consulta->execute([(int) $sessao['seminario_id']]);

    $criterios = [];

    foreach ($consulta->fetchAll() as $linha) {

        $criterios[] = [
            'id' => (int) $linha['id'],
            'nome' => $linha['nome_criterio'],
            'peso' => (int) $linha['peso_criterio'],
            'tipo' => $linha['tipo_avaliacao']
        ];
    }

    // 2. Grupos que este aluno pode avaliar

    $consultaGrupos = $pdo->prepare(
        'SELECT g.id, g.tema, g.ordem_apresentacao, g.status
         FROM grupos g
         WHERE g.seminario_data_id = ?
           AND g.status <> ?
           AND EXISTS (
                SELECT 1 FROM grupo_alunos ga
                WHERE ga.grupo_id = g.id
           )
           AND NOT EXISTS (
                SELECT 1 FROM grupo_alunos gb
                WHERE gb.grupo_id = g.id
                  AND gb.aluno_id = ?
           )
         ORDER BY g.ordem_apresentacao IS NULL,
                  g.ordem_apresentacao, g.id'
    );

    $consultaGrupos->execute([$dataId, 'avaliado', $alunoId]);

    $consultaIntegrantes = $pdo->prepare(
        'SELECT aluno_id
         FROM grupo_alunos
         WHERE grupo_id = ?
         ORDER BY aluno_id'
    );

    $grupos = [];

    foreach ($consultaGrupos->fetchAll() as $linha) {

        $grupoId = (int) $linha['id'];

        $consultaIntegrantes->execute([$grupoId]);

        $integrantesIds = array_map(
            'intval',
            $consultaIntegrantes->fetchAll(PDO::FETCH_COLUMN)
        );

        $ordem = $linha['ordem_apresentacao'] !== null
            ? (int) $linha['ordem_apresentacao']
            : null;

        $grupos[] = [
            'id' => $grupoId,
            'nome' => 'Grupo ' . ($ordem ?? $grupoId),
            'tema' => $linha['tema'],
            'integrantesIds' => $integrantesIds,
            'ordem' => $ordem,
            'status' => $linha['status']
        ];
    }

    // 3. Avaliações que este aluno já salvou

    $consulta = $pdo->prepare(
        'SELECT grupo_id, criterio_id, aluno_avaliado_id, nota
         FROM avaliacoes_notas
         WHERE seminario_data_id = ?
           AND tipo_avaliador = ?
           AND avaliador_id = ?
         ORDER BY grupo_id, criterio_id, aluno_avaliado_id'
    );

    $consulta->execute([$dataId, 'aluno', $alunoId]);

    $minhas = [];

    foreach ($consulta->fetchAll() as $linha) {

        $minhas[(int) $linha['grupo_id']]['notas'][] = [
            'criterioId' => (int) $linha['criterio_id'],
            'alunoId' => $linha['aluno_avaliado_id'] === null
                ? null
                : (int) $linha['aluno_avaliado_id'],
            'nota' => (float) $linha['nota']
        ];
    }

    $consulta = $pdo->prepare(
        'SELECT grupo_id, feedback_texto
         FROM avaliacoes_feedbacks
         WHERE seminario_data_id = ?
           AND tipo_avaliador = ?
           AND avaliador_id = ?'
    );

    $consulta->execute([$dataId, 'aluno', $alunoId]);

    foreach ($consulta->fetchAll() as $linha) {
        $minhas[(int) $linha['grupo_id']]['feedback'] =
            $linha['feedback_texto'];
    }

    $minhasAvaliacoes = [];

    foreach ($minhas as $grupoId => $dados) {

        $minhasAvaliacoes[] = [
            'grupoId' => $grupoId,
            'notas' => $dados['notas'] ?? [],
            'feedback' => $dados['feedback'] ?? ''
        ];
    }

    // 4. Montar a resposta no formato do contrato

    return [
        'seminarioId' => (int) $sessao['seminario_id'],
        'dataId' => $dataId,
        'dataSessao' => $sessao['data_apresentacao'],
        'criterios' => $criterios,
        'grupos' => $grupos,
        'minhasAvaliacoes' => $minhasAvaliacoes
    ];
}