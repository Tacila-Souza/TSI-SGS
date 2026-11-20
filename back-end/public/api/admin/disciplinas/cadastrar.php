<?php
declare(strict_types=1);

require dirname(__DIR__, 4) . '/src/bootstrap.php';

exigir_metodo('POST');
exigir_identidade('admin');
$dados = ler_json();

responder_sucesso(salvar_disciplina($pdo, $dados, null), 201, 'Disciplina cadastrada.');
