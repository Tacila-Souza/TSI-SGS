<?php
declare(strict_types=1);

require dirname(__DIR__, 4) . '/src/bootstrap.php';

exigir_metodo('POST');

$professorId = exigir_identidade('professor');
$dataId = query_int('data_id');

if ($dataId === null) {
    responder_erro('O parâmetro data_id é obrigatório.', 400);
}

$dados = json_decode(
    file_get_contents('php://input'),
    true
);

if (!is_array($dados)) {
    responder_erro('Corpo JSON inválido.', 400);
}

$presencas = salvar_chamada(
    $pdo,
    $professorId,
    $dataId,
    $dados
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
