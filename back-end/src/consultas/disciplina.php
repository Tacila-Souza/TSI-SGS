<?php
declare(strict_types=1);

/**
 * Consulta compartilhada que monta o DisciplinaDTO com professor e alunos.
 * Usada por listar, detalhes e pelas operações de cadastro/edição.
 */
function listar_disciplinas_dto(PDO $pdo, ?int $cursoId): array
{
    if ($cursoId === null) {
        return consultar_disciplinas_dto($pdo, '', []);
    }

    return consultar_disciplinas_dto($pdo, 'd.curso_id = ?', [$cursoId]);
}

function carregar_disciplina_dto(PDO $pdo, int $id): ?array
{
    $linhas = consultar_disciplinas_dto($pdo, 'd.id = ?', [$id]);

    return $linhas[0] ?? null;
}

function consultar_disciplinas_dto(PDO $pdo, string $condicao, array $parametros): array
{
    $sql = 'SELECT d.id, d.curso_id, d.nome, d.codigo, d.professor_id,
                   p.nome_completo AS professor_nome,
                   COALESCE(GROUP_CONCAT(DISTINCT da.aluno_id ORDER BY da.aluno_id SEPARATOR ","), "") AS alunos,
                   COUNT(DISTINCT da.aluno_id) AS alunos_count
            FROM disciplinas d
            LEFT JOIN professores p ON p.id = d.professor_id
            LEFT JOIN disciplina_aluno da ON da.disciplina_id = d.id';

    if ($condicao !== '') {
        $sql .= ' WHERE ' . $condicao;
    }
    $sql .= ' GROUP BY d.id, d.curso_id, d.nome, d.codigo, d.professor_id, p.nome_completo
              ORDER BY d.nome, d.id';

    $comando = $pdo->prepare($sql);
    $comando->execute($parametros);
    $linhas = $comando->fetchAll();

    foreach ($linhas as &$linha) {
        $linha['aluno_ids'] = parsear_ids((string) $linha['alunos']);
        unset($linha['alunos']);
    }
    unset($linha);

    return array_map('serializar_disciplina', $linhas);
}
