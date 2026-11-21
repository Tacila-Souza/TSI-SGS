<?php
declare(strict_types=1);

function turma_ids_aluno(PDO $pdo, int $alunoId): array
{
    $stmt = $pdo->prepare('SELECT disciplina_id FROM disciplina_aluno WHERE aluno_id = ? ORDER BY disciplina_id');
    $stmt->execute([$alunoId]);
    return array_column($stmt->fetchAll(), 'disciplina_id');
}

function turma_ids_professor(PDO $pdo, int $professorId): array
{
    $stmt = $pdo->prepare('SELECT id FROM disciplinas WHERE professor_id = ? ORDER BY id');
    $stmt->execute([$professorId]);
    return array_column($stmt->fetchAll(), 'id');
}

function listar_alunos(PDO $pdo, string $busca, bool $incluirInativos): array
{
    $sql = 'SELECT id, nome_completo, email, matricula, ano_matricula, semestre_entrada, status
            FROM alunos';
    $params = [];
    $condicoes = [];

    if (!$incluirInativos) {
        $condicoes[] = "status = 'ativo'";
    }
    if ($busca !== '') {
        $condicoes[] = '(nome_completo LIKE ? OR matricula LIKE ?)';
        $params[] = "%{$busca}%";
        $params[] = "%{$busca}%";
    }
    if ($condicoes !== []) {
        $sql .= ' WHERE ' . implode(' AND ', $condicoes);
    }
    $sql .= ' ORDER BY nome_completo';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $linhas = $stmt->fetchAll();
    foreach ($linhas as &$linha) {
        $linha['turma_ids'] = turma_ids_aluno($pdo, (int) $linha['id']);
    }
    return $linhas;
}

function listar_professores(PDO $pdo, string $busca, bool $incluirInativos): array
{
    $sql = 'SELECT id, nome_completo, email, registro, status FROM professores';
    $params = [];
    $condicoes = [];

    if (!$incluirInativos) {
        $condicoes[] = "status = 'ativo'";
    }
    if ($busca !== '') {
        $condicoes[] = '(nome_completo LIKE ? OR registro LIKE ?)';
        $params[] = "%{$busca}%";
        $params[] = "%{$busca}%";
    }
    if ($condicoes !== []) {
        $sql .= ' WHERE ' . implode(' AND ', $condicoes);
    }
    $sql .= ' ORDER BY nome_completo';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $linhas = $stmt->fetchAll();
    foreach ($linhas as &$linha) {
        $linha['turma_ids'] = turma_ids_professor($pdo, (int) $linha['id']);
    }
    return $linhas;
}

function buscar_aluno(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM alunos WHERE id = ?');
    $stmt->execute([$id]);
    $linha = $stmt->fetch();
    if ($linha === false) {
        return null;
    }
    $linha['turma_ids'] = turma_ids_aluno($pdo, $id);
    return $linha;
}

function buscar_professor(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM professores WHERE id = ?');
    $stmt->execute([$id]);
    $linha = $stmt->fetch();
    if ($linha === false) {
        return null;
    }
    $linha['turma_ids'] = turma_ids_professor($pdo, $id);
    return $linha;
}

function listar_cursos(PDO $pdo): array
{
    $stmt = $pdo->query('SELECT id, sigla, nome, descricao FROM cursos ORDER BY nome');
    return $stmt->fetchAll();
}

function listar_disciplinas(PDO $pdo, ?int $cursoId): array
{
    $sql = 'SELECT d.id, d.curso_id, d.nome, d.codigo, d.professor_id, p.nome_completo AS professor_nome
            FROM disciplinas d
            LEFT JOIN professores p ON p.id = d.professor_id';
    $params = [];
    if ($cursoId !== null) {
        $sql .= ' WHERE d.curso_id = ?';
        $params[] = $cursoId;
    }
    $sql .= ' ORDER BY d.nome';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $linhas = $stmt->fetchAll();

    foreach ($linhas as &$linha) {
        $stmtAlunos = $pdo->prepare('SELECT aluno_id FROM disciplina_aluno WHERE disciplina_id = ? ORDER BY aluno_id');
        $stmtAlunos->execute([(int) $linha['id']]);
        $linha['aluno_ids'] = array_column($stmtAlunos->fetchAll(), 'aluno_id');
        $linha['alunos_count'] = count($linha['aluno_ids']);
    }
    return $linhas;
}

function buscar_disciplina(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare(
        'SELECT d.id, d.curso_id, d.nome, d.codigo, d.professor_id, p.nome_completo AS professor_nome
         FROM disciplinas d
         LEFT JOIN professores p ON p.id = d.professor_id
         WHERE d.id = ?'
    );
    $stmt->execute([$id]);
    $linha = $stmt->fetch();
    if ($linha === false) {
        return null;
    }
    $stmtAlunos = $pdo->prepare('SELECT aluno_id FROM disciplina_aluno WHERE disciplina_id = ? ORDER BY aluno_id');
    $stmtAlunos->execute([$id]);
    $linha['aluno_ids'] = array_column($stmtAlunos->fetchAll(), 'aluno_id');
    $linha['alunos_count'] = count($linha['aluno_ids']);
    return $linha;
}
