<?php
declare(strict_types=1);

require dirname(__DIR__, 4) . '/src/bootstrap.php';

exigir_metodo('GET');

$professorId = exigir_identidade('professor');
$cursoId = query_int('curso_id');

$disciplinas = listar_disciplinas_professor($pdo, $professorId, $cursoId);

responder_sucesso(array_map('serializar_disciplina', $disciplinas));
