<?php
declare(strict_types=1);

require dirname(__DIR__, 4) . '/src/bootstrap.php';

exigir_metodo('GET');

$professorId = exigir_identidade('professor');

$cursos = listar_cursos_professor($pdo, $professorId);

responder_sucesso(array_map('serializar_curso', $cursos));
