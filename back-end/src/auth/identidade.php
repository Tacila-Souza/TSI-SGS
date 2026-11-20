<?php
declare(strict_types=1);

/**
 * Identidade do MVP sem autenticação real (seção 6.3.15 / DP-002).
 * professor_id e aluno_id são obrigatórios na query string; admin não envia ID.
 */
function exigir_identidade(string $perfil): ?int
{
    if ($perfil === 'admin') {
        return null;
    }

    if ($perfil !== 'aluno' && $perfil !== 'professor') {
        falhar('Perfil desconhecido.', 500);
    }

    $chave = $perfil === 'aluno' ? 'aluno_id' : 'professor_id';
    $id = inteiro_da_query($chave);
    $tabela = $perfil === 'aluno' ? 'alunos' : 'professores';
    $rotulo = $perfil === 'aluno' ? 'Aluno' : 'Professor';

    $pdo = $GLOBALS['pdo'];
    $stmt = $pdo->prepare("SELECT id, status FROM {$tabela} WHERE id = ?");
    $stmt->execute([$id]);
    $linha = $stmt->fetch();

    if ($linha === false) {
        falhar("{$rotulo} não encontrado.", 404);
    }
    if ($linha['status'] !== 'ativo') {
        falhar("{$rotulo} inativo.", 403);
    }

    return $id;
}
