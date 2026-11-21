<?php

declare(strict_types=1);

function criar_seminario(
    PDO $pdo,
    int $professorId,
    array $dados
): array {

    // 1. Validar informações principais

    $disciplinaId = validar_inteiro_positivo(
        $dados['turmaId'] ?? null,
        'turmaId'
    );

    $titulo = validar_texto(
        $dados['titulo'] ?? null,
        'titulo',
        180
    );

    $descricao = $dados['descricao'] ?? '';

    if (!is_string($descricao)) {
        responder_erro('Descrição inválida.', 400);
    }

    $tempo = validar_inteiro_positivo(
        $dados['tempoApresentacaoMin'] ?? null,
        'tempoApresentacaoMin'
    );

    // 2. Validar composição das notas

    $peso = $dados['pesoProfessor'] ?? null;

    if (
        (!is_int($peso) && !is_float($peso))
        || $peso < 0
        || $peso > 1
    ) {
        responder_erro(
            'O peso do professor deve estar entre 0 e 1.',
            400
        );
    }

    $pesoProfessor = (int) round($peso * 100);
    $pesoAlunos = 100 - $pesoProfessor;

    // 3. Validar listas

    $datas = validar_lista(
        $dados['datas'] ?? null,
        'datas'
    );

    $criterios = validar_lista(
        $dados['criterios'] ?? null,
        'criterios'
    );

    // 4. Validar datas e horários

    foreach ($datas as $data) {

        if (!is_array($data)) {
            responder_erro('Data inválida.', 400);
        }

        $dia = $data['data'] ?? '';
        $horario = $data['horario'] ?? '';

        if (
            !is_string($dia)
            || !preg_match('/^\d{4}-\d{2}-\d{2}$/D', $dia)
        ) {
            responder_erro('Formato de data inválido.', 400);
        }

        [$ano, $mes, $diaMes] = array_map(
            'intval',
            explode('-', $dia)
        );

        if (!checkdate($mes, $diaMes, $ano)) {
            responder_erro('Data inexistente.', 400);
        }

        if (
            !is_string($horario)
            || !preg_match(
                '/^([01]\d|2[0-3]):[0-5]\d$/D',
                $horario
            )
        ) {
            responder_erro('Horário inválido.', 400);
        }
    }

    // 5. Validar critérios

    $somaPesos = 0;
    $criteriosValidados = [];

    foreach ($criterios as $criterio) {

        if (!is_array($criterio)) {
            responder_erro('Critério inválido.', 400);
        }

        $nome = validar_texto(
            $criterio['nome'] ?? null,
            'nome do critério',
            100
        );

        $pesoCriterio = validar_inteiro_positivo(
            $criterio['peso'] ?? null,
            'peso do critério'
        );

        if ($pesoCriterio > 100) {
            responder_erro('Peso do critério inválido.', 400);
        }

        $tipo = validar_opcao(
            $criterio['tipo'] ?? null,
            'tipo',
            ['individual', 'grupo']
        );

        $somaPesos += $pesoCriterio;

        $criteriosValidados[] = [
            'nome' => $nome,
            'peso' => $pesoCriterio,
            'tipo' => $tipo
        ];
    }

    if ($somaPesos !== 100) {
        responder_erro(
            'A soma dos pesos dos critérios deve ser 100%.',
            400
        );
    }

    // 6. Verificar disciplina

    $consulta = $pdo->prepare(
        'SELECT id, nome, professor_id
         FROM disciplinas
         WHERE id = ?'
    );

    $consulta->execute([$disciplinaId]);

    $disciplina = $consulta->fetch();

    if (!$disciplina) {
        responder_erro('Disciplina não encontrada.', 404);
    }

    if ((int) $disciplina['professor_id'] !== $professorId) {
        responder_erro(
            'Esta disciplina não pertence ao professor.',
            403
        );
    }

    // 7. Iniciar transação

    $pdo->beginTransaction();

    try {

        // Inserir seminário

        $consulta = $pdo->prepare(
            'INSERT INTO seminarios (
                disciplina_id,
                professor_id,
                titulo,
                descricao,
                tempo_apresentacao,
                peso_professor,
                peso_alunos,
                status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );

        $consulta->execute([
            $disciplinaId,
            $professorId,
            $titulo,
            $descricao,
            $tempo,
            $pesoProfessor,
            $pesoAlunos,
            'pendente'
        ]);

        $seminarioId = (int) $pdo->lastInsertId();

        // Inserir datas

        $consultaData = $pdo->prepare(
            'INSERT INTO seminario_datas (
                seminario_id,
                data_apresentacao,
                horario,
                status
            ) VALUES (?, ?, ?, ?)'
        );

        $sessoes = [];

        foreach ($datas as $data) {

            $consultaData->execute([
                $seminarioId,
                $data['data'],
                $data['horario'],
                'agendada'
            ]);

            $sessoes[] = [
                'id' => (int) $pdo->lastInsertId(),
                'data' => $data['data'],
                'horario' => $data['horario'],
                'grupos' => [],
                'presenca' => [],
                'chamadaRealizada' => false,
                'status' => 'agendado'
            ];
        }

        // Inserir critérios

        $consultaCriterio = $pdo->prepare(
            'INSERT INTO seminario_criterios (
                seminario_id,
                nome_criterio,
                peso_criterio,
                tipo_avaliacao
            ) VALUES (?, ?, ?, ?)'
        );

        $criteriosCriados = [];

        foreach ($criteriosValidados as $criterio) {

            $consultaCriterio->execute([
                $seminarioId,
                $criterio['nome'],
                $criterio['peso'],
                $criterio['tipo']
            ]);

            $criteriosCriados[] = [
                'id' => (int) $pdo->lastInsertId(),
                'nome' => $criterio['nome'],
                'peso' => $criterio['peso'],
                'tipo' => $criterio['tipo']
            ];
        }

        $pdo->commit();

        // Retornar seminário criado

        return [
            'id' => $seminarioId,
            'titulo' => $titulo,
            'descricao' => $descricao,
            'disciplina' => $disciplina['nome'],
            'turmaId' => $disciplinaId,
            'professorId' => $professorId,
            'data' => $sessoes[0]['data'],
            'horario' => $sessoes[0]['horario'],
            'tempoApresentacaoMin' => $tempo,
            'pesoProfessor' => $pesoProfessor / 100,
            'status' => 'rascunho',
            'criterios' => $criteriosCriados,
            'grupos' => [],
            'presenca' => [],
            'sessoes' => $sessoes
        ];

    } catch (Throwable $erro) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        throw $erro;
    }
}