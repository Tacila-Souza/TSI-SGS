import type { Turma, User } from '@/lib/types'

export type { Turma, User }

export interface Curso {
  id: string
  sigla: string
  nome: string
  descricao: string
  disciplinas: DisciplinaAdmin[]
}

export interface DisciplinaAdmin {
  id: string
  cursoId: string
  nome: string
  codigo: string
  professorId: string
  professorNome: string
  alunoIds: string[]
}

export interface AlunoPayload {
  nome: string
  email: string
  registro: string
  anoMatricula: number
  semestreEntrada: '1' | '2'
  status?: 'ativo' | 'inativo'
}

export interface ProfessorPayload {
  nome: string
  email: string
  registro: string
  status?: 'ativo' | 'inativo'
}

export interface CursoPayload {
  sigla: string
  nome: string
  descricao: string
}

export interface ResultadoDTO {
  seminarioId: number
  dataId: number
  dataSessao: string
  semAvaliadores: boolean
  pesoProfessor?: number
  pesoAlunos?: number
  criterios: Array<{ id: number; nome: string; peso: number; tipo: 'individual' | 'grupo' }>
  ranking: Array<{
    posicao: number
    grupoId: number
    grupoNome: string
    tema: string
    mediaGeral: number
    notaProfessor: number
    notaAlunos: number
    avaliadoresConsiderados: number
    integrantes: Array<{
      alunoId: number
      nome: string
      notaProfessor: number
      notaAlunos: number
      notaFinal: number
    }>
    notasProfessorPorCriterio?: Array<{ criterioId: number; nota: number | null }>
    notasParesPorCriterio?: Array<{ criterioId: number; nota: number | null }>
  }>
  feedbacks: Array<{
    grupoId: number
    avaliadorId: number
    tipo: 'professor' | 'par'
    texto: string
    avaliadorNome?: string | null
  }>
}

export interface DisciplinaPayload {
  cursoId: number
  professorId: number | null
  nome: string
  codigo: string
  alunoIds: number[]
}
