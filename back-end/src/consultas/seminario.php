<?php
declare(strict_types=1);

function buscar_seminario(PDO $pdo, int $seminarioId): ?array
{
    $comando = $pdo->prepare(
        'SELECT s.*, d.nome AS disciplina_nome
         FROM seminarios s
         JOIN disciplinas d ON d.id = s.disciplina_id
         WHERE s.id = ?'
    );
    $comando->execute([$seminarioId]);
    $linha = $comando->fetch();

    return $linha === false ? null : $linha;
}

function listar_seminarios_do_aluno(PDO $pdo, int $alunoId): array
{
    $comando = $pdo->prepare(
        'SELECT DISTINCT s.*, d.nome AS disciplina_nome
         FROM seminarios s
         JOIN disciplinas d ON d.id = s.disciplina_id
         JOIN disciplina_aluno da ON da.disciplina_id = d.id
         WHERE da.aluno_id = ?
         ORDER BY s.id'
    );
    $comando->execute([$alunoId]);

    return $comando->fetchAll();
}

function listar_criterios(PDO $pdo, int $seminarioId): array
{
    $comando = $pdo->prepare(
        'SELECT id, nome_criterio, peso_criterio, tipo_avaliacao
         FROM seminario_criterios
         WHERE seminario_id = ?
         ORDER BY id'
    );
    $comando->execute([$seminarioId]);

    return $comando->fetchAll();
}

function carregar_sessao(PDO $pdo, int $dataId): ?array
{
    $comando = $pdo->prepare(
        'SELECT sd.*, s.id AS seminario_id, s.professor_id, s.disciplina_id,
                s.peso_professor, s.peso_alunos
         FROM seminario_datas sd
         JOIN seminarios s ON s.id = sd.seminario_id
         WHERE sd.id = ?'
    );
    $comando->execute([$dataId]);
    $linha = $comando->fetch();

    return $linha === false ? null : $linha;
}

function listar_sessoes(PDO $pdo, int $seminarioId): array
{
    $comando = $pdo->prepare(
        'SELECT id, data_apresentacao, horario, status
         FROM seminario_datas
         WHERE seminario_id = ?
         ORDER BY data_apresentacao, horario, id'
    );
    $comando->execute([$seminarioId]);

    return $comando->fetchAll();
}

function listar_grupos_da_sessao(PDO $pdo, int $dataId): array
{
    $comando = $pdo->prepare(
        'SELECT g.id, g.tema, g.ordem_apresentacao, g.status,
                COALESCE(GROUP_CONCAT(ga.aluno_id ORDER BY ga.aluno_id SEPARATOR ","), "") AS integrantes
         FROM grupos g
         LEFT JOIN grupo_alunos ga ON ga.grupo_id = g.id
         WHERE g.seminario_data_id = ?
         GROUP BY g.id, g.tema, g.ordem_apresentacao, g.status
         ORDER BY g.ordem_apresentacao IS NULL, g.ordem_apresentacao, g.id'
    );
    $comando->execute([$dataId]);

    $linhas = $comando->fetchAll();
    foreach ($linhas as &$linha) {
        $linha['integrantes_ids'] = parsear_ids((string) $linha['integrantes']);
        unset($linha['integrantes']);
    }

    return $linhas;
}

function listar_presencas(PDO $pdo, int $dataId): array
{
    $comando = $pdo->prepare(
        'SELECT aluno_id, presente FROM presencas WHERE seminario_data_id = ? ORDER BY aluno_id'
    );
    $comando->execute([$dataId]);

    return $comando->fetchAll();
}

function carregar_seminario_completo(PDO $pdo, int $seminarioId): ?array
{
    $seminario = buscar_seminario($pdo, $seminarioId);
    if ($seminario === null) {
        return null;
    }

    $criterios = listar_criterios($pdo, $seminarioId);
    $sessoes = [];

    foreach (listar_sessoes($pdo, $seminarioId) as $sessao) {
        $dataId = (int) $sessao['id'];
        $sessoes[] = serializar_sessao(
            $sessao,
            listar_grupos_da_sessao($pdo, $dataId),
            listar_presencas($pdo, $dataId),
        );
    }

    return serializar_seminario(
        $seminario,
        $criterios,
        $sessoes[0]['grupos'] ?? [],
        $sessoes[0]['presenca'] ?? [],
        $sessoes,
    );
}

function aluno_matriculado(PDO $pdo, int $alunoId, int $seminarioId): bool
{
    $comando = $pdo->prepare(
        'SELECT 1
         FROM disciplina_aluno da
         JOIN seminarios s ON s.disciplina_id = da.disciplina_id
         WHERE da.aluno_id = ? AND s.id = ?
         LIMIT 1'
    );
    $comando->execute([$alunoId, $seminarioId]);

    return $comando->fetchColumn() !== false;
}

function aluno_presente(PDO $pdo, int $alunoId, int $dataId): bool
{
    $comando = $pdo->prepare(
        'SELECT presente FROM presencas WHERE seminario_data_id = ? AND aluno_id = ? LIMIT 1'
    );
    $comando->execute([$dataId, $alunoId]);

    return (bool) $comando->fetchColumn();
}

