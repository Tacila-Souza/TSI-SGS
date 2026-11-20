<?php
declare(strict_types=1);

/**
 * Compila o ResultadoDTO (contrato 7.10) com privacidade para o aluno (DP-036).
 * Cálculo conforme RN-003/RN-004/RN-005: T_s, P_s, F_s e M_g,
 * com fallback P_s = T_s quando não há avaliadores de pares.
 */
function compilar_resultados(PDO $pdo, int $alunoId, int $dataId): array
{
    $sessao = carregar_sessao($pdo, $dataId);
    if ($sessao === null) {
        throw new RegraNegocioException('Sessão não encontrada.', 404);
    }
    if ((string) $sessao['status'] !== 'concluida') {
        throw new RegraNegocioException(
            'Os resultados só ficam disponíveis após a conclusão da sessão.',
            400,
        );
    }

    $seminarioId = (int) $sessao['seminario_id'];
    if (!aluno_matriculado($pdo, $alunoId, $seminarioId)) {
        throw new RegraNegocioException('Aluno não matriculado neste seminário.', 403);
    }

    $criterios = listar_criterios($pdo, $seminarioId);
    $grupos = listar_grupos_da_sessao($pdo, $dataId);
    $professorId = (int) $sessao['professor_id'];
    $pesoProfessor = (int) $sessao['peso_professor'];
    $pesoAlunos = (int) $sessao['peso_alunos'];

    $presentes = [];
    foreach (listar_presencas($pdo, $dataId) as $presenca) {
        if ((bool) $presenca['presente']) {
            $presentes[] = (int) $presenca['aluno_id'];
        }
    }

    $comando = $pdo->prepare(
        'SELECT grupo_id, criterio_id, avaliador_id, tipo_avaliador, aluno_avaliado_id, nota
         FROM avaliacoes_notas
         WHERE seminario_data_id = ?
         ORDER BY grupo_id, tipo_avaliador, avaliador_id, criterio_id'
    );
    $comando->execute([$dataId]);
    $notasPorGrupo = [];
    foreach ($comando->fetchAll() as $linha) {
        $grupoId = (int) $linha['grupo_id'];
        $tipo = (string) $linha['tipo_avaliador'];
        $avaliadorId = (int) $linha['avaliador_id'];
        $notasPorGrupo[$grupoId][$tipo][$avaliadorId][] = $linha;
    }

    $comando = $pdo->prepare(
        'SELECT grupo_id, avaliador_id, tipo_avaliador, feedback_texto
         FROM avaliacoes_feedbacks
         WHERE seminario_data_id = ?
         ORDER BY grupo_id, tipo_avaliador, avaliador_id'
    );
    $comando->execute([$dataId]);
    $feedbacks = $comando->fetchAll();

    $comando = $pdo->prepare(
        'SELECT ga.grupo_id
         FROM grupo_alunos ga
         JOIN grupos g ON g.id = ga.grupo_id
         WHERE ga.aluno_id = ? AND g.seminario_data_id = ?
         LIMIT 1'
    );
    $comando->execute([$alunoId, $dataId]);
    $meuGrupoId = $comando->fetchColumn();
    $meuGrupoId = $meuGrupoId === false ? null : (int) $meuGrupoId;

    $ranking = [];
    $houveAvaliadores = false;

    foreach ($grupos as $grupo) {
        $grupoId = (int) $grupo['id'];
        $integrantesIds = array_map('intval', $grupo['integrantes_ids']);
        $nomes = buscar_nomes_alunos($pdo, $integrantesIds);

        $notasProfessor = $notasPorGrupo[$grupoId]['professor'][$professorId] ?? [];

        $avaliadores = [];
        foreach ($notasPorGrupo[$grupoId]['aluno'] ?? [] as $avaliadorId => $linhas) {
            $avaliadorId = (int) $avaliadorId;
            if (in_array($avaliadorId, $integrantesIds, true)) {
                continue;
            }
            if (!in_array($avaliadorId, $presentes, true)) {
                continue;
            }
            $avaliadores[$avaliadorId] = $linhas;
        }
        $n = count($avaliadores);
        if ($n > 0) {
            $houveAvaliadores = true;
        }

        $integrantes = [];
        $somasProfessor = [];
        $somasPares = [];

        foreach ($integrantesIds as $alunoAvaliadoId) {
            $notaProfessor = calcular_nota_normalizada($notasProfessor, $criterios, $alunoAvaliadoId);

            $parciais = [];
            foreach ($avaliadores as $linhas) {
                $notaPar = calcular_nota_normalizada($linhas, $criterios, $alunoAvaliadoId);
                if ($notaPar !== null) {
                    $parciais[] = $notaPar;
                }
            }

            // RN-005: sem avaliadores com notas, a nota dos pares espelha a do professor.
            $notaPares = $parciais !== []
                ? array_sum($parciais) / count($parciais)
                : $notaProfessor;

            $notaFinal = calcular_nota_final($notaProfessor, $notaPares, $pesoProfessor, $pesoAlunos);

            if ($notaProfessor !== null) {
                $somasProfessor[] = $notaProfessor;
            }
            if ($notaPares !== null) {
                $somasPares[] = $notaPares;
            }

            $integrantes[] = [
                'alunoId' => $alunoAvaliadoId,
                'nome' => $nomes[$alunoAvaliadoId] ?? '',
                'notaProfessor' => $notaProfessor === null ? null : round($notaProfessor, 2),
                'notaAlunos' => $notaPares === null ? 0.0 : round($notaPares, 2),
                'notaFinal' => round($notaFinal, 2),
            ];
        }

        $medias = array_map(static fn (array $i): float => (float) $i['notaFinal'], $integrantes);
        $mediaGeral = $medias !== [] ? array_sum($medias) / count($medias) : 0.0;

        $ranking[] = [
            'grupoId' => $grupoId,
            'grupoNome' => 'Grupo ' . ((int) ($grupo['ordem_apresentacao'] ?? $grupoId)),
            'tema' => (string) $grupo['tema'],
            'mediaGeral' => round($mediaGeral, 2),
            'notaProfessor' => $somasProfessor !== []
                ? round(array_sum($somasProfessor) / count($somasProfessor), 2)
                : null,
            'notaAlunos' => $somasPares !== []
                ? round(array_sum($somasPares) / count($somasPares), 2)
                : 0.0,
            'avaliadoresConsiderados' => $n,
            // DP-036: detalhes individuais somente do grupo do aluno.
            'integrantes' => $grupoId === $meuGrupoId ? $integrantes : [],
        ];
    }

    usort($ranking, static function (array $a, array $b): int {
        $comparacao = $b['mediaGeral'] <=> $a['mediaGeral'];

        return $comparacao !== 0 ? $comparacao : $a['grupoId'] <=> $b['grupoId'];
    });

    foreach ($ranking as $indice => &$item) {
        $item['posicao'] = $indice + 1;
    }
    unset($item);

    // A ordenação das chaves segue o contrato; reindexa para JSON estável.
    $ranking = array_map(static function (array $item): array {
        return [
            'posicao' => $item['posicao'],
            'grupoId' => $item['grupoId'],
            'grupoNome' => $item['grupoNome'],
            'tema' => $item['tema'],
            'mediaGeral' => $item['mediaGeral'],
            'notaProfessor' => $item['notaProfessor'],
            'notaAlunos' => $item['notaAlunos'],
            'avaliadoresConsiderados' => $item['avaliadoresConsiderados'],
            'integrantes' => $item['integrantes'],
        ];
    }, $ranking);

    $feedbacksDTO = [];
    foreach ($feedbacks as $feedback) {
        $grupoId = (int) $feedback['grupo_id'];
        $tipo = (string) $feedback['tipo_avaliador'] === 'aluno' ? 'par' : 'professor';

        // Professor é público na sessão; pares só no próprio grupo.
        if ($tipo === 'par' && $grupoId !== $meuGrupoId) {
            continue;
        }

        $feedbacksDTO[] = [
            'grupoId' => $grupoId,
            'avaliadorId' => (int) $feedback['avaliador_id'],
            'tipo' => $tipo,
            'texto' => (string) $feedback['feedback_texto'],
        ];
    }

    return serializar_resultado([
        'seminarioId' => $seminarioId,
        'dataId' => $dataId,
        'dataSessao' => (string) $sessao['data_apresentacao'],
        'semAvaliadores' => !$houveAvaliadores,
        'criterios' => $criterios,
        'ranking' => $ranking,
        'feedbacks' => $feedbacksDTO,
    ]);
}

