<?php
declare(strict_types=1);

/**
 * Consultas reutilizáveis do perfil aluno (listagem, validação de escopo e formulário).
 */

function buscar_seminarios_matriculados(PDO $pdo, int $alunoId): array
{
    $sql = 'SELECT s.id, s.titulo, s.descricao, s.disciplina_id, s.professor_id,
                   s.tempo_apresentacao, s.peso_professor, s.status,
                   d.nome AS disciplina_nome
            FROM seminarios s
            JOIN disciplina_aluno da ON da.disciplina_id = s.disciplina_id AND da.aluno_id = ?
            JOIN disciplinas d ON d.id = s.disciplina_id
            ORDER BY s.id';

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$alunoId]);
    return $stmt->fetchAll();
}

function buscar_datas_seminario(PDO $pdo, int $seminarioId): array
{
    $sql = 'SELECT id, data_apresentacao, horario, status
            FROM seminario_datas
            WHERE seminario_id = ? AND status <> \'cancelada\'
            ORDER BY data_apresentacao, horario, id';

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$seminarioId]);
    return $stmt->fetchAll();
}

function buscar_criterios_seminario(PDO $pdo, int $seminarioId): array
{
    $stmt = $pdo->prepare(
        'SELECT id, nome_criterio, peso_criterio, tipo_avaliacao
         FROM seminario_criterios WHERE seminario_id = ? ORDER BY id'
    );
    $stmt->execute([$seminarioId]);
    return $stmt->fetchAll();
}

function buscar_grupos_da_data(PDO $pdo, int $dataId): array
{
    $sql = 'SELECT g.id, g.tema, g.ordem_apresentacao, g.status,
                   COALESCE(GROUP_CONCAT(ga.aluno_id ORDER BY ga.aluno_id SEPARATOR \',\'), \'\') AS integrantes_ids
            FROM grupos g
            LEFT JOIN grupo_alunos ga ON ga.grupo_id = g.id
            WHERE g.seminario_data_id = ?
            GROUP BY g.id, g.tema, g.ordem_apresentacao, g.status
            ORDER BY g.ordem_apresentacao IS NULL, g.ordem_apresentacao, g.id';

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$dataId]);
    return $stmt->fetchAll();
}

function buscar_presenca_da_data(PDO $pdo, int $dataId): array
{
    $stmt = $pdo->prepare('SELECT aluno_id, presente FROM presencas WHERE seminario_data_id = ? ORDER BY aluno_id');
    $stmt->execute([$dataId]);
    return $stmt->fetchAll();
}

function buscar_sessao_por_id(PDO $pdo, int $dataId): ?array
{
    $sql = 'SELECT sd.id, sd.seminario_id, sd.data_apresentacao, sd.horario, sd.status,
                   s.titulo, s.professor_id, s.disciplina_id, s.tempo_apresentacao, s.peso_professor,
                   d.nome AS disciplina_nome
            FROM seminario_datas sd
            JOIN seminarios s ON s.id = sd.seminario_id
            JOIN disciplinas d ON d.id = s.disciplina_id
            WHERE sd.id = ?';

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$dataId]);
    $linha = $stmt->fetch();
    return $linha === false ? null : $linha;
}

function aluno_matriculado_no_seminario(PDO $pdo, int $alunoId, int $seminarioId): bool
{
    $sql = 'SELECT 1
            FROM seminarios s
            JOIN disciplina_aluno da ON da.disciplina_id = s.disciplina_id AND da.aluno_id = ?
            WHERE s.id = ? LIMIT 1';

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$alunoId, $seminarioId]);
    return $stmt->fetchColumn() !== false;
}

function buscar_grupo_da_sessao(PDO $pdo, int $grupoId, int $dataId): ?array
{
    $sql = 'SELECT g.id, g.tema, g.status, g.ordem_apresentacao,
                   COALESCE(GROUP_CONCAT(ga.aluno_id ORDER BY ga.aluno_id SEPARATOR \',\'), \'\') AS integrantes_ids
            FROM grupos g
            LEFT JOIN grupo_alunos ga ON ga.grupo_id = g.id
            WHERE g.id = ? AND g.seminario_data_id = ?
            GROUP BY g.id, g.tema, g.status, g.ordem_apresentacao';

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$grupoId, $dataId]);
    $linha = $stmt->fetch();
    return $linha === false ? null : $linha;
}

function aluno_presente_na_sessao(PDO $pdo, int $alunoId, int $dataId): bool
{
    $stmt = $pdo->prepare('SELECT presente FROM presencas WHERE seminario_data_id = ? AND aluno_id = ?');
    $stmt->execute([$dataId, $alunoId]);
    $presente = $stmt->fetchColumn();
    return $presente !== false && (bool) $presente;
}

function buscar_minhas_avaliacoes(PDO $pdo, int $alunoId, int $dataId): array
{
    $sql = 'SELECT grupo_id, criterio_id, aluno_avaliado_id, nota
            FROM avaliacoes_notas
            WHERE seminario_data_id = ? AND tipo_avaliador = \'aluno\' AND avaliador_id = ?
            ORDER BY grupo_id, criterio_id, aluno_avaliado_id';

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$dataId, $alunoId]);
    $notas = $stmt->fetchAll();

    $sqlFeedback = 'SELECT grupo_id, feedback_texto
                    FROM avaliacoes_feedbacks
                    WHERE seminario_data_id = ? AND tipo_avaliador = \'aluno\' AND avaliador_id = ?';
    $stmtFeedback = $pdo->prepare($sqlFeedback);
    $stmtFeedback->execute([$dataId, $alunoId]);
    $feedbacks = $stmtFeedback->fetchAll();

    $agrupado = [];
    foreach ($notas as $nota) {
        $grupoId = (int) $nota['grupo_id'];
        $agrupado[$grupoId] ??= ['grupoId' => $grupoId, 'notas' => [], 'feedback' => ''];
        $agrupado[$grupoId]['notas'][] = $nota;
    }
    foreach ($feedbacks as $feedback) {
        $grupoId = (int) $feedback['grupo_id'];
        $agrupado[$grupoId] ??= ['grupoId' => $grupoId, 'notas' => [], 'feedback' => ''];
        $agrupado[$grupoId]['feedback'] = (string) $feedback['feedback_texto'];
    }

    return array_values($agrupado);
}

/**
 * Monta o FormularioAvaliacaoAlunoDTO (contrato, seção 7.12).
 * Grupos elegíveis: aluno não integrante, grupo não avaliado e com integrantes.
 */
function carregar_formulario_aluno(PDO $pdo, int $alunoId, int $dataId): array
{
    $sessao = buscar_sessao_por_id($pdo, $dataId);
    if ($sessao === null) {
        falhar('Sessão não encontrada.', 404);
    }

    $criterios = buscar_criterios_seminario($pdo, (int) $sessao['seminario_id']);
    $grupos = [];
    foreach (buscar_grupos_da_data($pdo, $dataId) as $grupo) {
        $integrantes = ids_da_coluna($grupo['integrantes_ids'] ?? null);
        if ($integrantes === [] || in_array($alunoId, $integrantes, true)) {
            continue;
        }
        if (($grupo['status'] ?? '') === 'avaliado') {
            continue;
        }
        $grupos[] = $grupo;
    }

    return serializar_formulario_aluno(
        $sessao,
        $criterios,
        $grupos,
        buscar_minhas_avaliacoes($pdo, $alunoId, $dataId)
    );
}
