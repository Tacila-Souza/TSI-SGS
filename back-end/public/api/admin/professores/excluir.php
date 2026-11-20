<?php
declare(strict_types=1);

require dirname(__DIR__, 4) . '/src/bootstrap.php';

exigir_metodo('DELETE');
exigir_identidade('admin');
$id = parametro_inteiro('id');

if (carregar_usuario_dto($pdo, 'professor', $id) === null) {
    throw new RegraNegocioException('Professor não encontrado.', 404);
}

$comando = $pdo->prepare("UPDATE professores SET status = 'inativo' WHERE id = ?");
$comando->execute([$id]);

responder_sucesso(['afetados' => $comando->rowCount()], 200, 'Professor desativado.');
