<?php
declare(strict_types=1);

require dirname(__DIR__, 4) . '/src/bootstrap.php';

exigir_metodo('POST');
exigir_identidade('admin');
$dados = ler_json();

$nome = campo_texto($dados, 'nome', 150);
$email = campo_email($dados);
$registro = campo_texto($dados, 'registro', 30);
$anoMatricula = campo_int($dados, 'anoMatricula', 2000);
$semestreEntrada = campo_enum($dados, 'semestreEntrada', ['1', '2']);

foreach ([
    'email' => $email,
    'matricula' => $registro,
] as $coluna => $valor) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM alunos WHERE {$coluna} = ?");
    $stmt->execute([$valor]);
    if ((int) $stmt->fetchColumn() > 0) {
        throw new HttpErro('E-mail ou matrícula já cadastrado.', 409);
    }
}

$stmt = $pdo->prepare(
    'INSERT INTO alunos (nome_completo, email, matricula, ano_matricula, semestre_entrada)
     VALUES (?, ?, ?, ?, ?)'
);
$stmt->execute([$nome, $email, $registro, $anoMatricula, (int) $semestreEntrada]);

responder_sucesso(
    serializar_aluno(buscar_aluno($pdo, (int) $pdo->lastInsertId())),
    201,
    'Aluno cadastrado com sucesso.'
);
