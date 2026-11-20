<?php
declare(strict_types=1);

require dirname(__DIR__, 4) . '/src/bootstrap.php';

exigir_metodo('PUT');
exigir_identidade('admin');
$id = parametro_inteiro('id');
$dados = ler_json();

$validado = validar_dados_professor($dados);
$status = array_key_exists('status', $dados) ? validar_status($dados['status']) : null;

if (carregar_usuario_dto($pdo, 'professor', $id) === null) {
    throw new RegraNegocioException('Professor não encontrado.', 404);
}
validar_unicidade_usuario($pdo, 'professor', $validado['email'], $validado['registro'], $id);

$comando = $pdo->prepare(
    'UPDATE professores
     SET nome_completo = ?, email = ?, registro = ?, status = COALESCE(?, status)
     WHERE id = ?'
);
$comando->execute([
    $validado['nome'],
    $validado['email'],
    $validado['registro'],
    $status,
    $id,
]);

responder_sucesso(carregar_usuario_dto($pdo, 'professor', $id));
