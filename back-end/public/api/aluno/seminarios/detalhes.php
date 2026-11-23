<?php
declare(strict_types=1);

require dirname(__DIR__, 4) . '/src/bootstrap.php';

exigir_metodo('GET');

$alunoId = exigir_identidade('aluno');
$seminarioId = query_int('id');

if ($seminarioId === null) {
    responder_erro('O parâmetro id é obrigatório.', 400);
}

// listar_seminarios_aluno já filtra pela matrícula e carrega os detalhes da sessão.
$seminario = null;
foreach (listar_seminarios_aluno($pdo, $alunoId) as $item) {
    if ((int) $item['id'] === $seminarioId) {
        $seminario = $item;
        break;
    }
}

if ($seminario === null) {
    responder_erro('Seminário não encontrado para este aluno.', 404);
}

responder_sucesso(serializar_seminario($seminario));
