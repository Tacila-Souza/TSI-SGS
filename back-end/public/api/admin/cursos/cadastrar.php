<?php
declare(strict_types=1);

require dirname(__DIR__, 4) . '/src/bootstrap.php';

exigir_metodo('POST');
exigir_identidade('admin');
$dados = ler_json();

$validado = validar_dados_curso($dados);

$comando = $pdo->prepare('SELECT id FROM cursos WHERE sigla = ? LIMIT 1');
$comando->execute([$validado['sigla']]);
if ($comando->fetchColumn() !== false) {
    throw new RegraNegocioException('Curso já cadastrado.', 409, ['sigla' => 'Esta sigla já pertence a outro curso.']);
}

try {
    $comando = $pdo->prepare(
        'INSERT INTO cursos (sigla, nome, descricao) VALUES (?, ?, ?)'
    );
    $comando->execute([$validado['sigla'], $validado['nome'], $validado['descricao']]);
} catch (PDOException $erro) {
    if (str_starts_with((string) $erro->getCode(), '23')) {
        throw new RegraNegocioException('Curso já cadastrado.', 409, ['sigla' => 'Esta sigla já pertence a outro curso.']);
    }
    throw $erro;
}

responder_sucesso(carregar_curso_dto($pdo, (int) $pdo->lastInsertId()), 201, 'Curso cadastrado.');
