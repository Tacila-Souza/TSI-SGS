<?php
declare(strict_types=1);

function status_api_seminario(string $status): string
{
    return match ($status) {
        'pendente' => 'rascunho',
        'concluido' => 'finalizado',
        default => $status,
    };
}

function status_api_sessao(string $status): string
{
    return match ($status) {
        'concluida' => 'finalizado',
        default => 'agendado',
    };
}

function serializar_criterio(array $row): array
{
    return [
        'id' => (int) $row['id'],
        'nome' => (string) $row['nome_criterio'],
        'peso' => (int) $row['peso_criterio'],
        'tipo' => (string) $row['tipo_avaliacao'],
    ];
}

function serializar_grupo(array $row): array
{
    $id = (int) $row['id'];
    $ordem = isset($row['ordem_apresentacao']) && $row['ordem_apresentacao'] !== null
        ? (int) $row['ordem_apresentacao']
        : $id;

    return [
        'id' => $id,
        'nome' => 'Grupo ' . $ordem,
        'tema' => (string) $row['tema'],
        'integrantesIds' => array_map('intval', $row['integrantes_ids'] ?? []),
        'ordem' => $ordem,
        'status' => (string) ($row['status'] ?? 'pendente'),
    ];
}

function serializar_presenca(array $linhas): array
{
    return array_map(
        static fn (array $linha): array => [
            'alunoId' => (int) $linha['aluno_id'],
            'presente' => (bool) $linha['presente'],
        ],
        $linhas,
    );
}

function serializar_sessao(array $sessao, array $grupos, array $presenca): array
{
    return [
        'id' => (int) $sessao['id'],
        'data' => (string) $sessao['data_apresentacao'],
        'horario' => substr((string) $sessao['horario'], 0, 5),
        'grupos' => array_map('serializar_grupo', $grupos),
        'presenca' => serializar_presenca($presenca),
        'chamadaRealizada' => $presenca !== [],
        'status' => status_api_sessao((string) $sessao['status']),
    ];
}

function serializar_seminario(
    array $seminario,
    array $criterios,
    array $grupos,
    array $presenca,
    array $sessoes,
): array {
    $primeira = $sessoes[0] ?? null;

    return [
        'id' => (int) $seminario['id'],
        'titulo' => (string) $seminario['titulo'],
        'descricao' => (string) ($seminario['descricao'] ?? ''),
        'disciplina' => (string) ($seminario['disciplina_nome'] ?? ''),
        'turmaId' => (int) $seminario['disciplina_id'],
        'professorId' => (int) $seminario['professor_id'],
        'data' => $primeira['data'] ?? '',
        'horario' => $primeira['horario'] ?? '',
        'tempoApresentacaoMin' => (int) $seminario['tempo_apresentacao'],
        'pesoProfessor' => round((int) $seminario['peso_professor'] / 100, 2),
        'status' => status_api_seminario((string) $seminario['status']),
        'criterios' => array_map('serializar_criterio', $criterios),
        'grupos' => $primeira['grupos'] ?? [],
        'presenca' => $primeira['presenca'] ?? [],
        'sessoes' => $sessoes,
    ];
}
