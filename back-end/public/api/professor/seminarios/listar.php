<?php
declare(strict_types=1);

require dirname(__DIR__, 4) . '/src/bootstrap.php';

exigir_metodo('GET');

$professorId = exigir_identidade('professor');

$seminarios = listar_seminarios_professor($pdo, $professorId);

responder_sucesso(array_map('serializar_seminario', $seminarios));
