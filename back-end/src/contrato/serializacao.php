<?php
declare(strict_types=1);

/**
 * Serialização para os DTOs camelCase do contrato (docs/contrato_api_frontend.md).
 * Nenhuma função aqui executa SQL: recebem linhas já carregadas.
 */

function ids_da_coluna(mixed $valor): array
{
    if ($valor === null || $valor === '') {
        return [];
    }
    $partes = explode(',', (string) $valor);
    return array_values(array_filter(array_map('intval', $partes), static fn (int $id): bool => $id > 0));
}

function converter_status_seminario(string $status): string
{
    return match ($status) {
        'pendente' => 'rascunho',
        'concluido' => 'finalizado',
        default => $status,
    };
}

function converter_status_sessao(string $status): string
{
    return match ($status) {
        'agendada', 'em_andamento' => 'agendado',
        'concluida', 'cancelada' => 'finalizado',
        default => $status,
    };
}

function serializar_criterio(array $linha): array
{
    return [
        'id' => (int) $linha['id'],
        'nome' => (string) $linha['nome_criterio'],
        'peso' => (int) $linha['peso_criterio'],
        'tipo' => (string) $linha['tipo_avaliacao'],
    ];
}

function serializar_grupo(array $linha): array
{
    $ordem = $linha['ordem_apresentacao'] !== null ? (int) $linha['ordem_apresentacao'] : (int) $linha['id'];

    return [
        'id' => (int) $linha['id'],
        'nome' => 'Grupo ' . $ordem,
        'tema' => (string) $linha['tema'],
        'integrantesIds' => ids_da_coluna($linha['integrantes_ids'] ?? null),
        'ordem' => $ordem,
        'status' => (string) ($linha['status'] ?? 'pendente'),
    ];
}

function serializar_presenca(array $linhas): array
{
    return array_map(
        static fn (array $linha): array => [
            'alunoId' => (int) $linha['aluno_id'],
            'presente' => (bool) $linha['presente'],
        ],
        $linhas
    );
}

function serializar_sessao(array $linha, array $grupos, array $presenca): array
{
    return [
        'id' => (int) $linha['id'],
        'data' => (string) $linha['data_apresentacao'],
        'horario' => substr((string) $linha['horario'], 0, 5),
        'grupos' => array_map('serializar_grupo', $grupos),
        'presenca' => serializar_presenca($presenca),
        'chamadaRealizada' => $presenca !== [],
        'status' => converter_status_sessao((string) $linha['status']),
    ];
}

function serializar_seminario(
    array $linha,
    array $datas,
    array $criterios,
    array $gruposPorData,
    array $presencaPorData
): array {
    $primeira = $datas[0] ?? null;
    $idPrimeira = $primeira !== null ? (int) $primeira['id'] : null;

    $sessoes = [];
    foreach ($datas as $data) {
        $id = (int) $data['id'];
        $sessoes[] = serializar_sessao($data, $gruposPorData[$id] ?? [], $presencaPorData[$id] ?? []);
    }

    return [
        'id' => (int) $linha['id'],
        'titulo' => (string) $linha['titulo'],
        'descricao' => (string) ($linha['descricao'] ?? ''),
        'disciplina' => (string) $linha['disciplina_nome'],
        'turmaId' => (int) $linha['disciplina_id'],
        'professorId' => (int) $linha['professor_id'],
        'data' => $primeira !== null ? (string) $primeira['data_apresentacao'] : '',
        'horario' => $primeira !== null ? substr((string) $primeira['horario'], 0, 5) : '',
        'tempoApresentacaoMin' => (int) $linha['tempo_apresentacao'],
        'pesoProfessor' => round((int) $linha['peso_professor'] / 100, 4),
        'status' => converter_status_seminario((string) $linha['status']),
        'criterios' => array_map('serializar_criterio', $criterios),
        'grupos' => $idPrimeira !== null ? array_map('serializar_grupo', $gruposPorData[$idPrimeira] ?? []) : [],
        'presenca' => $idPrimeira !== null ? serializar_presenca($presencaPorData[$idPrimeira] ?? []) : [],
        'sessoes' => $sessoes,
    ];
}

function serializar_notas(array $linhas): array
{
    return array_map(
        static fn (array $linha): array => [
            'criterioId' => (int) $linha['criterio_id'],
            'alunoId' => $linha['aluno_avaliado_id'] !== null ? (int) $linha['aluno_avaliado_id'] : null,
            'nota' => round((float) $linha['nota'], 2),
        ],
        $linhas
    );
}

function serializar_formulario_aluno(array $sessao, array $criterios, array $grupos, array $minhas): array
{
    return [
        'seminarioId' => (int) $sessao['seminario_id'],
        'dataId' => (int) $sessao['id'],
        'dataSessao' => (string) $sessao['data_apresentacao'],
        'criterios' => array_map('serializar_criterio', $criterios),
        'grupos' => array_map('serializar_grupo', $grupos),
        'minhasAvaliacoes' => array_values(array_map(
            static fn (array $avaliacao): array => [
                'grupoId' => (int) $avaliacao['grupoId'],
                'notas' => serializar_notas($avaliacao['notas']),
                'feedback' => (string) $avaliacao['feedback'],
            ],
            $minhas
        )),
    ];
}