/**
 * Média ponderada normalizada de um avaliador para um aluno (T_s ou P_s parcial).
 * Critérios de grupo usam a nota com aluno_avaliado_id = NULL.
 */
function calcular_nota_normalizada(array $linhas, array $criterios, int $alunoId): ?float
{
    $pesos = [];
    foreach ($criterios as $criterio) {
        $pesos[(int) $criterio['id']] = [
            'peso' => (int) $criterio['peso_criterio'],
            'tipo' => (string) $criterio['tipo_avaliacao'],
        ];
    }

    $soma = 0.0;
    $encontrou = false;

    foreach ($linhas as $linha) {
        $criterioId = (int) $linha['criterio_id'];
        if (!isset($pesos[$criterioId])) {
            continue;
        }

        $avaliadoId = $linha['aluno_avaliado_id'] === null ? null : (int) $linha['aluno_avaliado_id'];
        if ($pesos[$criterioId]['tipo'] === 'grupo') {
            if ($avaliadoId !== null) {
                continue;
            }
        } elseif ($avaliadoId !== $alunoId) {
            continue;
        }

        $soma += (float) $linha['nota'] * $pesos[$criterioId]['peso'];
        $encontrou = true;
    }

    return $encontrou ? $soma / 100.0 : null;
}

function calcular_nota_final(?float $notaProfessor, ?float $notaPares, int $pesoProfessor, int $pesoAlunos): float
{
    if ($notaProfessor === null && $notaPares === null) {
        return 0.0;
    }
    if ($notaProfessor === null) {
        return $notaPares;
    }
    if ($notaPares === null) {
        return $notaProfessor;
    }

    return $notaProfessor * ($pesoProfessor / 100) + $notaPares * ($pesoAlunos / 100);
}

function buscar_nomes_alunos(PDO $pdo, array $alunosIds): array
{
    if ($alunosIds === []) {
        return [];
    }

    $marcadores = implode(',', array_fill(0, count($alunosIds), '?'));
    $comando = $pdo->prepare(
        "SELECT id, nome_completo FROM alunos WHERE id IN ({$marcadores})"
    );
    $comando->execute(array_values($alunosIds));

    $nomes = [];
    foreach ($comando->fetchAll() as $linha) {
        $nomes[(int) $linha['id']] = (string) $linha['nome_completo'];
    }

    return $nomes;
}
