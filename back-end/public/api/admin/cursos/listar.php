<?php
declare(strict_types=1);

require dirname(__DIR__, 4) . '/src/bootstrap.php';

exigir_metodo('GET');
exigir_identidade('admin');

$comando = $pdo->query('SELECT id, sigla, nome, descricao FROM cursos ORDER BY nome, id');
$cursos = array_map('serializar_curso', $comando->fetchAll());

responder_sucesso($cursos);
