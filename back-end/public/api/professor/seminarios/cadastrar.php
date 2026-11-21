<?php
declare(strict_types=1);

require dirname(__DIR__, 4) . '/src/bootstrap.php';

exigir_metodo('POST');

$professorId = exigir_identidade('professor');

$dados = ler_json();

$seminario = criar_seminario($pdo, $professorId, $dados);

responder_sucesso(serializar_seminario($seminario), 201);
