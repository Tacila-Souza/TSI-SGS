<?php
declare(strict_types=1);

require dirname(__DIR__, 4) . '/src/bootstrap.php';

exigir_metodo('PUT');
exigir_identidade('admin');
$id = query_int('id');
$dados = ler_json();

$aluno = buscar_aluno($pdo, $id);
if ($aluno === null) {
    responder_erro('Aluno não encontrado.', 404);
}

$nome = campo_texto($dados, 'nome', 150);
$email = campo_email($dados);
$registro = campo_texto($dados, 'registro', 30);
$anoMatricula = campo_int($dados + ['anoMatricula' => $aluno['ano_matricula']], 'anoMatricula', 2000);
$semestreEntrada = campo_enum(
    $dados + ['semestreEntrada' => (string) $aluno['semestre_entrada']],
    'semestreEntrada',
    ['1', '2']
);
$status = campo_enum($dados + ['status' => $aluno['status']], 'status', ['ativo', 'inativo']);

foreach ([
    'email' => $email,
    'matricula' => $registro,
] as $coluna => $valor) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM alunos WHERE {$coluna} = ? AND id <> ?");
    $stmt->execute([$valor, $id]);
    if ((int) $stmt->fetchColumn() > 0) {
        throw new HttpErro('E-mail ou matrícula já cadastrado.', 409);
    }
}

$stmt = $pdo->prepare(
    'UPDATE alunos
     SET nome_completo = ?, email = ?, matricula = ?, ano_matricula = ?, semestre_entrada = ?, status = ?
     WHERE id = ?'
);
$stmt->execute([
    $nome,
    $email,
    $registro,
    $anoMatricula,
    (int) $semestreEntrada,
    $status,
    $id,
]);

responder_sucesso(
    serializar_aluno(buscar_aluno($pdo, $id)),
    200,
    'Aluno atualizado com sucesso.'
);
