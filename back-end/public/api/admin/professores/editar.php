<?php
declare(strict_types=1);

require dirname(__DIR__, 4) . '/src/bootstrap.php';

exigir_metodo('PUT');
exigir_identidade('admin');
$id = query_int('id');
$dados = ler_json();

$professor = buscar_professor($pdo, $id);
if ($professor === null) {
    responder_erro('Professor não encontrado.', 404);
}

$nome = campo_texto($dados, 'nome', 150);
$email = campo_email($dados);
$registro = campo_texto($dados, 'registro', 30);
$status = campo_enum($dados + ['status' => $professor['status']], 'status', ['ativo', 'inativo']);

foreach ([
    'email' => $email,
    'registro' => $registro,
] as $coluna => $valor) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM professores WHERE {$coluna} = ? AND id <> ?");
    $stmt->execute([$valor, $id]);
    if ((int) $stmt->fetchColumn() > 0) {
        throw new HttpErro('E-mail ou registro já cadastrado.', 409);
    }
}

$stmt = $pdo->prepare(
    'UPDATE professores SET nome_completo = ?, email = ?, registro = ?, status = ? WHERE id = ?'
);
$stmt->execute([$nome, $email, $registro, $status, $id]);

responder_sucesso(
    serializar_professor(buscar_professor($pdo, $id)),
    200,
    'Professor atualizado com sucesso.'
);
