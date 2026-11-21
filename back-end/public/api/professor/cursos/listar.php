<?php

declare(strict_types=1);

require_once dirname(__DIR__, 4) . '/src/bootstrap.php';

exigir_metodo('GET');

exigir_identidade('professor');

$consulta = $pdo->prepare(
    'SELECT id, sigla, nome, descricao
     FROM cursos
     ORDER BY nome ASC'
);

$consulta->execute();

$cursos = [];

foreach ($consulta->fetchAll() as $curso) {
    $cursos[] = [
        'id' => (int) $curso['id'],
        'sigla' => $curso['sigla'],
        'nome' => $curso['nome'],
        'descricao' => $curso['descricao']
    ];
}

responder_sucesso($cursos);