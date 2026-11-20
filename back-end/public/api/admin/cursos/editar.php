<?php
declare(strict_types=1);

require dirname(__DIR__, 4) . '/src/bootstrap.php';

exigir_metodo('PUT');
exigir_identidade('admin');
$id = parametro_inteiro('id');
$dados = ler_json();

$validado = validar_dados_curso($dados);

if (carregar_curso_dto($pdo, $id) === null) {
    throw new RegraNegocioException('Curso não encontrado.', 404);
}

$comando = $pdo->prepare('SELECT id FROM cursos WHERE sigla = ? AND id <> ? LIMIT 1');
$comando->execute([$validado['sigla'], $id]);
if ($comando->fetchColumn() !== false) {
    throw new RegraNegocioException('Curso já cadastrado.', 409, ['sigla' => 'Esta sigla já pertence a outro curso.']);
}

try {
    $comando = $pdo->prepare('UPDATE cursos SET sigla = ?, nome = ?, descricao = ? WHERE id = ?');
    $comando->execute([$validado['sigla'], $validado['nome'], $validado['descricao'], $id]);
} catch (PDOException $erro) {
    if (str_starts_with((string) $erro->getCode(), '23')) {
        throw new RegraNegocioException('Curso já cadastrado.', 409, ['sigla' => 'Esta sigla já pertence a outro curso.']);
    }
    throw $erro;
}

responder_sucesso(carregar_curso_dto($pdo, $id));
