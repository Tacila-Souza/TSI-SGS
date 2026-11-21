<?php
declare(strict_types=1);

function salvar_chamada(PDO $pdo, int $professorId, int $dataId, array $dados): array
{
    $data = validar_propriedade_data($pdo, $dataId, $professorId);
    $seminario = validar_propriedade_seminario($pdo, (int) $data['seminario_id'], $professorId);

    $itens = campo_lista($dados, 'presencas');
    $registros = [];
    foreach ($itens as $item) {
        if (!is_array($item)) {
            throw new HttpErro('Cada presença deve ser um objeto.', 400);
        }
        $alunoId = campo_int($item, 'alunoId');
        if (!array_key_exists('presente', $item)) {
            throw new HttpErro('O campo presente é obrigatório.', 400);
        }
        $registros[] = [
            'alunoId' => $alunoId,
            'presente' => filter_var($item['presente'], FILTER_VALIDATE_BOOLEAN),
        ];
    }

    $alunoIds = array_map(static fn (array $r): int => $r['alunoId'], $registros);
    validar_alunos_disciplina($pdo, $alunoIds, (int) $seminario['disciplina_id']);

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare(
            'INSERT INTO presencas (seminario_data_id, aluno_id, presente)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE presente = VALUES(presente)'
        );
        foreach ($registros as $registro) {
            $stmt->execute([
                $dataId,
                $registro['alunoId'],
                $registro['presente'] ? 1 : 0,
            ]);
        }
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }

    marcar_seminario_agendado($pdo, (int) $seminario['id']);
    return listar_presencas_sessao($pdo, $dataId, $professorId);
}
