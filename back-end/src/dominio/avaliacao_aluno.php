<?php
declare(strict_types=1);

/**
 * Avaliação por pares do aluno (spec-aluno RN-003/RN-004, DP-034/DP-035).
 * Criação e edição usam a mesma operação: as notas antigas do avaliador
 * são substituídas dentro de uma única transação (upsert lógico).
 */
function salvar_avaliacao_aluno(
    PDO $pdo,
    int $alunoId,
    int $dataId,
    int $grupoId,
    array $notas,
    string $feedback
): array {
    $sessao = buscar_sessao_por_id($pdo, $dataId);
    if ($sessao === null) {
        falhar('Sessão não encontrada.', 404);
    }

    if (!aluno_matriculado_no_seminario($pdo, $alunoId, (int) $sessao['seminario_id'])) {
        falhar('O aluno não está matriculado na disciplina deste seminário.', 403);
    }

    if (in_array((string) $sessao['status'], ['concluida', 'cancelada'], true)) {
        falhar('A sessão já foi finalizada; não é possível avaliar.', 400);
    }

    $grupo = buscar_grupo_da_sessao($pdo, $grupoId, $dataId);
    if ($grupo === null) {
        falhar('Grupo não encontrado nesta sessão.', 404);
    }

    $integrantes = ids_da_coluna($grupo['integrantes_ids'] ?? null);
    if (in_array($alunoId, $integrantes, true)) {
        falhar('Autoavaliação não é permitida.', 400);
    }
    if (!aluno_presente_na_sessao($pdo, $alunoId, $dataId)) {
        falhar('O aluno não está presente na chamada desta sessão.', 400);
    }

    $criterios = buscar_criterios_seminario($pdo, (int) $sessao['seminario_id']);
    $porId = [];
    foreach ($criterios as $criterio) {
        $porId[(int) $criterio['id']] = $criterio;
    }

    $mapa = [];
    foreach ($notas as $nota) {
        $criterioId = $nota['criterioId'];
        if (!isset($porId[$criterioId])) {
            falhar('Critério inválido para este seminário.', 400, ['notas' => 'Critério não pertence ao seminário.']);
        }

        $alunoAvaliadoId = $nota['alunoId'];
        if ($porId[$criterioId]['tipo_avaliacao'] === 'grupo') {
            if ($alunoAvaliadoId !== null) {
                falhar('Critério coletivo não aceita aluno avaliado.', 400);
            }
            $chave = (string) $criterioId;
        } else {
            if ($alunoAvaliadoId === null || !in_array($alunoAvaliadoId, $integrantes, true)) {
                falhar('Aluno avaliado inválido para critério individual.', 400);
            }
            $chave = $criterioId . ':' . $alunoAvaliadoId;
        }

        if (isset($mapa[$chave])) {
            falhar('Existe mais de uma nota para o mesmo critério/aluno.', 400);
        }
        $mapa[$chave] = $nota;
    }

    foreach ($criterios as $criterio) {
        $criterioId = (int) $criterio['id'];
        $nome = (string) $criterio['nome_criterio'];
        if ($criterio['tipo_avaliacao'] === 'grupo') {
            if (!isset($mapa[(string) $criterioId])) {
                falhar('Falta a nota do critério "' . $nome . '".', 400);
            }
            continue;
        }
        foreach ($integrantes as $integranteId) {
            if (!isset($mapa[$criterioId . ':' . $integranteId])) {
                falhar('Falta a nota individual do critério "' . $nome . '".', 400);
            }
        }
    }

    $pdo->beginTransaction();
    try {
        $removerNotas = $pdo->prepare(
            "DELETE FROM avaliacoes_notas
             WHERE seminario_data_id = ? AND grupo_id = ? AND tipo_avaliador = 'aluno' AND avaliador_id = ?"
        );
        $removerNotas->execute([$dataId, $grupoId, $alunoId]);

        $inserirNota = $pdo->prepare(
            "INSERT INTO avaliacoes_notas
                (seminario_data_id, grupo_id, criterio_id, avaliador_id, tipo_avaliador, aluno_avaliado_id, nota)
             VALUES (?, ?, ?, ?, 'aluno', ?, ?)"
        );
        foreach ($mapa as $nota) {
            $inserirNota->execute([
                $dataId,
                $grupoId,
                $nota['criterioId'],
                $alunoId,
                $nota['alunoId'],
                number_format($nota['nota'], 2, '.', ''),
            ]);
        }

        $removerFeedback = $pdo->prepare(
            "DELETE FROM avaliacoes_feedbacks
             WHERE seminario_data_id = ? AND grupo_id = ? AND tipo_avaliador = 'aluno' AND avaliador_id = ?"
        );
        $removerFeedback->execute([$dataId, $grupoId, $alunoId]);

        if ($feedback !== '') {
            $inserirFeedback = $pdo->prepare(
                "INSERT INTO avaliacoes_feedbacks
                    (seminario_data_id, grupo_id, avaliador_id, tipo_avaliador, feedback_texto)
                 VALUES (?, ?, ?, 'aluno', ?)"
            );
            $inserirFeedback->execute([$dataId, $grupoId, $alunoId, $feedback]);
        }

        $pdo->commit();
    } catch (Throwable $erro) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $erro;
    }

    return carregar_formulario_aluno($pdo, $alunoId, $dataId);
}
