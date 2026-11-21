<?php

declare(strict_types=1);

function exigir_identidade(string $perfil): int
{
    global $pdo;

    if ($perfil === 'admin') {
        return 0;
    }

    $configuracoes = [
        'professor' => [
            'parametro' => 'professor_id',
            'tabela' => 'professores'
        ],
        'aluno' => [
            'parametro' => 'aluno_id',
            'tabela' => 'alunos'
        ]
    ];

    if (!isset($configuracoes[$perfil])) {
        responder_erro('Perfil inválido.', 400);
    }

    $config = $configuracoes[$perfil];

    $id = filter_var(
        $_GET[$config['parametro']] ?? null,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );

    if ($id === false) {
        responder_erro(
            'Informe um identificador válido.',
            400
        );
    }

    $tabela = $config['tabela'];

    $consulta = $pdo->prepare(
        "SELECT id, status FROM {$tabela} WHERE id = ?"
    );

    $consulta->execute([$id]);

    $usuario = $consulta->fetch();

    if (!$usuario) {
        responder_erro('Usuário não encontrado.', 404);
    }

    if ($usuario['status'] !== 'ativo') {
        responder_erro('Usuário inativo.', 403);
    }

    return (int) $usuario['id'];
}
