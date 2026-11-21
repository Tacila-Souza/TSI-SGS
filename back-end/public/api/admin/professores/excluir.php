<?php
declare(strict_types=1);

require dirname(__DIR__, 4) . '/src/bootstrap.php';

exigir_metodo('DELETE');
exigir_identidade('admin');
$id = query_int('id');

$professor = buscar_professor($pdo, $id);
if ($professor === null) {
    responder_erro('Professor não encontrado.', 404);
}

$stmt = $pdo->prepare("UPDATE professores SET status = 'inativo' WHERE id = ?");
$stmt->execute([$id]);

responder_sucesso(['id' => $id], 200, 'Professor excluído logicamente.');
