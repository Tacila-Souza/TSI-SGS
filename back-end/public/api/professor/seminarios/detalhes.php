<?php
declare(strict_types=1);

require dirname(__DIR__, 4) . '/src/bootstrap.php';

exigir_metodo('GET');

$professorId = exigir_identidade('professor');
$seminarioId = query_int('id');

if ($seminarioId === null) {
    responder_erro('O parâmetro id é obrigatório.', 400);
}

$seminario = validar_propriedade_seminario($pdo, $seminarioId, $professorId);
carregar_detalhes_seminario($pdo, $seminario);

responder_sucesso(serializar_seminario($seminario));
