<?php

declare(strict_types=1);

require_once dirname(__DIR__, 4) . '/src/bootstrap.php';

exigir_metodo('GET');

exigir_identidade('professor');

// O curso_id agora é opcional

$cursoId = null;

if (isset($_GET['curso_id'])) {

    $cursoId = filter_var(
        $_GET['curso_id'],
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );

    if ($cursoId === false) {
        responder_erro('Informe um curso válido.', 400);
    }
}

// Montar consulta

$sql = 'SELECT
            d.id,
            d.curso_id,
            d.nome,
            d.codigo,
            d.professor_id,
            p.nome_completo AS professor_nome
        FROM disciplinas d
        LEFT JOIN professores p
            ON p.id = d.professor_id';

$parametros = [];

// Filtrar por curso somente quando informado

if ($cursoId !== null) {

    $sql .= ' WHERE d.curso_id = ?';

    $parametros[] = $cursoId;
}

$sql .= ' ORDER BY d.nome ASC';

$consulta = $pdo->prepare($sql);

$consulta->execute($parametros);

$disciplinas = [];

// Preparar consulta dos alunos

$consultaAlunos = $pdo->prepare(
    'SELECT da.aluno_id
     FROM disciplina_aluno da
     INNER JOIN alunos a
        ON a.id = da.aluno_id
     WHERE da.disciplina_id = ?
       AND a.status = ?
     ORDER BY da.aluno_id'
);

foreach ($consulta->fetchAll() as $disciplina) {

    $consultaAlunos->execute([
        $disciplina['id'],
        'ativo'
    ]);

    $alunoIds = array_map(
        'intval',
        $consultaAlunos->fetchAll(PDO::FETCH_COLUMN)
    );

    $disciplinas[] = [
        'id' => (int) $disciplina['id'],
        'cursoId' => (int) $disciplina['curso_id'],
        'nome' => $disciplina['nome'],
        'codigo' => $disciplina['codigo'],
        'professorId' => $disciplina['professor_id'] !== null
            ? (int) $disciplina['professor_id']
            : null,
        'professorNome' => $disciplina['professor_nome'],
        'alunoIds' => $alunoIds,
        'alunosCount' => count($alunoIds)
    ];
}

responder_sucesso($disciplinas);