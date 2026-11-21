<?php
declare(strict_types=1);

function validar_propriedade_seminario(PDO $pdo, int $seminarioId, int $professorId): array
{
    $stmt = $pdo->prepare('SELECT * FROM seminarios WHERE id = ? AND professor_id = ?');
    $stmt->execute([$seminarioId, $professorId]);
    $seminario = $stmt->fetch();
    if ($seminario === false) {
        responder_erro('Seminário não encontrado para este professor.', 404);
    }
    return $seminario;
}

function validar_propriedade_data(PDO $pdo, int $dataId, int $professorId): array
{
    $stmt = $pdo->prepare(
        'SELECT sd.* FROM seminario_datas sd
         INNER JOIN seminarios s ON s.id = sd.seminario_id
         WHERE sd.id = ? AND s.professor_id = ?'
    );
    $stmt->execute([$dataId, $professorId]);
    $data = $stmt->fetch();
    if ($data === false) {
        responder_erro('Sessão não encontrada para este professor.', 404);
    }
    return $data;
}

function validar_propriedade_grupo(PDO $pdo, int $grupoId, int $professorId): array
{
    $stmt = $pdo->prepare(
        'SELECT g.* FROM grupos g
         INNER JOIN seminario_datas sd ON sd.id = g.seminario_data_id
         INNER JOIN seminarios s ON s.id = sd.seminario_id
         WHERE g.id = ? AND s.professor_id = ?'
    );
    $stmt->execute([$grupoId, $professorId]);
    $grupo = $stmt->fetch();
    if ($grupo === false) {
        responder_erro('Grupo não encontrado para este professor.', 404);
    }
    return $grupo;
}

function validar_propriedade_disciplina(PDO $pdo, int $disciplinaId, int $professorId): array
{
    $stmt = $pdo->prepare('SELECT * FROM disciplinas WHERE id = ? AND professor_id = ?');
    $stmt->execute([$disciplinaId, $professorId]);
    $disciplina = $stmt->fetch();
    if ($disciplina === false) {
        responder_erro('Disciplina não encontrada para este professor.', 404);
    }
    return $disciplina;
}

function marcar_seminario_agendado(PDO $pdo, int $seminarioId): void
{
    $stmt = $pdo->prepare("UPDATE seminarios SET status = 'agendado' WHERE id = ? AND status = 'pendente'");
    $stmt->execute([$seminarioId]);
}

function listar_cursos_professor(PDO $pdo, int $professorId): array
{
    // O seletor de curso do formulário deve refletir todos os cursos cadastrados.
    // A validação de propriedade continua sendo feita ao criar/editar o seminário.
    $stmt = $pdo->query('SELECT id, sigla, nome, descricao FROM cursos ORDER BY nome');
    return $stmt->fetchAll();
}

