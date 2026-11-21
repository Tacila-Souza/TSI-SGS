<?php
declare(strict_types=1);

require dirname(__DIR__, 4) . '/src/bootstrap.php';

exigir_metodo('PUT');
exigir_identidade('admin');
$id = query_int('id');
$dados = ler_json();

$stmt = $pdo->prepare('SELECT id, sigla, nome, descricao FROM cursos WHERE id = ?');
$stmt->execute([$id]);
$curso = $stmt->fetch();
if ($curso === false) {
    responder_erro('Curso não encontrado.', 404);
}

$sigla = strtoupper(campo_texto($dados, 'sigla', 20));
$nome = campo_texto($dados, 'nome', 120);
$descricao = isset($dados['descricao']) ? trim((string) $dados['descricao']) : null;

$stmt = $pdo->prepare('SELECT COUNT(*) FROM cursos WHERE sigla = ? AND id <> ?');
$stmt->execute([$sigla, $id]);
if ((int) $stmt->fetchColumn() > 0) {
    throw new HttpErro('Sigla de curso já cadastrada.', 409);
}

$stmt = $pdo->prepare('UPDATE cursos SET sigla = ?, nome = ?, descricao = ? WHERE id = ?');
$stmt->execute([$sigla, $nome, $descricao, $id]);

$stmt = $pdo->prepare('SELECT id, sigla, nome, descricao FROM cursos WHERE id = ?');
$stmt->execute([$id]);
responder_sucesso(serializar_curso($stmt->fetch()), 200, 'Curso atualizado com sucesso.');
