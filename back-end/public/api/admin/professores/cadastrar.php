<?php
declare(strict_types=1);

require dirname(__DIR__, 4) . '/src/bootstrap.php';

exigir_metodo('POST');
exigir_identidade('admin');
$dados = ler_json();

$nome = campo_texto($dados, 'nome', 150);
$email = campo_email($dados);
$registro = campo_texto($dados, 'registro', 30);

foreach ([
    'email' => $email,
    'registro' => $registro,
] as $coluna => $valor) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM professores WHERE {$coluna} = ?");
    $stmt->execute([$valor]);
    if ((int) $stmt->fetchColumn() > 0) {
        throw new HttpErro('E-mail ou registro já cadastrado.', 409);
    }
}

$stmt = $pdo->prepare(
    'INSERT INTO professores (nome_completo, email, registro) VALUES (?, ?, ?)'
);
$stmt->execute([$nome, $email, $registro]);

responder_sucesso(
    serializar_professor(buscar_professor($pdo, (int) $pdo->lastInsertId())),
    201,
    'Professor cadastrado com sucesso.'
);
