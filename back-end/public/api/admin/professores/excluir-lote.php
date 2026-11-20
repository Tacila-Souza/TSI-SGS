<?php
declare(strict_types=1);

require dirname(__DIR__, 4) . '/src/bootstrap.php';

exigir_metodo('POST');
exigir_identidade('admin');
$dados = ler_json();

$ids = validar_lista_ids($dados['ids'] ?? null, 'ids');
$afetados = excluir_usuarios_lote($pdo, 'professor', $ids);

responder_sucesso(['afetados' => $afetados], 200, 'Professores desativados.');
