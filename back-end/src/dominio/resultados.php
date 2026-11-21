<?php
declare(strict_types=1);

function compilar_resultados(PDO $pdo, int $dataId, ?int $alunoId = null): array
{
    $stmt = $pdo->prepare(
        'SELECT sd.*, s.id AS seminario_id, s.titulo, s.peso_professor, s.peso_alunos, s.professor_id
         FROM seminario_datas sd
         INNER JOIN seminarios s ON s.id = sd.seminario_id
         WHERE sd.id = ?'
    );
    $stmt->execute([$dataId]);
    $data = $stmt->fetch();
    if ($data === false) {
        responder_erro('Sessão não encontrada.', 404);
    }

    $pesoProfessor = (int) $data['peso_professor'];
    $pesoAlunos = (int) $data['peso_alunos'];

    $stmt = $pdo->prepare('SELECT id, nome_criterio, peso_criterio, tipo_avaliacao FROM seminario_criterios WHERE seminario_id = ? ORDER BY id');
    $stmt->execute([(int) $data['seminario_id']]);
    $criterios = $stmt->fetchAll();
    $criteriosApi = array_map('serializar_criterio', $criterios);

    $stmt = $pdo->prepare('SELECT * FROM grupos WHERE seminario_data_id = ? ORDER BY ordem_apresentacao, id');
    $stmt->execute([$dataId]);
    $grupos = $stmt->fetchAll();

    $stmt = $pdo->prepare(
        'SELECT n.*, p.nome_completo AS avaliador_nome
         FROM avaliacoes_notas n
         LEFT JOIN professores p ON p.id = n.avaliador_id AND n.tipo_avaliador = ?
         WHERE n.seminario_data_id = ?'
    );
    $stmt->execute(['professor', $dataId]);
    $notasProfessor = $stmt->fetchAll();

    $stmt = $pdo->prepare(
        'SELECT n.*, a.nome_completo AS avaliador_nome
         FROM avaliacoes_notas n
         LEFT JOIN alunos a ON a.id = n.avaliador_id AND n.tipo_avaliador = ?
         WHERE n.seminario_data_id = ?'
    );
    $stmt->execute(['aluno', $dataId]);
    $notasPares = $stmt->fetchAll();

    $stmt = $pdo->prepare(
        'SELECT f.*, COALESCE(p.nome_completo, a.nome_completo) AS avaliador_nome
         FROM avaliacoes_feedbacks f
         LEFT JOIN professores p ON p.id = f.avaliador_id AND f.tipo_avaliador = ?
         LEFT JOIN alunos a ON a.id = f.avaliador_id AND f.tipo_avaliador = ?
         WHERE f.seminario_data_id = ?'
    );
    $stmt->execute(['professor', 'aluno', $dataId]);
    $feedbacks = $stmt->fetchAll();

    $ranking = [];
    $todosFeedbacks = [];
    $semAvaliadores = true;

    foreach ($grupos as $grupo) {
        $grupoId = (int) $grupo['id'];

        $stmtIntegrantes = $pdo->prepare(
            'SELECT a.id, a.nome_completo FROM grupo_alunos ga
             INNER JOIN alunos a ON a.id = ga.aluno_id
             WHERE ga.grupo_id = ? ORDER BY a.nome_completo'
        );
        $stmtIntegrantes->execute([$grupoId]);
        $integrantes = $stmtIntegrantes->fetchAll();

        $profNotasGrupo = array_values(array_filter(
            $notasProfessor,
            static fn (array $n): bool => (int) $n['grupo_id'] === $grupoId
        ));
        $paresGrupo = array_values(array_filter(
            $notasPares,
            static fn (array $n): bool => (int) $n['grupo_id'] === $grupoId
        ));

        $avaliadores = array_values(array_unique(array_map(
            static fn (array $n): int => (int) $n['avaliador_id'],
            $paresGrupo
        )));
        if ($avaliadores !== []) {
            $semAvaliadores = false;
        }

        $notaPorCriterioProfessor = [];
        foreach ($profNotasGrupo as $nota) {
            $chave = (int) $nota['criterio_id'] . ':' . (int) ($nota['aluno_avaliado_id'] ?? 0);
            $notaPorCriterioProfessor[$chave] = (float) $nota['nota'];
        }

        $integrantesResultado = [];
        $somaMedia = 0.0;
        $somaNotaProfessor = 0.0;
        $somaNotaAlunos = 0.0;

        foreach ($integrantes as $aluno) {
            $alunoId = (int) $aluno['id'];

            $notaProfessor = 0.0;
            foreach ($criterios as $criterio) {
                $criterioId = (int) $criterio['id'];
                if ($criterio['tipo_avaliacao'] === 'grupo') {
                    $valor = $notaPorCriterioProfessor[$criterioId . ':0'] ?? 0.0;
                } else {
                    $valor = $notaPorCriterioProfessor[$criterioId . ':' . $alunoId] ?? 0.0;
                }
                $notaProfessor += $valor * (int) $criterio['peso_criterio'];
            }
            $notaProfessor /= 100;

            $somaPares = 0.0;
            foreach ($avaliadores as $avaliador) {
                $notaAvaliador = 0.0;
                foreach ($criterios as $criterio) {
                    $criterioId = (int) $criterio['id'];
                    $alvo = $criterio['tipo_avaliacao'] === 'grupo' ? 0 : $alunoId;
                    foreach ($paresGrupo as $nota) {
                        if ((int) $nota['avaliador_id'] === $avaliador
                            && (int) $nota['criterio_id'] === $criterioId
                            && (int) ($nota['aluno_avaliado_id'] ?? 0) === $alvo) {
                            $notaAvaliador += (float) $nota['nota'] * (int) $criterio['peso_criterio'];
                            break;
                        }
                    }
                }
                $somaPares += $notaAvaliador / 100;
            }
            $notaAlunos = $avaliadores !== []
                ? $somaPares / count($avaliadores)
                : $notaProfessor;

            $notaFinal = $notaProfessor * ($pesoProfessor / 100) + $notaAlunos * ($pesoAlunos / 100);

            $somaNotaProfessor += $notaProfessor;
            $somaNotaAlunos += $notaAlunos;
            $somaMedia += $notaFinal;

            $integrantesResultado[] = [
                'alunoId' => $alunoId,
                'nome' => (string) $aluno['nome_completo'],
                'notaProfessor' => round($notaProfessor, 2),
                'notaAlunos' => round($notaAlunos, 2),
                'notaFinal' => round($notaFinal, 2),
            ];
        }

        $quantidade = max(count($integrantes), 1);
        $grupo = [
            'posicao' => 0,
            'grupoId' => $grupoId,
            'grupoNome' => 'Grupo ' . ((int) ($grupo['ordem_apresentacao'] ?? 0) ?: $grupoId),
            'tema' => (string) $grupo['tema'],
            'mediaGeral' => round($somaMedia / $quantidade, 2),
            'notaProfessor' => round($somaNotaProfessor / $quantidade, 2),
            'notaAlunos' => round($somaNotaAlunos / $quantidade, 2),
            'avaliadoresConsiderados' => count($avaliadores),
            'integrantes' => $integrantesResultado,
        ];
        $ranking[] = $grupo;

        $idsIntegrantes = array_map('intval', array_column($integrantes, 'id'));
        $grupoPertenceAluno = $alunoId !== null && in_array($alunoId, $idsIntegrantes, true);
        foreach ($feedbacks as $feedback) {
            if ((int) $feedback['grupo_id'] !== $grupoId) {
                continue;
            }
            if ($alunoId !== null && !$grupoPertenceAluno && $feedback['tipo_avaliador'] === 'aluno') {
                continue;
            }
            $todosFeedbacks[] = [
                'grupoId' => $grupoId,
                'avaliadorId' => (int) $feedback['avaliador_id'],
                'tipo' => $feedback['tipo_avaliador'] === 'aluno' ? 'par' : 'professor',
                'texto' => (string) $feedback['feedback_texto'],
                'avaliadorNome' => $feedback['avaliador_nome'] ?? null,
            ];
        }
    }

    usort($ranking, static fn (array $a, array $b): int => $b['mediaGeral'] <=> $a['mediaGeral']);
    foreach ($ranking as $indice => &$item) {
        $item['posicao'] = $indice + 1;
        if ($alunoId !== null) {
            $pertence = false;
            foreach ($item['integrantes'] as $integrante) {
                if ($integrante['alunoId'] === $alunoId) {
                    $pertence = true;
                    break;
                }
            }
            if (!$pertence) {
                $item['integrantes'] = [];
            }
        }
    }
    unset($item);

    return [
        'seminarioId' => (int) $data['seminario_id'],
        'dataId' => $dataId,
        'dataSessao' => (string) $data['data_apresentacao'],
        'semAvaliadores' => $semAvaliadores,
        'criterios' => $criteriosApi,
        'ranking' => $ranking,
        'feedbacks' => $todosFeedbacks,
    ];
}
