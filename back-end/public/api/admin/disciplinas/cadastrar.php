<?php
declare(strict_types=1);

require dirname(__DIR__, 4) . '/src/bootstrap.php';

exigir_metodo('POST');
exigir_identidade('admin');
$dados = ler_json();

$cursoId = campo_int($dados, 'cursoId');
$nome = campo_texto($dados, 'nome', 120);
$codigo = strtoupper(campo_texto($dados, 'codigo', 20));
$professorId = isset($dados['professorId']) && $dados['professorId'] !== ''
    ? (int) $dados['professorId']
    : null;
$alunoIds = campo_ids_opcional($dados, 'alunoIds');

$stmt = $pdo->prepare('SELECT COUNT(*) FROM cursos WHERE id = ?');
$stmt->execute([$cursoId]);
if ((int) $stmt->fetchColumn() === 0) {
    throw new HttpErro('Curso informado não existe.', 400, ['cursoId' => 'Curso inexistente.']);
}

if ($professorId !== null) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM professores WHERE id = ? AND status = 'ativo'");
    $stmt->execute([$professorId]);
    if ((int) $stmt->fetchColumn() === 0) {
        throw new HttpErro('Professor informado não existe ou está inativo.', 400, [
            'professorId' => 'Professor inválido.',
        ]);
    }
}

$stmt = $pdo->prepare('SELECT COUNT(*) FROM disciplinas WHERE codigo = ?');
$stmt->execute([$codigo]);
if ((int) $stmt->fetchColumn() > 0) {
    throw new HttpErro('Código de disciplina já cadastrado.', 409, [
        'codigo' => 'Valor já existente.',
    ]);
}

if ($alunoIds !== []) {
    $marcadores = implode(',', array_fill(0, count($alunoIds), '?'));
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM alunos WHERE status = 'ativo' AND id IN ({$marcadores})");
    $stmt->execute($alunoIds);
    if ((int) $stmt->fetchColumn() !== count($alunoIds)) {
        throw new HttpErro('Um ou mais alunos informados não existem ou estão inativos.', 400, [
            'alunoIds' => 'IDs inválidos.',
        ]);
    }
}

$pdo->beginTransaction();
try {
    $stmt = $pdo->prepare(
        'INSERT INTO disciplinas (curso_id, professor_id, nome, codigo) VALUES (?, ?, ?, ?)'
    );
    $stmt->execute([$cursoId, $professorId, $nome, $codigo]);
    $id = (int) $pdo->lastInsertId();

    $stmtVinculo = $pdo->prepare(
        'INSERT INTO disciplina_aluno (disciplina_id, aluno_id) VALUES (?, ?)'
    );
    foreach ($alunoIds as $alunoId) {
        $stmtVinculo->execute([$id, $alunoId]);
    }

    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    throw $e;
}

responder_sucesso(
    serializar_disciplina(buscar_disciplina($pdo, $id)),
    201,
    'Disciplina cadastrada com sucesso.'
);
