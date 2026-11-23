<?php
declare(strict_types=1);

function validar_pesos_seminario(array $dados): array
{
    $peso = filter_var($dados['pesoProfessor'] ?? null, FILTER_VALIDATE_FLOAT);
    if ($peso === false || $peso < 0 || $peso > 1) {
        throw new HttpErro('pesoProfessor deve estar entre 0 e 1.', 400, [
            'pesoProfessor' => 'Valor entre 0 e 1.',
        ]);
    }

    $professor = (int) round($peso * 100);
    return [$professor, 100 - $professor];
}

function validar_datas_seminario(array $dados): array
{
    $itens = campo_lista($dados, 'datas');
    $datas = [];
    foreach ($itens as $item) {
        if (!is_array($item)) {
            throw new HttpErro('Cada data deve ser um objeto.', 400);
        }
        $datas[] = [
            'id' => isset($item['id']) ? (int) $item['id'] : null,
            'data' => campo_data($item, 'data'),
            'horario' => campo_hora($item, 'horario'),
        ];
    }
    return $datas;
}

function validar_criterios_seminario(array $dados): array
{
    $itens = campo_lista($dados, 'criterios');
    $criterios = [];
    $total = 0;

    foreach ($itens as $item) {
        if (!is_array($item)) {
            throw new HttpErro('Cada critério deve ser um objeto.', 400);
        }
        $peso = campo_int($item, 'peso', 1);
        $total += $peso;
        $criterios[] = [
            'id' => isset($item['id']) ? (int) $item['id'] : null,
            'nome' => campo_texto($item, 'nome', 100),
            'peso' => $peso,
            'tipo' => campo_enum($item, 'tipo', ['individual', 'grupo']),
        ];
    }

    if ($total !== 100) {
        throw new HttpErro('A soma dos pesos dos critérios deve ser 100.', 400, [
            'criterios' => "Soma atual: {$total}.",
        ]);
    }

    return $criterios;
}

function criar_seminario(PDO $pdo, int $professorId, array $dados): array
{
    $disciplinaId = campo_int($dados, 'turmaId');
    validar_propriedade_disciplina($pdo, $disciplinaId, $professorId);

    $titulo = campo_texto($dados, 'titulo', 180);
    $descricao = isset($dados['descricao']) ? trim((string) $dados['descricao']) : null;
    $tempo = campo_int($dados, 'tempoApresentacaoMin', 1);
    [$pesoProfessor, $pesoAlunos] = validar_pesos_seminario($dados);
    $datas = validar_datas_seminario($dados);
    $criterios = validar_criterios_seminario($dados);

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare(
            'INSERT INTO seminarios
             (disciplina_id, professor_id, titulo, descricao, tempo_apresentacao, peso_professor, peso_alunos, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $disciplinaId,
            $professorId,
            $titulo,
            $descricao,
            $tempo,
            $pesoProfessor,
            $pesoAlunos,
            'pendente',
        ]);
        $seminarioId = (int) $pdo->lastInsertId();

        $stmtData = $pdo->prepare(
            'INSERT INTO seminario_datas (seminario_id, data_apresentacao, horario, status)
             VALUES (?, ?, ?, ?)'
        );
        foreach ($datas as $data) {
            $stmtData->execute([$seminarioId, $data['data'], $data['horario'], 'agendada']);
        }

        $stmtCriterio = $pdo->prepare(
            'INSERT INTO seminario_criterios (seminario_id, nome_criterio, peso_criterio, tipo_avaliacao)
             VALUES (?, ?, ?, ?)'
        );
        foreach ($criterios as $criterio) {
            $stmtCriterio->execute([
                $seminarioId,
                $criterio['nome'],
                $criterio['peso'],
                $criterio['tipo'],
            ]);
        }

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }

    $seminario = validar_propriedade_seminario($pdo, $seminarioId, $professorId);
    carregar_detalhes_seminario($pdo, $seminario);
    return $seminario;
}

