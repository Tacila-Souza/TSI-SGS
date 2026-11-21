<?php
declare(strict_types=1);

require dirname(__DIR__, 4) . '/src/bootstrap.php';

exigir_metodo('GET');
exigir_identidade('admin');

$cursoId = query_int('curso_id');
$linhas = listar_disciplinas($pdo, $cursoId);
responder_sucesso(array_map('serializar_disciplina', $linhas));
