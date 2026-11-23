<?php
declare(strict_types=1);

/** Converte linhas do banco nos DTOs camelCase definidos em docs/contrato_api_frontend.md. */

function serializar_aluno(array $r): array
{
    return [
        'id' => (int) $r['id'],
        'nome' => (string) $r['nome_completo'],
        'email' => (string) $r['email'],
        'role' => 'aluno',
        'status' => (string) $r['status'],
        'registro' => (string) $r['matricula'],
        'turmaIds' => array_map('intval', $r['turma_ids'] ?? []),
        'anoMatricula' => (int) $r['ano_matricula'],
        'semestreEntrada' => (string) $r['semestre_entrada'],
    ];
}

function serializar_professor(array $r): array
{
    return [
        'id' => (int) $r['id'],
        'nome' => (string) $r['nome_completo'],
        'email' => (string) $r['email'],
        'role' => 'professor',
        'status' => (string) $r['status'],
        'registro' => (string) $r['registro'],
        'turmaIds' => array_map('intval', $r['turma_ids'] ?? []),
    ];
}

function serializar_curso(array $r): array
{
    $dto = [
        'id' => (int) $r['id'],
        'sigla' => (string) $r['sigla'],
        'nome' => (string) $r['nome'],
        'descricao' => $r['descricao'] !== null ? (string) $r['descricao'] : null,
    ];

    if (array_key_exists('disciplinas', $r)) {
        $dto['disciplinas'] = array_map('serializar_disciplina', $r['disciplinas']);
    }

    return $dto;
}

function serializar_disciplina(array $r): array
{
    return [
        'id' => (int) $r['id'],
        'cursoId' => (int) $r['curso_id'],
        'nome' => (string) $r['nome'],
        'codigo' => (string) $r['codigo'],
        'professorId' => isset($r['professor_id']) ? (int) $r['professor_id'] : null,
        'professorNome' => $r['professor_nome'] ?? null,
        'alunoIds' => array_map('intval', $r['aluno_ids'] ?? []),
        'alunosCount' => (int) ($r['alunos_count'] ?? count($r['aluno_ids'] ?? [])),
    ];
}

function serializar_turma(array $r): array
{
    return [
        'id' => (int) $r['id'],
        'nome' => (string) $r['curso_sigla'],
        'cursoId' => (int) $r['curso_id'],
        'cursoDescricao' => (string) ($r['curso_descricao'] ?? $r['curso_nome'] ?? ''),
        'disciplina' => (string) $r['nome'],
        'disciplinaSigla' => (string) $r['codigo'],
        'professorId' => isset($r['professor_id']) ? (int) $r['professor_id'] : null,
        'alunoIds' => array_map('intval', $r['aluno_ids'] ?? []),
    ];
}

function serializar_criterio(array $r): array
{
    return [
        'id' => (int) $r['id'],
        'nome' => (string) $r['nome_criterio'],
        'peso' => (int) $r['peso_criterio'],
        'tipo' => (string) $r['tipo_avaliacao'],
    ];
}

function serializar_grupo(array $r): array
{
    $ordem = isset($r['ordem_apresentacao']) ? (int) $r['ordem_apresentacao'] : 0;
    return [
        'id' => (int) $r['id'],
        'nome' => 'Grupo ' . ($ordem > 0 ? $ordem : (int) $r['id']),
        'tema' => (string) $r['tema'],
        'integrantesIds' => array_map('intval', $r['integrantes_ids'] ?? []),
        'ordem' => $ordem,
        'status' => (string) ($r['status'] ?? 'pendente'),
    ];
}

function status_sessao_api(string $status): string
{
    return match ($status) {
        'concluida' => 'finalizado',
        default => 'agendado',
    };
}

function status_seminario_api(string $status): string
{
    return match ($status) {
        'pendente' => 'rascunho',
        'concluido' => 'finalizado',
        default => $status,
    };
}

function serializar_sessao(array $r): array
{
    $presenca = [];
    foreach ($r['presencas'] ?? [] as $item) {
        $presenca[] = [
            'alunoId' => (int) $item['aluno_id'],
            'presente' => (bool) $item['presente'],
        ];
    }

    return [
        'id' => (int) $r['id'],
        'data' => (string) $r['data_apresentacao'],
        'horario' => substr((string) $r['horario'], 0, 5),
        'grupos' => array_map('serializar_grupo', $r['grupos'] ?? []),
        'presenca' => $presenca,
        'chamadaRealizada' => !empty($r['presencas']),
        'status' => status_sessao_api((string) $r['status']),
    ];
}

function serializar_seminario(array $r): array
{
    $sessoes = array_map('serializar_sessao', $r['sessoes'] ?? []);
    $primeira = $sessoes[0] ?? null;

    return [
        'id' => (int) $r['id'],
        'titulo' => (string) $r['titulo'],
        'descricao' => (string) ($r['descricao'] ?? ''),
        'disciplina' => (string) ($r['disciplina_nome'] ?? ''),
        'turmaId' => (int) $r['disciplina_id'],
        'professorId' => (int) $r['professor_id'],
        'data' => $primeira['data'] ?? '',
        'horario' => $primeira['horario'] ?? '',
        'tempoApresentacaoMin' => (int) $r['tempo_apresentacao'],
        'pesoProfessor' => round((int) $r['peso_professor'] / 100, 2),
        'status' => status_seminario_api((string) $r['status']),
        'criterios' => array_map('serializar_criterio', $r['criterios'] ?? []),
        'grupos' => $primeira['grupos'] ?? [],
        'presenca' => $primeira['presenca'] ?? [],
        'sessoes' => $sessoes,
    ];
}
