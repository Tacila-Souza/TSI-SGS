<?php
declare(strict_types=1);

require dirname(__DIR__, 4) . '/src/bootstrap.php';

exigir_metodo('GET');

$professorId = exigir_identidade('professor');
$dataId = query_int('data_id');

if ($dataId === null) {
    responder_erro('O parâmetro data_id é obrigatório.', 400);
}

$presencas = listar_presencas_sessao(
    $pdo,
    $dataId,
    $professorId
);

responder_sucesso(
    array_map(
        static function (array $presenca): array {
            return [
                'alunoId' => (int) $presenca['aluno_id'],
                'nome' => (string) $presenca['nome_completo'],
                'presente' => (bool) $presenca['presente'],
            ];
        },
        $presencas
    )
);
