<?php
declare(strict_types=1);

require dirname(__DIR__, 4) . '/src/bootstrap.php';

exigir_metodo('POST');

$professorId = exigir_identidade('professor');
$dataId = query_int('data_id');

if ($dataId === null) {
    responder_erro('O parâmetro data_id é obrigatório.', 400);
}

$dados = ler_json();

$presencas = salvar_chamada($pdo, $professorId, $dataId, $dados);

responder_sucesso(
    array_map(
        static fn (array $linha): array => [
            'alunoId' => (int) $linha['aluno_id'],
            'nome' => (string) $linha['nome_completo'],
            'presente' => (bool) $linha['presente'],
        ],
        $presencas
    ),
    200,
    'Chamada salva com sucesso.'
);
