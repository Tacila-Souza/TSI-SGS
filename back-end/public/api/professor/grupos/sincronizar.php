<?php

declare(strict_types=1);

require_once dirname(__DIR__, 4) . '/src/bootstrap.php';

require_once dirname(__DIR__, 4)
    . '/src/dominio/grupos.php';

exigir_metodo('PUT');

$professorId = exigir_identidade('professor');

$dataId = filter_var(
    $_GET['data_id'] ?? null,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

if ($dataId === false) {
    responder_erro('Informe uma sessão válida.', 400);
}

$dados = ler_json();

$grupos = sincronizar_grupos(
    $pdo,
    $professorId,
    $dataId,
    $dados
);

responder_sucesso(
    $grupos,
    200,
    'Grupos atualizados com sucesso!'
);