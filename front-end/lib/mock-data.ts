import type {
  Avaliacao,
  Criterio,
  Seminario,
  Turma,
  User,
} from './types'

// ---------------------------------------------------------------------------
// DADOS FICTÍCIOS DE DEMONSTRAÇÃO
// Coerentes com o contexto acadêmico descrito nos documentos anexados.
// ---------------------------------------------------------------------------

const cursosBase = [
  {
    id: 'curso-tsi',
    sigla: 'TSI',
    nome: 'Tecnólogo em Sistemas para Internet',
    disciplinas: [
      { id: 't-tsi-pi', nome: 'Programação Imperativa', sigla: 'PI' },
    ],
  },
] as const

export const turmas: Turma[] = cursosBase.flatMap((curso) =>
  curso.disciplinas.map((disciplina) => ({
    id: disciplina.id,
    nome: curso.sigla,
    cursoId: curso.id,
    cursoDescricao: curso.nome,
    disciplina: disciplina.nome,
    disciplinaSigla: disciplina.sigla,
    professorId: 'u-prof-1',
    alunoIds: ['u-al-1', 'u-al-2'],
  })),
)

export const usuarios: User[] = [
  {
    id: 'u-coord',
    nome: 'Administrador do Sistema',
    email: 'admin@instituto.edu.br',
    role: 'coordenador',
    status: 'ativo',
    registro: 'SIAPE 100234',
    turmaIds: [],
  },
  {
    id: 'u-prof-1',
    nome: 'Liliane Nascimento',
    email: 'liliane.nascimento@instituto.edu.br',
    role: 'professor',
    status: 'ativo',
    registro: '204518',
    turmaIds: turmas.map((turma) => turma.id),
  },
  {
    id: 'u-al-1',
    nome: 'Ana Beatriz Souza',
    email: 'ana.beatriz.souza@aluno.instituto.edu.br',
    role: 'aluno',
    status: 'ativo',
    registro: '20261001',
    turmaIds: turmas.map((turma) => turma.id),
    anoMatricula: 2026,
    semestreEntrada: '1',
  },
  {
    id: 'u-al-2',
    nome: 'Lucas Henrique Lima',
    email: 'lucas.henrique.lima@aluno.instituto.edu.br',
    role: 'aluno',
    status: 'ativo',
    registro: '20261002',
    turmaIds: turmas.map((turma) => turma.id),
    anoMatricula: 2026,
    semestreEntrada: '1',
  },
]

export const cursosAcademicos = cursosBase.map((curso) => ({
  id: curso.id,
  sigla: curso.sigla,
  nome: curso.nome,
  descricao: `Curso ${curso.sigla}`,
  disciplinas: turmas.filter((turma) => turma.cursoId === curso.id).map((turma) => ({
    id: turma.id,
    nome: turma.disciplina,
    codigo: turma.disciplinaSigla,
    professores: [turma.professorId],
    alunos: turma.alunoIds,
  })),
}))

const criteriosPadrao: Criterio[] = [
  {
    id: 'c-1',
    nome: 'Domínio do conteúdo',
    peso: 30,
    tipo: 'grupo',
  },
  {
    id: 'c-2',
    nome: 'Clareza e didática',
    peso: 25,
    tipo: 'individual',
  },
  {
    id: 'c-3',
    nome: 'Qualidade do material',
    peso: 20,
    tipo: 'grupo',
  },
  {
    id: 'c-4',
    nome: 'Gestão do tempo',
    peso: 15,
    tipo: 'grupo',
  },
  {
    id: 'c-5',
    nome: 'Postura e participação',
    peso: 10,
    tipo: 'individual',
  },
]

function presencaTodos(alunoIds: string[], ausentes: string[] = []) {
  return Object.fromEntries(
    alunoIds.map((id) => [id, !ausentes.includes(id)]),
  ) as Record<string, boolean>
}

const alunosEsw = turmas.find((turma) => turma.id === 't-tsi-pi')!.alunoIds

