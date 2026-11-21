<?php
declare(strict_types=1);

require dirname(__DIR__, 4) . '/src/bootstrap.php';

exigir_metodo('GET');
exigir_identidade('admin');
$id = query_int('id');

$disciplina = buscar_disciplina($pdo, $id);
if ($disciplina === null) {
    responder_erro('Disciplina não encontrada.', 404);
}

responder_sucesso(serializar_disciplina($disciplina));
