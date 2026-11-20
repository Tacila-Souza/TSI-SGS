<?php
declare(strict_types=1);

/**
 * Lista alunos ou professores com busca e filtro de situação.
 */
function listar_usuarios(PDO $pdo, string $perfil, string $busca, bool $incluirInativos): array
{
    if ($perfil === 'aluno') {
        $sql = 'SELECT id, nome_completo, email, matricula, ano_matricula, semestre_entrada, status
                FROM alunos';
        $camposBusca = ['nome_completo', 'matricula', 'email'];
    } elseif ($perfil === 'professor') {
        $sql = 'SELECT id, nome_completo, email, registro, status
                FROM professores';
        $camposBusca = ['nome_completo', 'registro', 'email'];
    } else {
        throw new RegraNegocioException('Perfil inválido.', 400);
    }

    $condicoes = [];
    $parametros = [];

    if (!$incluirInativos) {
        $condicoes[] = 'status = "ativo"';
    }
    if ($busca !== '') {
        $partes = [];
        foreach ($camposBusca as $campo) {
            $partes[] = "{$campo} LIKE ?";
            $parametros[] = "%{$busca}%";
        }
        $condicoes[] = '(' . implode(' OR ', $partes) . ')';
    }

    if ($condicoes !== []) {
        $sql .= ' WHERE ' . implode(' AND ', $condicoes);
    }
    $sql .= ' ORDER BY nome_completo, id';

    $comando = $pdo->prepare($sql);
    $comando->execute($parametros);

    return $comando->fetchAll();
}

/**
 * IDs das disciplinas/turmas vinculadas ao usuário.
 */
function turmas_do_usuario(PDO $pdo, string $perfil, int $id): array
{
    if ($perfil === 'aluno') {
        $sql = 'SELECT disciplina_id FROM disciplina_aluno WHERE aluno_id = ?';
    } elseif ($perfil === 'professor') {
        $sql = 'SELECT id FROM disciplinas WHERE professor_id = ?';
    } else {
        throw new RegraNegocioException('Perfil inválido.', 400);
    }

    $comando = $pdo->prepare($sql);
    $comando->execute([$id]);

    return array_map('intval', $comando->fetchAll(PDO::FETCH_COLUMN));
}

/**
 * Carrega um usuário já no formato UsuarioDTO, incluindo os vínculos de turma.
 */
function carregar_usuario_dto(PDO $pdo, string $perfil, int $id): ?array
{
    if ($perfil === 'aluno') {
        $sql = 'SELECT id, nome_completo, email, matricula, ano_matricula, semestre_entrada, status
                FROM alunos WHERE id = ? LIMIT 1';
    } elseif ($perfil === 'professor') {
        $sql = 'SELECT id, nome_completo, email, registro, status
                FROM professores WHERE id = ? LIMIT 1';
    } else {
        throw new RegraNegocioException('Perfil inválido.', 400);
    }

    $comando = $pdo->prepare($sql);
    $comando->execute([$id]);
    $linha = $comando->fetch();
    if ($linha === false) {
        return null;
    }

    return serializar_usuario($linha, turmas_do_usuario($pdo, $perfil, $id), $perfil);
}
