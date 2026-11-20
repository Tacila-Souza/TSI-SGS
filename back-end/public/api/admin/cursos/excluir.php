<?php
declare(strict_types=1);

require dirname(__DIR__, 4) . '/src/bootstrap.php';

exigir_metodo('DELETE');
exigir_identidade('admin');
$id = parametro_inteiro('id');

if (carregar_curso_dto($pdo, $id) === null) {
    throw new RegraNegocioException('Curso não encontrado.', 404);
}

$comando = $pdo->prepare('SELECT COUNT(*) FROM disciplinas WHERE curso_id = ?');
$comando->execute([$id]);
if ((int) $comando->fetchColumn() > 0) {
    throw new RegraNegocioException(
        'Não é possível excluir um curso com disciplinas vinculadas.',
        409,
    );
}

$comando = $pdo->prepare('DELETE FROM cursos WHERE id = ?');
$comando->execute([$id]);

responder_sucesso(['afetados' => $comando->rowCount()], 200, 'Curso excluído.');
