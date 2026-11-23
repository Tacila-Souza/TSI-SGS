<?php
declare(strict_types=1);

require dirname(__DIR__, 4) . '/src/bootstrap.php';

exigir_metodo('PUT');

$professorId = exigir_identidade('professor');
$seminarioId = query_int('id');

if ($seminarioId === null) {
    responder_erro('O parâmetro id é obrigatório.', 400);
}

$dados = ler_json();

$seminario = editar_seminario($pdo, $professorId, $seminarioId, $dados);

responder_sucesso(serializar_seminario($seminario), 200, 'Seminário atualizado com sucesso.');
