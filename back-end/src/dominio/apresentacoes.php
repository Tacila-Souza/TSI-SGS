<?php

declare(strict_types=1);

require_once __DIR__ . '/presencas.php';

function carregar_apresentacao(
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

    // 2. Buscar os grupos da sessão

    $consultaGrupos = $pdo->prepare(
        'SELECT
            id,
            seminario_data_id,
            tema,
            ordem_apresentacao,
            status
         FROM grupos
         WHERE seminario_data_id = ?
         ORDER BY ordem_apresentacao ASC, id ASC'
    );

    $consultaGrupos->execute([$dataId]);

    $linhasGrupos = $consultaGrupos->fetchAll();

    // 3. Buscar os integrantes de todos os grupos

    $consultaIntegrantes = $pdo->prepare(
        'SELECT ga.grupo_id, ga.aluno_id
         FROM grupo_alunos ga
         INNER JOIN grupos g
            ON g.id = ga.grupo_id
         WHERE g.seminario_data_id = ?
         ORDER BY ga.aluno_id ASC'
    );

    $consultaIntegrantes->execute([$dataId]);

    $integrantes = [];

    foreach ($consultaIntegrantes->fetchAll() as $linha) {
        $integrantes[(int) $linha['grupo_id']][] =
            (int) $linha['aluno_id'];
    }

    // 4. Buscar as notas já salvas pelo professor

    $consultaNotas = $pdo->prepare(
        'SELECT
            grupo_id,
            criterio_id,
            aluno_avaliado_id,
            nota
         FROM avaliacoes_notas
         WHERE seminario_data_id = ?
           AND tipo_avaliador = ?
           AND avaliador_id = ?
         ORDER BY criterio_id ASC, aluno_avaliado_id ASC'
    );

    $consultaNotas->execute([
        $dataId,
        'professor',
        $professorId
    ]);

    $notas = [];

    foreach ($consultaNotas->fetchAll() as $linha) {

        $notas[(int) $linha['grupo_id']][] = [
            'criterioId' => (int) $linha['criterio_id'],
            'alunoId' => $linha['aluno_avaliado_id'] === null
                ? null
                : (int) $linha['aluno_avaliado_id'],
            'nota' => (float) $linha['nota']
        ];
    }

    // 5. Buscar os feedbacks já salvos pelo professor

    $consultaFeedbacks = $pdo->prepare(
        'SELECT grupo_id, feedback_texto
         FROM avaliacoes_feedbacks
         WHERE seminario_data_id = ?
           AND tipo_avaliador = ?
           AND avaliador_id = ?'
    );

    $consultaFeedbacks->execute([
        $dataId,
        'professor',
        $professorId
    ]);

    $feedbacks = [];

    foreach ($consultaFeedbacks->fetchAll() as $linha) {
        $feedbacks[(int) $linha['grupo_id']] =
            $linha['feedback_texto'];
    }

    // 6. Montar a resposta no formato do contrato

    $grupos = [];
    $avaliados = 0;

    foreach ($linhasGrupos as $linha) {

        $grupoId = (int) $linha['id'];
        $notasDoGrupo = $notas[$grupoId] ?? [];
        $avaliado = count($notasDoGrupo) > 0;

        if ($avaliado) {
            $avaliados++;
        }

        $grupos[] = [
            'id' => $grupoId,
            'seminario_data_id' => (int) $linha['seminario_data_id'],
            'tema' => $linha['tema'],
            'ordem_apresentacao' => $linha['ordem_apresentacao'] === null
                ? null
                : (int) $linha['ordem_apresentacao'],
            'status' => $linha['status'],
            'integrantes_ids' => $integrantes[$grupoId] ?? [],
            'avaliado' => $avaliado,
            'notas' => $notasDoGrupo,
            'feedback' => $feedbacks[$grupoId] ?? ''
        ];
    }

    return [
        'seminarioId' => (int) $sessao['seminario_id'],
        'dataId' => $dataId,
        'dataSessao' => $sessao['data_apresentacao'],
        'status' => $sessao['status'],
        'grupos' => $grupos,
        'gruposAvaliados' => $avaliados,
        'totalGrupos' => count($grupos)
    ];
}

function iniciar_apresentacao(
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

    // 2. Se já está em andamento, só devolve os dados

    if ($sessao['status'] === 'em_andamento') {
        return carregar_apresentacao($pdo, $professorId, $dataId);
    }

    if ($sessao['status'] !== 'agendada') {
        responder_erro(
            'Esta sessão não pode ser iniciada.',
            409
        );
    }

    // 3. Precisa ter pelo menos um grupo

    $consultaGrupos = $pdo->prepare(
        'SELECT COUNT(*) FROM grupos WHERE seminario_data_id = ?'
    );

    $consultaGrupos->execute([$dataId]);

    if ((int) $consultaGrupos->fetchColumn() === 0) {
        responder_erro(
            'Cadastre ao menos um grupo antes de iniciar.',
            409
        );
    }

    // 4. A chamada precisa ter sido registrada

    $consultaPresentes = $pdo->prepare(
        'SELECT COUNT(*)
         FROM presencas
         WHERE seminario_data_id = ?
           AND presente = 1'
    );

    $consultaPresentes->execute([$dataId]);

    if ((int) $consultaPresentes->fetchColumn() === 0) {
        responder_erro(
            'Registre a chamada antes de iniciar a apresentação.',
            409
        );
    }

    // 5. Mudar o status da sessão

    $atualizar = $pdo->prepare(
        'UPDATE seminario_datas
         SET status = ?
         WHERE id = ?
           AND status = ?'
    );

    $atualizar->execute(['em_andamento', $dataId, 'agendada']);

    // 6. Devolver a tela ao vivo atualizada

    return carregar_apresentacao($pdo, $professorId, $dataId);
}