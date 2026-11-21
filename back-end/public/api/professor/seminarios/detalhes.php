<?php

declare(strict_types=1);

require_once dirname(__DIR__, 4) . '/src/bootstrap.php';

require_once dirname(__DIR__, 4)
    . '/src/contrato/seminario.php';

exigir_metodo('GET');

$professorId = exigir_identidade('professor');

// Validar ID do seminário

$seminarioId = filter_var(
    $_GET['id'] ?? null,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

if ($seminarioId === false) {
    responder_erro('Informe um seminário válido.', 400);
}

// Buscar seminário

$consulta = $pdo->prepare(
    'SELECT
        s.id,
        s.disciplina_id,
        s.professor_id,
        s.titulo,
        s.descricao,
        s.tempo_apresentacao,
        s.peso_professor,
        s.status,
        d.nome AS disciplina_nome
     FROM seminarios s
     INNER JOIN disciplinas d
        ON d.id = s.disciplina_id
     WHERE s.id = ?'
);

$consulta->execute([$seminarioId]);

$seminario = $consulta->fetch();

if (!$seminario) {
    responder_erro('Seminário não encontrado.', 404);
}

// Verificar propriedade

if ((int) $seminario['professor_id'] !== $professorId) {
    responder_erro(
        'Você não possui acesso a este seminário.',
        403
    );
}

// Organizar resposta

$dados = serializar_seminario($pdo, $seminario);

responder_sucesso($dados);