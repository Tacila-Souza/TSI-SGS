<?php
declare(strict_types=1);

require dirname(__DIR__, 4) . '/src/bootstrap.php';

exigir_metodo('GET');

$alunoId = exigir_identidade('aluno');
$dataId = query_int('data_id');

if ($dataId === null) {
    responder_erro('O parâmetro data_id é obrigatório.', 400);
}

$stmt = $pdo->prepare('SELECT id, peso_professor, peso_alunos FROM seminarios WHERE id = (SELECT seminario_id FROM seminario_datas WHERE id = ?)');
$stmt->execute([$dataId]);
$seminario = $stmt->fetch();

if ($seminario === false) {
    responder_erro('Sessão não encontrada.', 404);
}

$seminarioId = (int) $seminario['id'];
$pesoProfessorDb = (int) $seminario['peso_professor'];
$pesoAlunosDb = (int) $seminario['peso_alunos'];

// Aluno só consulta resultados de seminários em que está matriculado.
validar_matricula_seminario($pdo, $alunoId, $seminarioId);

$resultado = compilar_resultados($pdo, $dataId, $alunoId);

// O compilador da main não devolve os pesos nem as notas por critério.
// Este endpoint complementa o DTO sem alterar src/.
$resultado['pesoProfessor'] = round($pesoProfessorDb / 100, 2);
$resultado['pesoAlunos'] = round($pesoAlunosDb / 100, 2);

$stmt = $pdo->prepare(
    'SELECT g.id AS grupo_id, ga.aluno_id
     FROM grupos g
     LEFT JOIN grupo_alunos ga ON ga.grupo_id = g.id
     WHERE g.seminario_data_id = ?'
);
$stmt->execute([$dataId]);
$integrantesPorGrupo = [];
foreach ($stmt->fetchAll() as $linha) {
    $grupoId = (int) $linha['grupo_id'];
    $integrantesPorGrupo[$grupoId] ??= [];
    if ($linha['aluno_id'] !== null) {
        $integrantesPorGrupo[$grupoId][] = (int) $linha['aluno_id'];
    }
}

$stmt = $pdo->query('SELECT id, nome_completo FROM alunos');
$nomesAlunos = [];
foreach ($stmt->fetchAll() as $linha) {
    $nomesAlunos[(int) $linha['id']] = (string) $linha['nome_completo'];
}

$gruposDoAluno = [];
foreach ($integrantesPorGrupo as $grupoId => $integrantes) {
    if (in_array($alunoId, $integrantes, true)) {
        $gruposDoAluno[] = $grupoId;
    }
}

$stmt = $pdo->prepare(
    'SELECT grupo_id, criterio_id, aluno_avaliado_id, tipo_avaliador, avaliador_id, nota
     FROM avaliacoes_notas
     WHERE seminario_data_id = ?'
);
$stmt->execute([$dataId]);
$notas = [];
foreach ($stmt->fetchAll() as $linha) {
    $grupoId = (int) $linha['grupo_id'];
    $criterioId = (int) $linha['criterio_id'];
    $alvo = $linha['aluno_avaliado_id'] === null ? 0 : (int) $linha['aluno_avaliado_id'];
    $tipo = $linha['tipo_avaliador'];
    $notas[$grupoId][$tipo][$criterioId][$alvo][] = [
        'nota' => (float) $linha['nota'],
        'avaliador' => (int) $linha['avaliador_id'],
    ];
}

$media = static function (array $valores): ?float {
    return $valores === [] ? null : round(array_sum($valores) / count($valores), 2);
};

// Closure simples para extrair notas (por critério/alvo/avaliador) de um grupo.
$extrair = static function (int $grupoId, string $tipo, int $criterioId, int $alvo, ?int $avaliador = null) use ($notas): array {
    $itens = $notas[$grupoId][$tipo][$criterioId][$alvo] ?? [];
    $valores = [];
    foreach ($itens as $item) {
        if ($avaliador === null || $item['avaliador'] === $avaliador) {
            $valores[] = $item['nota'];
        }
    }
    return $valores;
};

