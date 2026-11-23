<?php
declare(strict_types=1);

/**
 * Converte uma linha de cursos para o CursoDTO do contrato (seção 7.2).
 * O array de disciplinas é passado por quem carregou os dados.
 */
function serializar_curso(array $linha, array $disciplinas = []): array
{
    return [
        'id' => (int) $linha['id'],
        'sigla' => (string) $linha['sigla'],
        'nome' => (string) $linha['nome'],
        'descricao' => $linha['descricao'] === null ? null : (string) $linha['descricao'],
        'disciplinas' => $disciplinas,
    ];
}
