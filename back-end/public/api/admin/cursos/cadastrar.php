<?php
declare(strict_types=1);

require dirname(__DIR__, 4) . '/src/bootstrap.php';

exigir_metodo('POST');
exigir_identidade('admin');
$dados = ler_json();

$sigla = strtoupper(campo_texto($dados, 'sigla', 20));
$nome = campo_texto($dados, 'nome', 120);
$descricao = isset($dados['descricao']) ? trim((string) $dados['descricao']) : null;

$stmt = $pdo->prepare('SELECT COUNT(*) FROM cursos WHERE sigla = ?');
$stmt->execute([$sigla]);
if ((int) $stmt->fetchColumn() > 0) {
    throw new HttpErro('Sigla de curso já cadastrada.', 409, [
        'sigla' => 'Valor já existente.',
    ]);
}

$stmt = $pdo->prepare('INSERT INTO cursos (sigla, nome, descricao) VALUES (?, ?, ?)');
$stmt->execute([$sigla, $nome, $descricao]);

$stmt = $pdo->prepare('SELECT id, sigla, nome, descricao FROM cursos WHERE id = ?');
$stmt->execute([(int) $pdo->lastInsertId()]);
responder_sucesso(serializar_curso($stmt->fetch()), 201, 'Curso cadastrado com sucesso.');
