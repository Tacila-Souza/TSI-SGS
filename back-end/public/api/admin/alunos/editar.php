<?php
declare(strict_types=1);

require dirname(__DIR__, 4) . '/src/bootstrap.php';

exigir_metodo('PUT');
exigir_identidade('admin');
$id = parametro_inteiro('id');
$dados = ler_json();

$validado = validar_dados_aluno($dados);
$status = array_key_exists('status', $dados) ? validar_status($dados['status']) : null;

if (carregar_usuario_dto($pdo, 'aluno', $id) === null) {
    throw new RegraNegocioException('Aluno não encontrado.', 404);
}
validar_unicidade_usuario($pdo, 'aluno', $validado['email'], $validado['registro'], $id);

$comando = $pdo->prepare(
    'UPDATE alunos
     SET nome_completo = ?, email = ?, matricula = ?, ano_matricula = ?,
         semestre_entrada = ?, status = COALESCE(?, status)
     WHERE id = ?'
);
$comando->execute([
    $validado['nome'],
    $validado['email'],
    $validado['registro'],
    $validado['anoMatricula'],
    (int) $validado['semestreEntrada'],
    $status,
    $id,
]);

responder_sucesso(carregar_usuario_dto($pdo, 'aluno', $id));