foreach ($resultado['ranking'] as &$grupo) {
    $grupoId = (int) $grupo['grupoId'];
    $integrantes = $integrantesPorGrupo[$grupoId] ?? [];
    $quantidade = max(count($integrantes), 1);

    // O compilador da main esvazia os integrantes do grupo do próprio aluno;
    // reconstruímos a lista para poder calcular as notas individuais.
    $grupo['integrantes'] = [];
    foreach ($integrantes as $alunoIntegrante) {
        $grupo['integrantes'][] = [
            'alunoId' => $alunoIntegrante,
            'nome' => $nomesAlunos[$alunoIntegrante] ?? ('Aluno ' . $alunoIntegrante),
            'notaProfessor' => 0.0,
            'notaAlunos' => 0.0,
            'notaFinal' => 0.0,
        ];
    }

    $avaliadores = [];
    foreach ($notas[$grupoId]['aluno'] ?? [] as $porCriterio) {
        foreach ($porCriterio as $porAlvo) {
            foreach ($porAlvo as $item) {
                $avaliadores[$item['avaliador']] = true;
            }
        }
    }
    $avaliadores = array_keys($avaliadores);

    $notasProfessorPorCriterio = [];
    $notasParesPorCriterio = [];

    foreach ($resultado['criterios'] as $criterio) {
        $criterioId = (int) $criterio['id'];
        $individual = $criterio['tipo'] === 'individual';

        $valoresProfessor = [];
        $valoresPares = [];
        foreach ($integrantes as $alunoIntegrante) {
            $alvo = $individual ? $alunoIntegrante : 0;
            $valoresProfessor = array_merge($valoresProfessor, $extrair($grupoId, 'professor', $criterioId, $alvo));
            $valoresPares = array_merge($valoresPares, $extrair($grupoId, 'aluno', $criterioId, $alvo));
        }
        if (!$individual) {
            $valoresProfessor = $extrair($grupoId, 'professor', $criterioId, 0);
            $valoresPares = $extrair($grupoId, 'aluno', $criterioId, 0);
        }

        $notasProfessorPorCriterio[] = ['criterioId' => $criterioId, 'nota' => $media($valoresProfessor)];
        $notasParesPorCriterio[] = ['criterioId' => $criterioId, 'nota' => $media($valoresPares)];
    }

    $somaProfessor = 0.0;
    $somaPares = 0.0;
    $somaFinal = 0.0;

    foreach ($grupo['integrantes'] as &$integrante) {
        $integranteId = (int) $integrante['alunoId'];

        $notaProfessor = 0.0;
        foreach ($resultado['criterios'] as $criterio) {
            $criterioId = (int) $criterio['id'];
            $alvo = $criterio['tipo'] === 'individual' ? $integranteId : 0;
            $valor = $media($extrair($grupoId, 'professor', $criterioId, $alvo));
            $notaProfessor += ($valor ?? 0.0) * (int) $criterio['peso'] / 100;
        }

        $notasPares = [];
        foreach ($avaliadores as $avaliador) {
            $notaAvaliador = 0.0;
            foreach ($resultado['criterios'] as $criterio) {
                $criterioId = (int) $criterio['id'];
                $alvo = $criterio['tipo'] === 'individual' ? $integranteId : 0;
                $valor = $media($extrair($grupoId, 'aluno', $criterioId, $alvo, $avaliador));
                $notaAvaliador += ($valor ?? 0.0) * (int) $criterio['peso'] / 100;
            }
            $notasPares[] = $notaAvaliador;
        }
        $notaPares = $notasPares !== [] ? array_sum($notasPares) / count($notasPares) : $notaProfessor;
        $notaFinal = $notaProfessor * ($pesoProfessorDb / 100) + $notaPares * ($pesoAlunosDb / 100);

        $integrante['notaProfessor'] = round($notaProfessor, 2);
        $integrante['notaAlunos'] = round($notaPares, 2);
        $integrante['notaFinal'] = round($notaFinal, 2);

        $somaProfessor += $notaProfessor;
        $somaPares += $notaPares;
        $somaFinal += $notaFinal;
    }
    unset($integrante);

    $grupo['notaProfessor'] = round($somaProfessor / $quantidade, 2);
    $grupo['notaAlunos'] = round($somaPares / $quantidade, 2);
    $grupo['mediaGeral'] = round($somaFinal / $quantidade, 2);
    $grupo['avaliadoresConsiderados'] = count($avaliadores);
    $grupo['notasProfessorPorCriterio'] = $notasProfessorPorCriterio;
    $grupo['notasParesPorCriterio'] = in_array($grupoId, $gruposDoAluno, true)
        ? $notasParesPorCriterio
        : [];

    // Privacidade (DP-036): o aluno vê detalhes apenas do próprio grupo.
    if (!in_array($grupoId, $gruposDoAluno, true)) {
        $grupo['integrantes'] = [];
    }
}
unset($grupo);

$resultado['feedbacks'] = array_values(array_filter(
    $resultado['feedbacks'],
    static fn (array $feedback): bool => $feedback['tipo'] !== 'par'
        || in_array((int) $feedback['grupoId'], $gruposDoAluno, true)
));

$temAvaliacoesDePares = false;
foreach ($notas as $notasDoGrupo) {
    if (!empty($notasDoGrupo['aluno'])) {
        $temAvaliacoesDePares = true;
        break;
    }
}
$resultado['semAvaliadores'] = !$temAvaliacoesDePares;

usort($resultado['ranking'], static fn (array $a, array $b): int => $b['mediaGeral'] <=> $a['mediaGeral']);
foreach ($resultado['ranking'] as $indice => &$item) {
    $item['posicao'] = $indice + 1;
}
unset($item);

responder_sucesso($resultado);
