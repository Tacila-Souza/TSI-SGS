<?php
declare(strict_types=1);

require dirname(__DIR__, 4) . '/src/bootstrap.php';

exigir_metodo('DELETE');
exigir_identidade('admin');
$id = parametro_inteiro('id');

if (carregar_usuario_dto($pdo, 'aluno', $id) === null) {
    throw new RegraNegocioException('Aluno não encontrado.', 404);
}

$comando = $pdo->prepare("UPDATE alunos SET status = 'inativo' WHERE id = ?");
$comando->execute([$id]);

responder_sucesso(['afetados' => $comando->rowCount()], 200, 'Aluno desativado.');
