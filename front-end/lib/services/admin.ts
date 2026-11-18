import { api } from './api'
import { mapCurso, mapDisciplina, mapUsuario, toApiId } from './mappers'
import type {
  AlunoPayload,
  Curso,
  CursoPayload,
  DisciplinaAdmin,
  DisciplinaPayload,
  ProfessorPayload,
  User,
} from '@/lib/types-admin'

type UsuarioDTO = Parameters<typeof mapUsuario>[0]
type CursoDTO = Parameters<typeof mapCurso>[0]
type DisciplinaDTO = Parameters<typeof mapDisciplina>[0]

interface Envelope<T> {
  sucesso: boolean
  dados?: T
}

function dados<T>(envelope: Envelope<T>): T {
  if (envelope.dados === undefined) {
    throw new Error('A API não retornou dados.')
  }
  return envelope.dados
}

export const adminService = {
  async listarAlunos(incluirInativos = false, busca = ''): Promise<User[]> {
    const params = new URLSearchParams()
    if (busca) params.set('busca', busca)
    if (incluirInativos) params.set('incluir_inativos', '1')
    const sufixo = params.toString() ? `?${params}` : ''
    const resposta = await api.get<UsuarioDTO[]>(`/api/admin/alunos/listar.php${sufixo}`)
    return dados(resposta).map(mapUsuario)
  },

  async cadastrarAluno(payload: AlunoPayload): Promise<User> {
    const resposta = await api.post<UsuarioDTO>('/api/admin/alunos/cadastrar.php', payload)
    return mapUsuario(dados(resposta))
  },

  async editarAluno(id: number | string, payload: AlunoPayload): Promise<User> {
    const resposta = await api.put<UsuarioDTO>(
      `/api/admin/alunos/editar.php?id=${toApiId(id)}`,
      payload,
    )
    return mapUsuario(dados(resposta))
  },

  async excluirAluno(id: number | string): Promise<void> {
    await api.del(`/api/admin/alunos/excluir.php?id=${toApiId(id)}`)
  },

  async excluirAlunosLote(ids: Array<number | string>): Promise<number> {
    const resposta = await api.post<{ afetados: number }>(
      '/api/admin/alunos/excluir-lote.php',
      { ids: ids.map(toApiId) },
    )
    return dados(resposta).afetados
  },

  async listarProfessores(incluirInativos = false, busca = ''): Promise<User[]> {
    const params = new URLSearchParams()
    if (busca) params.set('busca', busca)
    if (incluirInativos) params.set('incluir_inativos', '1')
    const sufixo = params.toString() ? `?${params}` : ''
    const resposta = await api.get<UsuarioDTO[]>(`/api/admin/professores/listar.php${sufixo}`)
    return dados(resposta).map(mapUsuario)
  },

  async cadastrarProfessor(payload: ProfessorPayload): Promise<User> {
    const resposta = await api.post<UsuarioDTO>('/api/admin/professores/cadastrar.php', payload)
    return mapUsuario(dados(resposta))
  },

  async editarProfessor(id: number | string, payload: ProfessorPayload): Promise<User> {
    const resposta = await api.put<UsuarioDTO>(
      `/api/admin/professores/editar.php?id=${toApiId(id)}`,
      payload,
    )
    return mapUsuario(dados(resposta))
  },

  async excluirProfessor(id: number | string): Promise<void> {
    await api.del(`/api/admin/professores/excluir.php?id=${toApiId(id)}`)
  },

  async excluirProfessoresLote(ids: Array<number | string>): Promise<number> {
    const resposta = await api.post<{ afetados: number }>(
      '/api/admin/professores/excluir-lote.php',
      { ids: ids.map(toApiId) },
    )
    return dados(resposta).afetados
  },

  async listarCursos(): Promise<Curso[]> {
    const resposta = await api.get<CursoDTO[]>('/api/admin/cursos/listar.php')
    return dados(resposta).map(mapCurso)
  },

  async cadastrarCurso(payload: CursoPayload): Promise<Curso> {
    const resposta = await api.post<CursoDTO>('/api/admin/cursos/cadastrar.php', payload)
    return mapCurso(dados(resposta))
  },

  async editarCurso(id: number | string, payload: CursoPayload): Promise<Curso> {
    const resposta = await api.put<CursoDTO>(
      `/api/admin/cursos/editar.php?id=${toApiId(id)}`,
      payload,
    )
    return mapCurso(dados(resposta))
  },

  async excluirCurso(id: number | string): Promise<void> {
    await api.del(`/api/admin/cursos/excluir.php?id=${toApiId(id)}`)
  },

  async listarDisciplinas(cursoId?: number | string): Promise<DisciplinaAdmin[]> {
    const sufixo = cursoId !== undefined ? `?curso_id=${toApiId(cursoId)}` : ''
    const resposta = await api.get<DisciplinaDTO[]>(`/api/admin/disciplinas/listar.php${sufixo}`)
    return dados(resposta).map(mapDisciplina)
  },

  async detalhesDisciplina(id: number | string): Promise<DisciplinaAdmin> {
    const resposta = await api.get<DisciplinaDTO>(
      `/api/admin/disciplinas/detalhes.php?id=${toApiId(id)}`,
    )
    return mapDisciplina(dados(resposta))
  },

  async cadastrarDisciplina(payload: DisciplinaPayload): Promise<DisciplinaAdmin> {
    const resposta = await api.post<DisciplinaDTO>(
      '/api/admin/disciplinas/cadastrar.php',
      payload,
    )
    return mapDisciplina(dados(resposta))
  },

  async editarDisciplina(id: number | string, payload: DisciplinaPayload): Promise<DisciplinaAdmin> {
    const resposta = await api.put<DisciplinaDTO>(
      `/api/admin/disciplinas/editar.php?id=${toApiId(id)}`,
      payload,
    )
    return mapDisciplina(dados(resposta))
  },

  async excluirDisciplina(id: number | string): Promise<void> {
    await api.del(`/api/admin/disciplinas/excluir.php?id=${toApiId(id)}`)
  },
}