export const seminarios: Seminario[] = [
  {
    id: 's-live',
    titulo: 'Seminário de Arquitetura de Software',
    descricao: 'Seminário ao vivo para testar chamada, grupos e avaliações em tempo real.',
    disciplina: 'Programação Imperativa',
    turmaId: 't-tsi-pi',
    professorId: 'u-prof-1',
    data: '2026-11-17',
    horario: '19:00',
    tempoApresentacaoMin: 15,
    pesoProfessor: 0.6,
    status: 'em_andamento',
    criterios: criteriosPadrao,
    grupos: [
      { id: 'gl-1', nome: 'Grupo 1', tema: 'Arquitetura em camadas', integrantesIds: ['u-al-1'], ordem: 1 },
      { id: 'gl-2', nome: 'Grupo 2', tema: 'Microsserviços', integrantesIds: ['u-al-2'], ordem: 2 },
    ],
    presenca: { 'u-al-1': true, 'u-al-2': true },
    sessoes: [{
      data: '2026-11-17',
      horario: '19:00',
      grupos: [
        { id: 'gl-1', nome: 'Grupo 1', tema: 'Arquitetura em camadas', integrantesIds: ['u-al-1'], ordem: 1 },
        { id: 'gl-2', nome: 'Grupo 2', tema: 'Microsserviços', integrantesIds: ['u-al-2'], ordem: 2 },
      ],
      presenca: { 'u-al-1': true, 'u-al-2': true },
      chamadaRealizada: true,
      status: 'agendado',
    }],
  },
  {
    id: 's-pad',
    titulo: 'Seminário de Projeto 1',
    descricao: 'Apresentações concluídas sobre padrões de projeto, com notas e feedbacks para consulta dos resultados.',
    disciplina: 'Programação Imperativa',
    turmaId: 't-tsi-pi',
    professorId: 'u-prof-1',
    data: '2026-09-08',
    horario: '19:00',
    tempoApresentacaoMin: 12,
    pesoProfessor: 0.6,
    status: 'finalizado',
    criterios: criteriosPadrao,
    grupos: [
      { id: 'gp-1', nome: 'Grupo A', tema: 'Padrões Criacionais', integrantesIds: ['u-al-1'], ordem: 1 },
      { id: 'gp-2', nome: 'Grupo B', tema: 'Padrões Estruturais', integrantesIds: ['u-al-2'], ordem: 2 },
    ],
    presenca: presencaTodos(alunosEsw),
  },
]

// Avaliações do seminário finalizado (professor + pares) para exibir resultados.
function gerarAvaliacoes(): Avaliacao[] {
  const semin = seminarios.find((s) => s.id === 's-pad')!
  const out: Avaliacao[] = []
  const notasBase: Record<string, Record<string, number>> = {
    'gp-1': { 'c-1': 9, 'c-2': 8, 'c-3': 9, 'c-4': 8, 'c-5': 9 },
    'gp-2': { 'c-1': 8, 'c-2': 9, 'c-3': 7, 'c-4': 8, 'c-5': 8 },
  }
  const feedbacksProf: Record<string, string> = {
    'gp-1': 'Excelente domínio dos padrões criacionais e ótimos exemplos práticos.',
    'gp-2': 'Boa apresentação, mas o material poderia ter mais diagramas.',
  }
  // Avaliação do professor
  for (const g of semin.grupos) {
    out.push({
      id: `av-prof-${g.id}`,
      seminarioId: semin.id,
      grupoId: g.id,
      avaliadorId: 'u-prof-1',
      tipo: 'professor',
      notas: notasBase[g.id],
      feedback: feedbacksProf[g.id],
      criadoEm: '2026-09-08T21:30:00',
    })
  }
  // Avaliações por pares (alguns alunos avaliando outros grupos, com pequena variação)
  const avaliadores = ['u-al-1', 'u-al-2', 'u-al-3', 'u-al-4', 'u-al-5', 'u-al-6']
  for (const g of semin.grupos) {
    avaliadores
      // aluno não avalia o próprio grupo
      .filter((a) => !g.integrantesIds.includes(a))
      .slice(0, 4)
      .forEach((a, idx) => {
        const jitter = (idx % 3) - 1 // -1, 0, 1
        const notas = Object.fromEntries(
          Object.entries(notasBase[g.id]).map(([c, n]) => [
            c,
            Math.max(0, Math.min(10, n + jitter)),
          ]),
        )
        out.push({
          id: `av-${g.id}-${a}`,
          seminarioId: semin.id,
          grupoId: g.id,
          avaliadorId: a,
          tipo: 'par',
          notas,
          feedback:
            idx === 0 ? 'Apresentação clara e bem estruturada. Parabéns!' : '',
          criadoEm: '2026-09-08T21:35:00',
        })
      })
  }
  return out
}

export const avaliacoesIniciais: Avaliacao[] = gerarAvaliacoes()

// ---------------------------------------------------------------------------
// Helpers de leitura
// ---------------------------------------------------------------------------

export function nomeUsuario(id: string): string {
  return usuarios.find((u) => u.id === id)?.nome ?? 'Desconhecido'
}

export function iniciais(nome: string): string {
  const parts = nome.trim().split(/\s+/)
  return (parts[0]?.[0] ?? '') + (parts[parts.length - 1]?.[0] ?? '')
}

export function nomeTurma(id: string): string {
  return turmas.find((t) => t.id === id)?.nome ?? '—'
}
