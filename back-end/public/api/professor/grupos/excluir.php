<?php
declare(strict_types=1);

require dirname(__DIR__, 4) . '/src/bootstrap.php';

exigir_metodo('DELETE');

$professorId = exigir_identidade('professor');

$grupoId = query_int('id');

if ($grupoId === null) {
    responder_erro('O parâmetro id é obrigatório.', 400);
}

excluir_grupo($pdo, $professorId, $grupoId);

responder_sucesso(null, 200, 'Grupo excluído com sucesso.');
