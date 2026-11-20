<?php
declare(strict_types=1);

require dirname(__DIR__, 4) . '/src/bootstrap.php';

exigir_metodo('GET');
exigir_identidade('admin');
$id = parametro_inteiro('id');

$dto = carregar_disciplina_dto($pdo, $id);
if ($dto === null) {
    throw new RegraNegocioException('Disciplina não encontrada.', 404);
}

responder_sucesso($dto);
