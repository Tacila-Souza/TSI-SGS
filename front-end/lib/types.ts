// Tipos de domínio do protótipo "The Seven".
// Baseados nas User Stories e na Visão do Produto anexadas.

export type Role = 'coordenador' | 'professor' | 'aluno'

export type UserStatus = 'ativo' | 'inativo'

export interface User {
  id: string
  nome: string
  email: string
  role: Role
  status: UserStatus
  /** Matrícula (aluno) ou SIAPE/registro (professor). */
  registro: string
  /** Turmas ou disciplinas às quais o usuário está vinculado. */
  turmaIds: string[]
  /** Metadados acadêmicos usados no cadastro de alunos. */
  anoMatricula?: number
  semestreEntrada?: '1' | '2'
}

export interface Turma {
  id: string
  /** Sigla do curso, usada como agrupador nos formulários acadêmicos. */
  nome: string
  cursoId: string
  cursoDescricao: string
  disciplina: string
  disciplinaSigla: string
  professorId: string
  alunoIds: string[]
}

export type CriterioTipo = 'individual' | 'grupo'

export interface Criterio {
  id: string
  nome: string
  /** Peso relativo do critério (somatório dos pesos = 100). */
  peso: number
  tipo: CriterioTipo
}

export interface Grupo {
  id: string
  nome: string
  tema: string
  integrantesIds: string[]
  /** Ordem de apresentação definida pelo sorteio. */
  ordem: number
}

export type SeminarioStatus =
  | 'rascunho'
  | 'agendado'
  | 'em_andamento'
  | 'finalizado'

export interface SeminarioSessao {
  /** ID numérico da sessão no banco; usado pelos endpoints de grupo/presença. */
  id?: number
  data: string
  horario?: string
  grupos: Grupo[]
  presenca: Record<string, boolean>
  chamadaRealizada?: boolean
  status: 'agendado' | 'finalizado'
}

export interface Seminario {
  id: string
  titulo: string
  descricao: string
  disciplina: string
  turmaId: string
  professorId: string
  data: string // ISO date
  horario: string // HH:mm
  /** Tempo em minutos definido para cada apresentação. */
  tempoApresentacaoMin: number
  /** Peso da avaliação do professor na nota final (0-1). */
  pesoProfessor: number
  status: SeminarioStatus
  criterios: Criterio[]
  grupos: Grupo[]
  /** Presença por aluno (chamada). alunoId -> presente. */
  presenca: Record<string, boolean>
  /** Sessões independentes para seminários com múltiplas datas. */
  sessoes?: SeminarioSessao[]
}

export function obterSessoesSeminario(seminario: Seminario): SeminarioSessao[] {
  return seminario.sessoes?.length
    ? seminario.sessoes
    : [{ data: seminario.data, grupos: seminario.grupos, presenca: seminario.presenca, status: seminario.status === 'finalizado' ? 'finalizado' : 'agendado' }]
}

export type AvaliacaoTipo = 'professor' | 'par'

export interface Avaliacao {
  id: string
  seminarioId: string
  dataSessao?: string
  grupoId: string
  avaliadorId: string
  tipo: AvaliacaoTipo
  /** Notas por critério (0-10). criterioId -> nota. */
  notas: Record<string, number>
  feedback: string
  criadoEm: string
}

/** Estado da apresentação ao vivo (sincronizado na sessão). */
export interface LiveState {
  seminarioId: string | null
  grupoId: string | null
  status: 'aguardando' | 'em_andamento' | 'finalizada'
  running: boolean
  durationSec: number
  remainingSec: number
}
