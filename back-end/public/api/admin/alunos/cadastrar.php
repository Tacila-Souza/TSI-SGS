<?php
declare(strict_types=1);

require dirname(__DIR__, 4) . '/src/bootstrap.php';

exigir_metodo('POST');
exigir_identidade('admin');
$dados = ler_json();

$validado = validar_dados_aluno($dados);
$nome = $validado['nome'];
$email = $validado['email'];
$registro = $validado['registro'];
$anoMatricula = $validado['anoMatricula'];
$semestreEntrada = $validado['semestreEntrada'];

validar_unicidade_usuario($pdo, 'aluno', $email, $registro, null);

try {
    $comando = $pdo->prepare(
        'INSERT INTO alunos (nome_completo, email, matricula, ano_matricula, semestre_entrada, status)
         VALUES (?, ?, ?, ?, ?, "ativo")'
    );
    $comando->execute([$nome, $email, $registro, $anoMatricula, (int) $semestreEntrada]);
} catch (PDOException $erro) {
    if (str_starts_with((string) $erro->getCode(), '23')) {
        throw new RegraNegocioException(
            'E-mail ou matrícula já cadastrado.',
            409,
            ['registro' => 'E-mail ou matrícula já pertence a outro aluno.'],
        );
    }
    throw $erro;
}

$linha = [
    'id' => (int) $pdo->lastInsertId(),
    'nome_completo' => $nome,
    'email' => $email,
    'matricula' => $registro,
    'ano_matricula' => $anoMatricula,
    'semestre_entrada' => $semestreEntrada,
    'status' => 'ativo',
];

responder_sucesso(serializar_usuario($linha, [], 'aluno'), 201);
