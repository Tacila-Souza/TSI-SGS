<?php
declare(strict_types=1);

/**
 * Converte uma linha agregada de disciplinas para o DisciplinaDTO (seção 7.3).
 * Não consulta o banco: os vínculos já vêm carregados pelo módulo de consultas.
 */
function serializar_disciplina(array $linha): array
{
    $alunoIds = array_map('intval', $linha['aluno_ids'] ?? []);
    $professorId = $linha['professor_id'] ?? null;
    $professorNome = $linha['professor_nome'] ?? null;

    return [
        'id' => (int) $linha['id'],
        'cursoId' => (int) $linha['curso_id'],
        'nome' => (string) $linha['nome'],
        'codigo' => (string) $linha['codigo'],
        'professorId' => $professorId === null ? null : (int) $professorId,
        'professorNome' => $professorNome === null ? null : (string) $professorNome,
        'alunoIds' => $alunoIds,
        'alunosCount' => isset($linha['alunos_count']) ? (int) $linha['alunos_count'] : count($alunoIds),
    ];
}
