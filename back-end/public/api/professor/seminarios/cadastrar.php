<?php

declare(strict_types=1);

require_once dirname(__DIR__, 4) . '/src/bootstrap.php';

require_once dirname(__DIR__, 4)
    . '/src/dominio/seminarios.php';

exigir_metodo('POST');

$professorId = exigir_identidade('professor');

$dados = ler_json();

$seminario = criar_seminario(
    $pdo,
    $professorId,
    $dados
);

responder_sucesso(
    $seminario,
    201,
    'Seminário cadastrado com sucesso!'
);