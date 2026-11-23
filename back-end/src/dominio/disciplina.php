<?php
declare(strict_types=1);

/**
 * Cadastra ou edita uma disciplina e seus vínculos com alunos.
 * Duas tabelas são alteradas, portanto toda a operação é transacional.
 */
function salvar_disciplina(PDO $pdo, array $dados, ?int $id): array
{
    $cursoId = inteiro_positivo($dados['cursoId'] ?? null, 'cursoId');
    $nome = texto_obrigatorio($dados['nome'] ?? null, 'nome', 120);
    $codigo = texto_obrigatorio($dados['codigo'] ?? null, 'codigo', 20);

    $professorBruto = $dados['professorId'] ?? null;
    $professorId = $professorBruto === null ? null : inteiro_positivo($professorBruto, 'professorId');
    $alunoIds = validar_lista_ids($dados['alunoIds'] ?? null, 'alunoIds', true);

    if (!registro_existe($pdo, 'cursos', $cursoId)) {
        throw new RegraNegocioException('Curso não encontrado.', 404);
    }
    if ($professorId !== null && !registro_existe($pdo, 'professores', $professorId)) {
        throw new RegraNegocioException('Professor não encontrado.', 404);
    }
    if ($alunoIds !== [] && !todos_registros_existem($pdo, 'alunos', $alunoIds)) {
        throw new RegraNegocioException('Dados inválidos.', 400, ['alunoIds' => 'Um ou mais alunos não foram encontrados.']);
    }
    if ($id !== null && !registro_existe($pdo, 'disciplinas', $id)) {
        throw new RegraNegocioException('Disciplina não encontrada.', 404);
    }

    $sqlCodigo = 'SELECT id FROM disciplinas WHERE codigo = ?';
    $parametrosCodigo = [$codigo];
    if ($id !== null) {
        $sqlCodigo .= ' AND id <> ?';
        $parametrosCodigo[] = $id;
    }
    $comando = $pdo->prepare($sqlCodigo);
    $comando->execute($parametrosCodigo);
    if ($comando->fetchColumn() !== false) {
        throw new RegraNegocioException('Disciplina já cadastrada.', 409, ['codigo' => 'Este código já pertence a outra disciplina.']);
    }

    try {
        $pdo->beginTransaction();

        if ($id === null) {
            $comando = $pdo->prepare(
                'INSERT INTO disciplinas (curso_id, professor_id, nome, codigo) VALUES (?, ?, ?, ?)'
            );
            $comando->execute([$cursoId, $professorId, $nome, $codigo]);
            $id = (int) $pdo->lastInsertId();
        } else {
            $comando = $pdo->prepare(
                'UPDATE disciplinas SET curso_id = ?, professor_id = ?, nome = ?, codigo = ? WHERE id = ?'
            );
            $comando->execute([$cursoId, $professorId, $nome, $codigo, $id]);
        }

        $comando = $pdo->prepare('DELETE FROM disciplina_aluno WHERE disciplina_id = ?');
        $comando->execute([$id]);

        $comando = $pdo->prepare(
            'INSERT INTO disciplina_aluno (disciplina_id, aluno_id) VALUES (?, ?)'
        );
        foreach ($alunoIds as $alunoId) {
            $comando->execute([$id, $alunoId]);
        }

        $pdo->commit();
    } catch (PDOException $erro) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        if (str_starts_with((string) $erro->getCode(), '23')) {
            throw new RegraNegocioException('Disciplina já cadastrada.', 409, ['codigo' => 'Este código já pertence a outra disciplina.']);
        }
        throw $erro;
    } catch (Throwable $erro) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $erro;
    }

    $dto = carregar_disciplina_dto($pdo, $id);
    if ($dto === null) {
        throw new RegraNegocioException('Disciplina não encontrada.', 404);
    }

    return $dto;
}

function registro_existe(PDO $pdo, string $tabela, int $id): bool
{
    $permitidas = ['cursos', 'professores', 'alunos', 'disciplinas'];
    if (!in_array($tabela, $permitidas, true)) {
        throw new RegraNegocioException('Tabela inválida.', 400);
    }

    $comando = $pdo->prepare("SELECT 1 FROM {$tabela} WHERE id = ? LIMIT 1");
    $comando->execute([$id]);

    return $comando->fetchColumn() !== false;
}

function todos_registros_existem(PDO $pdo, string $tabela, array $ids): bool
{
    $marcadores = implode(',', array_fill(0, count($ids), '?'));
    $comando = $pdo->prepare("SELECT COUNT(*) FROM {$tabela} WHERE id IN ({$marcadores})");
    $comando->execute($ids);

    return (int) $comando->fetchColumn() === count($ids);
}