function editar_seminario(PDO $pdo, int $professorId, int $seminarioId, array $dados): array
{
    $seminario = validar_propriedade_seminario($pdo, $seminarioId, $professorId);

    $disciplinaId = campo_int($dados, 'turmaId');
    validar_propriedade_disciplina($pdo, $disciplinaId, $professorId);

    $titulo = campo_texto($dados, 'titulo', 180);
    $descricao = isset($dados['descricao']) ? trim((string) $dados['descricao']) : null;
    $tempo = campo_int($dados, 'tempoApresentacaoMin', 1);
    [$pesoProfessor, $pesoAlunos] = validar_pesos_seminario($dados);
    $datas = validar_datas_seminario($dados);
    $criterios = validar_criterios_seminario($dados);

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare(
            'UPDATE seminarios
             SET disciplina_id = ?, titulo = ?, descricao = ?, tempo_apresentacao = ?,
                 peso_professor = ?, peso_alunos = ?
             WHERE id = ?'
        );
        $stmt->execute([
            $disciplinaId,
            $titulo,
            $descricao,
            $tempo,
            $pesoProfessor,
            $pesoAlunos,
            $seminarioId,
        ]);

        // Diff de datas por ID. Datas omitidas só são removidas se não houver histórico.
        $stmtAtuais = $pdo->prepare('SELECT id FROM seminario_datas WHERE seminario_id = ?');
        $stmtAtuais->execute([$seminarioId]);
        $idsAtuais = array_map('intval', array_column($stmtAtuais->fetchAll(), 'id'));
        $idsEnviados = array_values(array_filter(array_map(
            static fn (array $d): int => (int) ($d['id'] ?? 0),
            $datas
        )));

        foreach (array_diff($idsAtuais, $idsEnviados) as $idRemover) {
            $stmtHistorico = $pdo->prepare(
                'SELECT
                    (SELECT COUNT(*) FROM grupos WHERE seminario_data_id = ?) +
                    (SELECT COUNT(*) FROM presencas WHERE seminario_data_id = ?) +
                    (SELECT COUNT(*) FROM avaliacoes_notas WHERE seminario_data_id = ?) +
                    (SELECT COUNT(*) FROM avaliacoes_feedbacks WHERE seminario_data_id = ?) AS total'
            );
            $stmtHistorico->execute([$idRemover, $idRemover, $idRemover, $idRemover]);
            if ((int) $stmtHistorico->fetchColumn() > 0) {
                throw new HttpErro(
                    "A data {$idRemover} possui grupos, presença ou avaliações e não pode ser removida.",
                    409
                );
            }
            $stmtDel = $pdo->prepare('DELETE FROM seminario_datas WHERE id = ? AND seminario_id = ?');
            $stmtDel->execute([$idRemover, $seminarioId]);
        }

        $stmtUpData = $pdo->prepare(
            'UPDATE seminario_datas SET data_apresentacao = ?, horario = ?
             WHERE id = ? AND seminario_id = ?'
        );
        $stmtNewData = $pdo->prepare(
            'INSERT INTO seminario_datas (seminario_id, data_apresentacao, horario, status)
             VALUES (?, ?, ?, ?)'
        );
        foreach ($datas as $data) {
            if ($data['id'] !== null && in_array($data['id'], $idsAtuais, true)) {
                $stmtUpData->execute([$data['data'], $data['horario'], $data['id'], $seminarioId]);
            } else {
                $stmtNewData->execute([$seminarioId, $data['data'], $data['horario'], 'agendada']);
            }
        }

        // Diff de critérios por ID.
        $stmtAtuais = $pdo->prepare('SELECT id FROM seminario_criterios WHERE seminario_id = ?');
        $stmtAtuais->execute([$seminarioId]);
        $idsAtuais = array_map('intval', array_column($stmtAtuais->fetchAll(), 'id'));
        $idsEnviados = array_values(array_filter(array_map(
            static fn (array $c): int => (int) ($c['id'] ?? 0),
            $criterios
        )));

        foreach (array_diff($idsAtuais, $idsEnviados) as $idRemover) {
            $stmtHistorico = $pdo->prepare('SELECT COUNT(*) FROM avaliacoes_notas WHERE criterio_id = ?');
            $stmtHistorico->execute([$idRemover]);
            if ((int) $stmtHistorico->fetchColumn() > 0) {
                throw new HttpErro(
                    "O critério {$idRemover} possui notas registradas e não pode ser removido.",
                    409
                );
            }
            $stmtDel = $pdo->prepare('DELETE FROM seminario_criterios WHERE id = ? AND seminario_id = ?');
            $stmtDel->execute([$idRemover, $seminarioId]);
        }

        $stmtUpCriterio = $pdo->prepare(
            'UPDATE seminario_criterios SET nome_criterio = ?, peso_criterio = ?, tipo_avaliacao = ?
             WHERE id = ? AND seminario_id = ?'
        );
        $stmtNewCriterio = $pdo->prepare(
            'INSERT INTO seminario_criterios (seminario_id, nome_criterio, peso_criterio, tipo_avaliacao)
             VALUES (?, ?, ?, ?)'
        );
        foreach ($criterios as $criterio) {
            if ($criterio['id'] !== null && in_array($criterio['id'], $idsAtuais, true)) {
                $stmtUpCriterio->execute([
                    $criterio['nome'],
                    $criterio['peso'],
                    $criterio['tipo'],
                    $criterio['id'],
                    $seminarioId,
                ]);
            } else {
                $stmtNewCriterio->execute([
                    $seminarioId,
                    $criterio['nome'],
                    $criterio['peso'],
                    $criterio['tipo'],
                ]);
            }
        }

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }

    $seminario = validar_propriedade_seminario($pdo, $seminarioId, $professorId);
    carregar_detalhes_seminario($pdo, $seminario);
    return $seminario;
}
