<?php
declare(strict_types=1);

/**
 * Carrega um curso já no formato CursoDTO do contrato.
 */
function carregar_curso_dto(PDO $pdo, int $id): ?array
{
    $comando = $pdo->prepare(
        'SELECT id, sigla, nome, descricao FROM cursos WHERE id = ? LIMIT 1'
    );
    $comando->execute([$id]);
    $linha = $comando->fetch();

    return $linha === false ? null : serializar_curso($linha);
}

/**
 * Validação compartilhada entre cadastro e edição de curso.
 */
function validar_dados_curso(array $dados): array
{
    $sigla = texto_obrigatorio($dados['sigla'] ?? null, 'sigla', 20);
    $nome = texto_obrigatorio($dados['nome'] ?? null, 'nome', 120);
    $descricao = texto_opcional($dados['descricao'] ?? null, 'descricao');
    if ($descricao === '') {
        $descricao = null;
    }

    return ['sigla' => $sigla, 'nome' => $nome, 'descricao' => $descricao];
}
