<?php
declare(strict_types=1);

require dirname(__DIR__, 4) . '/src/bootstrap.php';

exigir_metodo('DELETE');
exigir_identidade('admin');
$id = query_int('id');

$aluno = buscar_aluno($pdo, $id);
if ($aluno === null) {
    responder_erro('Aluno não encontrado.', 404);
}

$stmt = $pdo->prepare("UPDATE alunos SET status = 'inativo' WHERE id = ?");
$stmt->execute([$id]);

responder_sucesso(['id' => $id], 200, 'Aluno excluído logicamente.');
