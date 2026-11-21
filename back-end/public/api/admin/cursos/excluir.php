<?php
declare(strict_types=1);

require dirname(__DIR__, 4) . '/src/bootstrap.php';

exigir_metodo('DELETE');
exigir_identidade('admin');
$id = query_int('id');

$stmt = $pdo->prepare('SELECT COUNT(*) FROM disciplinas WHERE curso_id = ?');
$stmt->execute([$id]);
if ((int) $stmt->fetchColumn() > 0) {
    responder_erro(
        'Não é possível excluir um curso com disciplinas vinculadas. Remova as disciplinas primeiro.',
        409
    );
}

$stmt = $pdo->prepare('DELETE FROM cursos WHERE id = ?');
$stmt->execute([$id]);

responder_sucesso(['id' => $id], 200, 'Curso excluído.');
