<?php
declare(strict_types=1);

/**
 * Identifica o perfil da requisição no MVP.
 *
 * Admin: não exige identificador (identidade fixa u-coord).
 * Professor/aluno: exigem ?professor_id= / ?aluno_id= na query string.
 * PROFESSOR_PADRAO e ALUNO_PADRAO ficam disponíveis para o frontend,
 * mas o parâmetro continua obrigatório conforme o contrato.
 */
function exigir_identidade(string $perfil): ?int
{
    if ($perfil === 'admin') {
        return null;
    }

    if (!in_array($perfil, ['professor', 'aluno'], true)) {
        responder_erro('Perfil desconhecido.', 500);
    }

    $chave = $perfil === 'professor' ? 'professor_id' : 'aluno_id';
    $id = query_int($chave);

    if ($id === null) {
        responder_erro("Parâmetro '{$chave}' é obrigatório.", 400, [
            $chave => 'Informe o identificador do perfil.',
        ]);
    }

    global $pdo;

    $tabela = $perfil === 'professor' ? 'professores' : 'alunos';
    $stmt = $pdo->prepare("SELECT status FROM {$tabela} WHERE id = ?");
    $stmt->execute([$id]);
    $registro = $stmt->fetch();

    if ($registro === false) {
        responder_erro('Identidade informada não encontrada.', 404);
    }

    if ($registro['status'] !== 'ativo') {
        responder_erro('Identidade informada está inativa.', 403);
    }

    return $id;
}
