<?php

declare(strict_types=1);

require_once dirname(__DIR__, 4) . '/src/bootstrap.php';

exigir_metodo('GET');

$professorId = exigir_identidade('professor');

// 1. Validar ID da disciplina

$disciplinaId = filter_var(
    $_GET['disciplina_id'] ?? null,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

if ($disciplinaId === false) {
    responder_erro('Informe uma disciplina válida.', 400);
}

// 2. Verificar se a disciplina existe

$consulta = $pdo->prepare(
    'SELECT id, professor_id
     FROM disciplinas
     WHERE id = ?'
);

$consulta->execute([$disciplinaId]);

$disciplina = $consulta->fetch();

if (!$disciplina) {
    responder_erro('Disciplina não encontrada.', 404);
}

// 3. Verificar propriedade da disciplina

if ((int) $disciplina['professor_id'] !== $professorId) {
    responder_erro(
        'Você não possui acesso a esta disciplina.',
        403
    );
}

// 4. Buscar alunos ativos matriculados

$consulta = $pdo->prepare(
    'SELECT
        a.id,
        a.nome_completo,
        a.email,
        a.matricula,
        a.ano_matricula,
        a.semestre_entrada,
        a.status
     FROM alunos a
     INNER JOIN disciplina_aluno da
        ON da.aluno_id = a.id
     WHERE da.disciplina_id = ?
       AND a.status = ?
     ORDER BY a.nome_completo ASC'
);

$consulta->execute([
    $disciplinaId,
    'ativo'
]);

// 5. Organizar resposta

$alunos = [];

foreach ($consulta->fetchAll() as $aluno) {

   $alunos[] = [
    'id' => (int) $aluno['id'],
    'nome' => $aluno['nome_completo'],
    'email' => $aluno['email'],
    'role' => 'aluno',
    'registro' => $aluno['matricula'],
    'turmaIds' => [$disciplinaId],
    'anoMatricula' => (int) $aluno['ano_matricula'],
    'semestreEntrada' => (string) $aluno['semestre_entrada'],
    'status' => $aluno['status']
];
}

responder_sucesso($alunos);