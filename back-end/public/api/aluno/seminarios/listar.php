<?php
declare(strict_types=1);

require dirname(__DIR__, 4) . '/src/bootstrap.php';

exigir_metodo('GET');
$alunoId = exigir_identidade('aluno');

$seminarios = [];
foreach (listar_seminarios_do_aluno($pdo, $alunoId) as $linha) {
    $seminarios[] = carregar_seminario_completo($pdo, (int) $linha['id']);
}

responder_sucesso($seminarios);
