<?php
declare(strict_types=1);

require dirname(__DIR__, 4) . '/src/bootstrap.php';

exigir_metodo('GET');

$professorId = exigir_identidade('professor');
$disciplinaId = query_int('disciplina_id');

if ($disciplinaId === null) {
    responder_erro(
        'O parâmetro disciplina_id é obrigatório.',
        400
    );
}

$alunos = listar_alunos_disciplina(
    $pdo,
    $professorId,
    $disciplinaId
);

responder_sucesso(
    array_map('serializar_aluno', $alunos)
);
