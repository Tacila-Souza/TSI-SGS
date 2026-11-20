<?php
declare(strict_types=1);

require dirname(__DIR__, 4) . '/src/bootstrap.php';

exigir_metodo('PUT');
exigir_identidade('admin');
$id = parametro_inteiro('id');
$dados = ler_json();

responder_sucesso(salvar_disciplina($pdo, $dados, $id));
