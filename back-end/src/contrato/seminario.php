<?php

declare(strict_types=1);

function serializar_seminario(
    PDO $pdo,
    array $seminario
): array {

    $seminarioId = (int) $seminario['id'];

    // 1. Buscar critérios

    $consulta = $pdo->prepare(
        'SELECT id, nome_criterio, peso_criterio,
                tipo_avaliacao
         FROM seminario_criterios
         WHERE seminario_id = ?
         ORDER BY id'
    );

    $consulta->execute([$seminarioId]);

    $criterios = [];

    foreach ($consulta->fetchAll() as $criterio) {

        $criterios[] = [
            'id' => (int) $criterio['id'],
            'nome' => $criterio['nome_criterio'],
            'peso' => (int) $criterio['peso_criterio'],
            'tipo' => $criterio['tipo_avaliacao']
        ];
    }

    // 2. Buscar sessões do seminário

    $consulta = $pdo->prepare(
        'SELECT id, data_apresentacao, horario, status
         FROM seminario_datas
         WHERE seminario_id = ?
         ORDER BY id'
    );

    $consulta->execute([$seminarioId]);

    $datas = $consulta->fetchAll();

    $sessoes = [];

    // 3. Preparar consultas relacionadas

    $consultaGrupos = $pdo->prepare(
        'SELECT id, tema, ordem_apresentacao, status
         FROM grupos
         WHERE seminario_data_id = ?
         ORDER BY ordem_apresentacao IS NULL,
                  ordem_apresentacao, id'
    );

    $consultaIntegrantes = $pdo->prepare(
        'SELECT aluno_id
         FROM grupo_alunos
         WHERE grupo_id = ?
         ORDER BY aluno_id'
    );

    $consultaPresencas = $pdo->prepare(
        'SELECT aluno_id, presente
         FROM presencas
         WHERE seminario_data_id = ?
         ORDER BY aluno_id'
    );

    // 4. Organizar cada sessão

    foreach ($datas as $data) {

        $dataId = (int) $data['id'];

        $consultaGrupos->execute([$dataId]);

        $grupos = [];

        foreach ($consultaGrupos->fetchAll() as $grupo) {

            $grupoId = (int) $grupo['id'];

            $consultaIntegrantes->execute([$grupoId]);

            $integrantesIds = array_map(
                'intval',
                $consultaIntegrantes->fetchAll(
                    PDO::FETCH_COLUMN
                )
            );

            $ordem = $grupo['ordem_apresentacao'] !== null
                ? (int) $grupo['ordem_apresentacao']
                : null;

            $grupos[] = [
                'id' => $grupoId,
                'nome' => 'Grupo ' . ($ordem ?? $grupoId),
                'tema' => $grupo['tema'],
                'integrantesIds' => $integrantesIds,
                'ordem' => $ordem,
                'status' => $grupo['status']
            ];
        }

        // 5. Recuperar presenças existentes

        $consultaPresencas->execute([$dataId]);

        $presencas = [];

        foreach ($consultaPresencas->fetchAll() as $presenca) {

            $presencas[] = [
                'alunoId' => (int) $presenca['aluno_id'],
                'presente' => (bool) $presenca['presente']
            ];
        }

        // 6. Montar sessão

        $sessoes[] = [
            'id' => $dataId,
            'data' => $data['data_apresentacao'],
            'horario' => substr($data['horario'], 0, 5),
            'grupos' => $grupos,
            'presenca' => $presencas,
            'chamadaRealizada' => count($presencas) > 0,
            'status' => $data['status'] === 'concluida'
                ? 'finalizado'
                : 'agendado'
        ];
    }

    // 7. Converter status do seminário

    $status = match ($seminario['status']) {
        'pendente' => 'rascunho',
        'concluido' => 'finalizado',
        default => $seminario['status']
    };

    $primeiraSessao = $sessoes[0] ?? null;

    // 8. Montar resposta final

    return [
        'id' => $seminarioId,
        'titulo' => $seminario['titulo'],
        'descricao' => $seminario['descricao'] ?? '',
        'disciplina' => $seminario['disciplina_nome'],
        'turmaId' => (int) $seminario['disciplina_id'],
        'professorId' => (int) $seminario['professor_id'],
        'data' => $primeiraSessao['data'] ?? '',
        'horario' => $primeiraSessao['horario'] ?? '',
        'tempoApresentacaoMin' =>
            (int) $seminario['tempo_apresentacao'],
        'pesoProfessor' =>
            (int) $seminario['peso_professor'] / 100,
        'status' => $status,
        'criterios' => $criterios,
        'grupos' => $primeiraSessao['grupos'] ?? [],
        'presenca' => $primeiraSessao['presenca'] ?? [],
        'sessoes' => $sessoes
    ];
}