function listar_disciplinas_professor(PDO $pdo, int $professorId, ?int $cursoId): array
{
    $sql = 'SELECT d.id, d.curso_id, d.nome, d.codigo, d.professor_id, p.nome_completo AS professor_nome,
                   c.sigla AS curso_sigla, c.nome AS curso_nome
            FROM disciplinas d
            INNER JOIN cursos c ON c.id = d.curso_id
            LEFT JOIN professores p ON p.id = d.professor_id
            WHERE 1 = 1';
    $params = [];
    if ($cursoId !== null) {
        $sql .= ' AND d.curso_id = ?';
        $params[] = $cursoId;
    }
    $sql .= ' ORDER BY d.nome';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function listar_alunos_disciplina(PDO $pdo, int $professorId, int $disciplinaId): array
{
    validar_propriedade_disciplina($pdo, $disciplinaId, $professorId);

    $stmt = $pdo->prepare(
        "SELECT a.id, a.nome_completo, a.email, a.matricula, a.ano_matricula, a.semestre_entrada, a.status
         FROM alunos a
         INNER JOIN disciplina_aluno da ON da.aluno_id = a.id
         WHERE da.disciplina_id = ? AND a.status = 'ativo'
         ORDER BY a.nome_completo"
    );
    $stmt->execute([$disciplinaId]);
    $linhas = $stmt->fetchAll();
    foreach ($linhas as &$linha) {
        $linha['turma_ids'] = turma_ids_aluno($pdo, (int) $linha['id']);
    }
    return $linhas;
}

function listar_seminarios_professor(PDO $pdo, int $professorId): array
{
    $stmt = $pdo->prepare('SELECT * FROM seminarios WHERE professor_id = ? ORDER BY id DESC');
    $stmt->execute([$professorId]);
    $seminarios = $stmt->fetchAll();

    foreach ($seminarios as &$seminario) {
        carregar_detalhes_seminario($pdo, $seminario);
    }
    return $seminarios;
}

function carregar_detalhes_seminario(PDO $pdo, array &$seminario): void
{
    $id = (int) $seminario['id'];

    $stmt = $pdo->prepare('SELECT nome FROM disciplinas WHERE id = ?');
    $stmt->execute([(int) $seminario['disciplina_id']]);
    $seminario['disciplina_nome'] = $stmt->fetchColumn() ?: '';

    $stmt = $pdo->prepare('SELECT id, nome_criterio, peso_criterio, tipo_avaliacao FROM seminario_criterios WHERE seminario_id = ? ORDER BY id');
    $stmt->execute([$id]);
    $seminario['criterios'] = $stmt->fetchAll();

    $stmt = $pdo->prepare('SELECT * FROM seminario_datas WHERE seminario_id = ? ORDER BY data_apresentacao, horario, id');
    $stmt->execute([$id]);
    $datas = $stmt->fetchAll();

    foreach ($datas as &$data) {
        $data['grupos'] = carregar_grupos_sessao($pdo, (int) $data['id']);
        $stmtPres = $pdo->prepare('SELECT aluno_id, presente FROM presencas WHERE seminario_data_id = ? ORDER BY aluno_id');
        $stmtPres->execute([(int) $data['id']]);
        $data['presencas'] = $stmtPres->fetchAll();
    }
    $seminario['sessoes'] = $datas;
}

function carregar_grupos_sessao(PDO $pdo, int $dataId): array
{
    $stmt = $pdo->prepare('SELECT * FROM grupos WHERE seminario_data_id = ? ORDER BY ordem_apresentacao, id');
    $stmt->execute([$dataId]);
    $grupos = $stmt->fetchAll();

    foreach ($grupos as &$grupo) {
        $stmtAlunos = $pdo->prepare('SELECT aluno_id FROM grupo_alunos WHERE grupo_id = ? ORDER BY aluno_id');
        $stmtAlunos->execute([(int) $grupo['id']]);
        $grupo['integrantes_ids'] = array_column($stmtAlunos->fetchAll(), 'aluno_id');
    }
    return $grupos;
}

function listar_grupos_sessao(PDO $pdo, int $dataId, int $professorId): array
{
    validar_propriedade_data($pdo, $dataId, $professorId);
    return carregar_grupos_sessao($pdo, $dataId);
}

function listar_presencas_sessao(PDO $pdo, int $dataId, int $professorId): array
{
    validar_propriedade_data($pdo, $dataId, $professorId);
    $stmt = $pdo->prepare(
        'SELECT a.id AS aluno_id, a.nome_completo, COALESCE(p.presente, 0) AS presente
         FROM disciplina_aluno da
         INNER JOIN alunos a ON a.id = da.aluno_id
         INNER JOIN seminario_datas sd ON sd.id = ?
         INNER JOIN seminarios s ON s.id = sd.seminario_id
         LEFT JOIN presencas p ON p.aluno_id = a.id AND p.seminario_data_id = sd.id
         WHERE da.disciplina_id = s.disciplina_id AND a.status = ?
         ORDER BY a.nome_completo'
    );
    $stmt->execute([$dataId, 'ativo']);
    return $stmt->fetchAll();
}
