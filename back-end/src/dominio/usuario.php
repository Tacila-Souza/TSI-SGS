<?php
declare(strict_types=1);

/**
 * Exclusão lógica em lote de alunos ou professores.
 * Todos os IDs são validados antes da atualização e a operação é atômica.
 */
function excluir_usuarios_lote(PDO $pdo, string $perfil, array $ids): int
{
    if ($perfil === 'aluno') {
        $tabela = 'alunos';
    } elseif ($perfil === 'professor') {
        $tabela = 'professores';
    } else {
        throw new RegraNegocioException('Perfil inválido.', 400);
    }

    if ($ids === []) {
        throw new RegraNegocioException('Dados inválidos.', 400, ['ids' => 'Envie ao menos um ID.']);
    }

    $marcadores = implode(',', array_fill(0, count($ids), '?'));
    $comando = $pdo->prepare("SELECT COUNT(*) FROM {$tabela} WHERE id IN ({$marcadores})");
    $comando->execute($ids);
    if ((int) $comando->fetchColumn() !== count($ids)) {
        throw new RegraNegocioException('Um ou mais registros não foram encontrados.', 404);
    }

    try {
        $pdo->beginTransaction();
        $comando = $pdo->prepare("UPDATE {$tabela} SET status = 'inativo' WHERE id IN ({$marcadores})");
        $comando->execute($ids);
        $afetados = $comando->rowCount();
        $pdo->commit();
    } catch (Throwable $erro) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $erro;
    }

    return $afetados;
}

/**
 * Validação compartilhada entre cadastro e edição de aluno.
 */
function validar_dados_aluno(array $dados): array
{
    $nome = texto_obrigatorio($dados['nome'] ?? null, 'nome', 150);
    $email = texto_obrigatorio($dados['email'] ?? null, 'email', 150);
    if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        throw new RegraNegocioException('Dados inválidos.', 400, ['email' => 'Informe um e-mail válido.']);
    }
    $registro = texto_obrigatorio($dados['registro'] ?? null, 'registro', 30);

    $anoMatricula = $dados['anoMatricula'] ?? null;
    if (!is_int($anoMatricula) || $anoMatricula < 1900 || $anoMatricula > 2100) {
        throw new RegraNegocioException('Dados inválidos.', 400, ['anoMatricula' => 'Informe um ano válido.']);
    }

    $semestreEntrada = $dados['semestreEntrada'] ?? null;
    if (!is_string($semestreEntrada) || !in_array($semestreEntrada, ['1', '2'], true)) {
        throw new RegraNegocioException('Dados inválidos.', 400, ['semestreEntrada' => 'Escolha 1 ou 2.']);
    }

    return [
        'nome' => $nome,
        'email' => $email,
        'registro' => $registro,
        'anoMatricula' => $anoMatricula,
        'semestreEntrada' => $semestreEntrada,
    ];
}

/**
 * Validação compartilhada entre cadastro e edição de professor.
 */
function validar_dados_professor(array $dados): array
{
    $nome = texto_obrigatorio($dados['nome'] ?? null, 'nome', 150);
    $email = texto_obrigatorio($dados['email'] ?? null, 'email', 150);
    if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        throw new RegraNegocioException('Dados inválidos.', 400, ['email' => 'Informe um e-mail válido.']);
    }
    $registro = texto_obrigatorio($dados['registro'] ?? null, 'registro', 30);

    return ['nome' => $nome, 'email' => $email, 'registro' => $registro];
}

/**
 * Conflito de unicidade em alunos ou professores, opcionalmente ignorando um ID.
 */
function validar_unicidade_usuario(
    PDO $pdo,
    string $perfil,
    string $email,
    string $registro,
    ?int $idIgnorado,
): void {
    if ($perfil === 'aluno') {
        $tabela = 'alunos';
        $colunaRegistro = 'matricula';
        $mensagemRegistro = 'Esta matrícula já pertence a outro aluno.';
        $mensagemEmail = 'Este e-mail já pertence a outro aluno.';
    } elseif ($perfil === 'professor') {
        $tabela = 'professores';
        $colunaRegistro = 'registro';
        $mensagemRegistro = 'Este registro já pertence a outro professor.';
        $mensagemEmail = 'Este e-mail já pertence a outro professor.';
    } else {
        throw new RegraNegocioException('Perfil inválido.', 400);
    }

    $sql = "SELECT email, {$colunaRegistro} AS registro FROM {$tabela} WHERE (email = ? OR {$colunaRegistro} = ?)";
    $parametros = [$email, $registro];
    if ($idIgnorado !== null) {
        $sql .= ' AND id <> ?';
        $parametros[] = $idIgnorado;
    }
    $sql .= ' LIMIT 2';

    $comando = $pdo->prepare($sql);
    $comando->execute($parametros);
    $erros = [];
    foreach ($comando->fetchAll() as $existente) {
        if (hash_equals((string) $existente['email'], $email)) {
            $erros['email'] = $mensagemEmail;
        }
        if (hash_equals((string) $existente['registro'], $registro)) {
            $erros['registro'] = $mensagemRegistro;
        }
    }
    if ($erros !== []) {
        throw new RegraNegocioException('Usuário já cadastrado.', 409, $erros);
    }
}
