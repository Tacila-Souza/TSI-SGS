<?php
declare(strict_types=1);

/**
 * Converte uma linha de alunos/professores para o UsuarioDTO do contrato.
 * Não consulta o banco: as turmas são passadas por quem carregou os dados.
 */
function serializar_usuario(array $linha, array $turmaIds, string $perfil): array
{
    $dto = [
        'id' => (int) $linha['id'],
        'nome' => (string) $linha['nome_completo'],
        'email' => (string) $linha['email'],
        'role' => $perfil,
        'status' => (string) $linha['status'],
        'registro' => (string) ($linha['matricula'] ?? $linha['registro'] ?? ''),
        'turmaIds' => array_map('intval', $turmaIds),
    ];

    if ($perfil === 'aluno') {
        $dto['anoMatricula'] = (int) $linha['ano_matricula'];
        $dto['semestreEntrada'] = (string) $linha['semestre_entrada'];
    }

    return $dto;
}
