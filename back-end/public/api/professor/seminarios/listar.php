<?php

declare(strict_types=1);

require_once dirname(__DIR__, 4) . '/src/bootstrap.php';

require_once dirname(__DIR__, 4)
    . '/src/contrato/seminario.php';

exigir_metodo('GET');

$professorId = exigir_identidade('professor');

$consulta = $pdo->prepare(
    'SELECT
        s.*,
        d.nome AS disciplina_nome
     FROM seminarios s
     INNER JOIN disciplinas d
        ON d.id = s.disciplina_id
     WHERE s.professor_id = ?
     ORDER BY s.id DESC'
);

$consulta->execute([$professorId]);

$seminarios = [];

foreach ($consulta->fetchAll() as $seminario) {

    $seminarios[] = serializar_seminario(
        $pdo,
        $seminario
    );
}

responder_sucesso($seminarios);