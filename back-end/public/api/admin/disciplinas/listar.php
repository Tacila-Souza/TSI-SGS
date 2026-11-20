<?php
declare(strict_types=1);

require dirname(__DIR__, 4) . '/src/bootstrap.php';

exigir_metodo('GET');
exigir_identidade('admin');
$cursoId = parametro_inteiro_opcional('curso_id');

responder_sucesso(listar_disciplinas_dto($pdo, $cursoId));
