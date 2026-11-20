<?php
declare(strict_types=1);

/**
 * Salva a avaliação real de um aluno em um grupo da sessão.
 * Regras: sessão em andamento, matrícula, presença, não autoavaliar,
 * uma avaliação por grupo e persistência atômica de notas + feedback.
 */
function salvar_avaliacao_aluno(
    PDO $pdo,
    int $alunoId,
    int $dataId,
    int $grupoId,
    array $dados,
): array {
    $sessao = carregar_sessao($pdo, $dataId);
    if ($sessao === null) {
        throw new RegraNegocioException('Sessão não encontrada.', 404);
    }
    if ((string) $sessao['status'] !== 'em_andamento') {
        throw new RegraNegocioException('A avaliação só é permitida enquanto a sessão está em andamento.', 400);
    }

    $seminarioId = (int) $sessao['seminario_id'];
    if (!aluno_matriculado($pdo, $alunoId, $seminarioId)) {
        throw new RegraNegocioException('Aluno não matriculado neste seminário.', 403);
    }
    if (!aluno_presente($pdo, $alunoId, $dataId)) {
        throw new RegraNegocioException('Aluno não consta na chamada desta sessão.', 403);
    }
    if (!grupo_pertence_a_sessao($pdo, $grupoId, $dataId)) {
        throw new RegraNegocioException('Grupo não pertence a esta sessão.', 404);
    }

    $integrantes = integrantes_do_grupo($pdo, $grupoId);
    if (in_array($alunoId, $integrantes, true)) {
        throw new RegraNegocioException('Não é permitido avaliar o próprio grupo.', 403);
    }
    if (aluno_avaliou_grupo($pdo, $alunoId, $dataId, $grupoId)) {
        throw new RegraNegocioException('Você já avaliou este grupo.', 409);
    }

    $criterios = listar_criterios($pdo, $seminarioId);
    $notas = validar_notas($dados['notas'] ?? null, $criterios, $integrantes);
    $feedback = texto_opcional($dados['feedback'] ?? null, 'feedback');

    try {
        $pdo->beginTransaction();

        $sqlNota = 'INSERT INTO avaliacoes_notas
                        (seminario_data_id, grupo_id, criterio_id, avaliador_id,
                         tipo_avaliador, aluno_avaliado_id, nota)
                    VALUES (?, ?, ?, ?, "aluno", ?, ?)
                    ON DUPLICATE KEY UPDATE nota = VALUES(nota)';
        $comando = $pdo->prepare($sqlNota);
        foreach ($notas as $nota) {
            $comando->execute([
                $dataId,
                $grupoId,
                $nota['criterioId'],
                $alunoId,
                $nota['alunoId'],
                $nota['nota'],
            ]);
        }

        $sqlFeedback = 'INSERT INTO avaliacoes_feedbacks
                            (seminario_data_id, grupo_id, avaliador_id, tipo_avaliador, feedback_texto)
                        VALUES (?, ?, ?, "aluno", ?)
                        ON DUPLICATE KEY UPDATE feedback_texto = VALUES(feedback_texto)';
        $comando = $pdo->prepare($sqlFeedback);
        $comando->execute([$dataId, $grupoId, $alunoId, $feedback]);

        $pdo->commit();
    } catch (Throwable $erro) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $erro;
    }

    return montar_formulario_aluno($pdo, $alunoId, $dataId);
}
