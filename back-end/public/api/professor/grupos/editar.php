<?php
declare(strict_types=1);

require dirname(__DIR__, 4) . '/src/bootstrap.php';

exigir_metodo('PUT');

$professorId = exigir_identidade('professor');

$grupoId = query_int('id');

if ($grupoId === null) {
    responder_erro('O parâmetro id é obrigatório.', 400);
}

$dados = ler_json();

$grupo = editar_grupo($pdo, $professorId, $grupoId, $dados);

responder_sucesso(serializar_grupo($grupo));