function grupo_pertence_a_sessao(PDO $pdo, int $grupoId, int $dataId): bool
{
    $comando = $pdo->prepare(
        'SELECT 1 FROM grupos WHERE id = ? AND seminario_data_id = ? LIMIT 1'
    );
    $comando->execute([$grupoId, $dataId]);

    return $comando->fetchColumn() !== false;
}

function integrantes_do_grupo(PDO $pdo, int $grupoId): array
{
    $comando = $pdo->prepare(
        'SELECT aluno_id FROM grupo_alunos WHERE grupo_id = ? ORDER BY aluno_id'
    );
    $comando->execute([$grupoId]);

    return array_map('intval', $comando->fetchAll(PDO::FETCH_COLUMN));
}

function aluno_avaliou_grupo(PDO $pdo, int $alunoId, int $dataId, int $grupoId): bool
{
    $comando = $pdo->prepare(
        'SELECT 1
         FROM avaliacoes_notas
         WHERE seminario_data_id = ? AND grupo_id = ?
           AND tipo_avaliador = "aluno" AND avaliador_id = ?
         LIMIT 1'
    );
    $comando->execute([$dataId, $grupoId, $alunoId]);

    return $comando->fetchColumn() !== false;
}

function montar_formulario_aluno(PDO $pdo, int $alunoId, int $dataId): array
{
    $sessao = carregar_sessao($pdo, $dataId);
    if ($sessao === null) {
        throw new RegraNegocioException('Sessão não encontrada.', 404);
    }

    $seminarioId = (int) $sessao['seminario_id'];
    $comando = $pdo->prepare(
        'SELECT g.id, g.tema, g.ordem_apresentacao, g.status,
                COALESCE(GROUP_CONCAT(ga.aluno_id ORDER BY ga.aluno_id SEPARATOR ","), "") AS integrantes
         FROM grupos g
         LEFT JOIN grupo_alunos ga ON ga.grupo_id = g.id
         WHERE g.seminario_data_id = ?
           AND NOT EXISTS (
               SELECT 1 FROM grupo_alunos ga2
               WHERE ga2.grupo_id = g.id AND ga2.aluno_id = ?
           )
           AND NOT EXISTS (
               SELECT 1 FROM avaliacoes_notas an2
               WHERE an2.seminario_data_id = g.seminario_data_id
                 AND an2.grupo_id = g.id
                 AND an2.tipo_avaliador = "aluno"
                 AND an2.avaliador_id = ?
           )
         GROUP BY g.id, g.tema, g.ordem_apresentacao, g.status
         HAVING COUNT(ga.aluno_id) >= 1
         ORDER BY g.ordem_apresentacao IS NULL, g.ordem_apresentacao, g.id'
    );
    $comando->execute([$dataId, $alunoId, $alunoId]);
    $grupos = array_map(
        static function (array $linha): array {
            $linha['integrantes_ids'] = parsear_ids((string) $linha['integrantes']);
            unset($linha['integrantes']);

            return serializar_grupo($linha);
        },
        $comando->fetchAll(),
    );

    $comando = $pdo->prepare(
        'SELECT grupo_id, criterio_id, aluno_avaliado_id, nota
         FROM avaliacoes_notas
         WHERE seminario_data_id = ? AND tipo_avaliador = "aluno" AND avaliador_id = ?
         ORDER BY grupo_id, criterio_id, aluno_avaliado_id'
    );
    $comando->execute([$dataId, $alunoId]);
    $notasPorGrupo = [];
    foreach ($comando->fetchAll() as $linha) {
        $grupoId = (int) $linha['grupo_id'];
        $notasPorGrupo[$grupoId][] = [
            'criterioId' => (int) $linha['criterio_id'],
            'alunoId' => $linha['aluno_avaliado_id'] === null ? null : (int) $linha['aluno_avaliado_id'],
            'nota' => (float) $linha['nota'],
        ];
    }

    $comando = $pdo->prepare(
        'SELECT grupo_id, feedback_texto
         FROM avaliacoes_feedbacks
         WHERE seminario_data_id = ? AND tipo_avaliador = "aluno" AND avaliador_id = ?
         ORDER BY grupo_id'
    );
    $comando->execute([$dataId, $alunoId]);
    $feedbacks = [];
    foreach ($comando->fetchAll() as $linha) {
        $feedbacks[(int) $linha['grupo_id']] = (string) $linha['feedback_texto'];
    }

    $minhas = [];
    foreach ($notasPorGrupo as $grupoId => $notas) {
        $minhas[] = [
            'grupoId' => $grupoId,
            'notas' => $notas,
            'feedback' => $feedbacks[$grupoId] ?? '',
        ];
    }

    return [
        'seminarioId' => $seminarioId,
        'dataId' => $dataId,
        'dataSessao' => (string) $sessao['data_apresentacao'],
        'criterios' => array_map('serializar_criterio', listar_criterios($pdo, $seminarioId)),
        'grupos' => $grupos,
        'minhasAvaliacoes' => $minhas,
    ];
}

function parsear_ids(string $bruto): array
{
    if ($bruto === '') {
        return [];
    }

    $ids = [];
    foreach (explode(',', $bruto) as $parte) {
        if (ctype_digit($parte)) {
            $ids[] = (int) $parte;
        }
    }

    return $ids;
}
