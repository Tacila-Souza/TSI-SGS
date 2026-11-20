<?php
declare(strict_types=1);

/**
 * Identidade do MVP: ?aluno_id= ou ?professor_id= na query string.
 * Confere apenas existência e situação ativa do registro.
 */
function exigir_identidade(string $perfil): int
{
    global $pdo;

    // A coordenação é uma identidade fixa do frontend (u-coord), sem registro no banco.
    if ($perfil === 'admin') {
        return 0;
    }

    $chave = $perfil === 'professor' ? 'professor_id' : 'aluno_id';
    $id = parametro_inteiro($chave);

    $sql = match ($perfil) {
        'aluno' => 'SELECT id FROM alunos WHERE id = ? AND status = "ativo"',
        'professor' => 'SELECT id FROM professores WHERE id = ? AND status = "ativo"',
        default => throw new RegraNegocioException('Perfil inválido.', 400),
    };

    $comando = $pdo->prepare($sql);
    $comando->execute([$id]);

    if ($comando->fetchColumn() === false) {
        throw new RegraNegocioException('Usuário não encontrado ou inativo.', 403);
    }

    return $id;
}
