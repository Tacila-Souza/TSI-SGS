<?php
declare(strict_types=1);

require dirname(__DIR__, 4) . '/src/bootstrap.php';

exigir_metodo('DELETE');
exigir_identidade('admin');
$id = parametro_inteiro('id');

if (carregar_disciplina_dto($pdo, $id) === null) {
    throw new RegraNegocioException('Disciplina não encontrada.', 404);
}

$comando = $pdo->prepare('SELECT COUNT(*) FROM seminarios WHERE disciplina_id = ?');
$comando->execute([$id]);
if ((int) $comando->fetchColumn() > 0) {
    throw new RegraNegocioException(
        'Não é possível excluir uma disciplina com seminários vinculados.',
        409,
    );
}

$comando = $pdo->prepare('DELETE FROM disciplinas WHERE id = ?');
$comando->execute([$id]);

responder_sucesso(['afetados' => $comando->rowCount()], 200, 'Disciplina excluída.');
