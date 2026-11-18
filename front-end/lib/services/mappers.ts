import type { Curso, DisciplinaAdmin, Turma, User } from '@/lib/types-admin'
import type { Criterio, Grupo, Seminario, SeminarioSessao } from '@/lib/types'

export function toApiId(id: number | string): number {
  if (typeof id === 'number') return id
  const parte = String(id).split('-').pop()
  const numero = Number(parte)
  if (!Number.isInteger(numero) || numero <= 0) {
    throw new Error(`ID inválido para a API: ${id}`)
  }
  return numero
}

export function mapUsuario(dto: {
  id: number
  nome: string
  email: string
  role: 'aluno' | 'professor'
  status: 'ativo' | 'inativo'
  registro: string
  turmaIds: number[]
  anoMatricula?: number
  semestreEntrada?: string
}): User {
  const prefixo = dto.role === 'aluno' ? 'u-al-' : 'u-prof-'
  return {
    id: `${prefixo}${dto.id}`,
    nome: dto.nome,
    email: dto.email,
    role: dto.role,
    status: dto.status,
    registro: dto.registro,
    turmaIds: dto.turmaIds.map((id) => `t-${id}`),
    ...(dto.role === 'aluno'
      ? {
          anoMatricula: dto.anoMatricula,
          semestreEntrada: (dto.semestreEntrada === '2' ? '2' : '1') as '1' | '2',
        }
      : {}),
  }
}

export function mapCurso(dto: {
  id: number
  sigla: string
  nome: string
  descricao: string | null
}): Curso {
  return {
    id: `c-${dto.id}`,
    sigla: dto.sigla,
    nome: dto.nome,
    descricao: dto.descricao ?? '',
    disciplinas: [],
  }
}

export function mapDisciplina(dto: {
  id: number
  cursoId: number
  nome: string
  codigo: string
  professorId: number | null
  professorNome: string | null
  alunoIds: number[]
  alunosCount: number
}): DisciplinaAdmin {
  return {
    id: `t-${dto.id}`,
    cursoId: `c-${dto.cursoId}`,
    nome: dto.nome,
    codigo: dto.codigo,
    professorId: dto.professorId ? `u-prof-${dto.professorId}` : '',
    professorNome: dto.professorNome ?? '',
    alunoIds: dto.alunoIds.map((id) => `u-al-${id}`),
  }
}

export function mapCriterio(dto: {
  id: number
  nome: string
  peso: number
  tipo: 'individual' | 'grupo'
}): Criterio {
  return {
    id: `cr-${dto.id}`,
    nome: dto.nome,
    peso: dto.peso,
    tipo: dto.tipo,
  }
}

export function mapGrupo(dto: {
  id: number
  nome: string
  tema: string
  integrantesIds: number[]
  ordem: number
}): Grupo {
  return {
    id: `g-${dto.id}`,
    nome: dto.nome,
    tema: dto.tema,
    integrantesIds: dto.integrantesIds.map((id) => `u-al-${id}`),
    ordem: dto.ordem,
  }
}

export function mapSessao(dto: {
  id?: number
  data: string
  horario?: string
  grupos: Parameters<typeof mapGrupo>[0][]
  presenca: Array<{ alunoId: number; presente: boolean }>
  chamadaRealizada?: boolean
  status: 'agendado' | 'finalizado'
}): SeminarioSessao {
  return {
    ...(dto.id ? { id: dto.id } : {}),
    data: dto.data,
    horario: dto.horario,
    grupos: dto.grupos.map(mapGrupo),
    presenca: Object.fromEntries(
      dto.presenca.map((p) => [`u-al-${p.alunoId}`, p.presente]),
    ),
    chamadaRealizada: dto.chamadaRealizada,
    status: dto.status,
  }
}

export function mapSeminario(dto: {
  id: number
  titulo: string
  descricao: string
  disciplina: string
  turmaId: number
  professorId: number
  data: string
  horario: string
  tempoApresentacaoMin: number
  pesoProfessor: number
  status: Seminario['status']
  criterios: Parameters<typeof mapCriterio>[0][]
  grupos: Parameters<typeof mapGrupo>[0][]
  presenca: Array<{ alunoId: number; presente: boolean }>
  sessoes?: Parameters<typeof mapSessao>[0][]
}): Seminario {
  const sessoes = dto.sessoes?.map(mapSessao) ?? []
  return {
    id: `s-${dto.id}`,
    titulo: dto.titulo,
    descricao: dto.descricao,
    disciplina: dto.disciplina,
    turmaId: `t-${dto.turmaId}`,
    professorId: `u-prof-${dto.professorId}`,
    data: dto.data,
    horario: dto.horario,
    tempoApresentacaoMin: dto.tempoApresentacaoMin,
    pesoProfessor: dto.pesoProfessor,
    status: dto.status,
    criterios: dto.criterios.map(mapCriterio),
    grupos: dto.grupos.map(mapGrupo),
    presenca: Object.fromEntries(
      dto.presenca.map((p) => [`u-al-${p.alunoId}`, p.presente]),
    ),
    ...(sessoes.length ? { sessoes } : {}),
  }
}

export function mapTurma(dto: {
  id: number
  nome: string
  cursoId: number
  cursoDescricao: string
  disciplina: string
  disciplinaSigla: string
  professorId: number | null
  alunoIds: number[]
}): Turma {
  return {
    id: `t-${dto.id}`,
    nome: dto.nome,
    cursoId: `c-${dto.cursoId}`,
    cursoDescricao: dto.cursoDescricao,
    disciplina: dto.disciplina,
    disciplinaSigla: dto.disciplinaSigla,
    professorId: dto.professorId ? `u-prof-${dto.professorId}` : '',
    alunoIds: dto.alunoIds.map((id) => `u-al-${id}`),
  }
}
