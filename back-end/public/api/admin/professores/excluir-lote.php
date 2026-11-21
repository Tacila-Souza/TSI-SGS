<?php
declare(strict_types=1);

require dirname(__DIR__, 4) . '/src/bootstrap.php';

exigir_metodo('POST');
exigir_identidade('admin');
$dados = ler_json();
$ids = campo_ids($dados, 'ids');

$pdo->beginTransaction();
try {
    $stmtBusca = $pdo->prepare('SELECT status FROM professores WHERE id = ?');
    $stmtUpdate = $pdo->prepare("UPDATE professores SET status = 'inativo' WHERE id = ?");

    foreach ($ids as $id) {
        $stmtBusca->execute([$id]);
        $status = $stmtBusca->fetchColumn();
        if ($status === false) {
            throw new HttpErro("Professor {$id} não encontrado.", 404);
        }
        if ($status !== 'ativo') {
            throw new HttpErro("Professor {$id} já está inativo.", 400);
        }
    }

    foreach ($ids as $id) {
        $stmtUpdate->execute([$id]);
    }

    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    throw $e;
}

responder_sucesso(['afetados' => count($ids)], 200, 'Professores excluídos logicamente.');
