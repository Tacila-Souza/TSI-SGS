import { api } from './api'
import { mapCurso, mapDisciplina, mapGrupo, mapSeminario, mapUsuario, toApiId } from './mappers'
import type { Curso, DisciplinaAdmin, ResultadoDTO } from '@/lib/types-admin'
import type { Seminario } from '@/lib/types'

type CursoDTO = Parameters<typeof mapCurso>[0]
type DisciplinaDTO = Parameters<typeof mapDisciplina>[0]
type SeminarioDTO = Parameters<typeof mapSeminario>[0]

interface Envelope<T> {
  sucesso: boolean
  dados?: T
}

function dados<T>(envelope: Envelope<T>): T {
  if (envelope.dados === undefined) throw new Error('A API não retornou dados.')
  return envelope.dados
}

export interface SeminarioPayload {
  turmaId: number
  titulo: string
  descricao: string
  tempoApresentacaoMin: number
  pesoProfessor: number
  datas: Array<{ id?: number; data: string; horario: string }>
  criterios: Array<{ id?: number; nome: string; peso: number; tipo: 'individual' | 'grupo' }>
}

export const professorService = {
  async listarCursos(professorId: number): Promise<Curso[]> {
    const resposta = await api.get<CursoDTO[]>(
      `/api/professor/cursos/listar.php?professor_id=${professorId}`,
    )
    return dados(resposta).map(mapCurso)
  },

  async listarDisciplinas(professorId: number, cursoId?: number | string): Promise<DisciplinaAdmin[]> {
    const sufixo = cursoId !== undefined ? `&curso_id=${toApiId(cursoId)}` : ''
    const resposta = await api.get<DisciplinaDTO[]>(
      `/api/professor/disciplinas/listar.php?professor_id=${professorId}${sufixo}`,
    )
    return dados(resposta).map(mapDisciplina)
  },

  async listarAlunos(professorId: number, disciplinaId: number | string): Promise<ReturnType<typeof mapUsuario>[]> {
    const resposta = await api.get<Parameters<typeof mapUsuario>[0][]>(
      `/api/professor/alunos/listar.php?professor_id=${professorId}&disciplina_id=${toApiId(disciplinaId)}`,
    )
    return dados(resposta).map(mapUsuario)
  },

  async listarSeminarios(professorId: number): Promise<Seminario[]> {
    const resposta = await api.get<SeminarioDTO[]>(
      `/api/professor/seminarios/listar.php?professor_id=${professorId}`,
    )
    return dados(resposta).map(mapSeminario)
  },

  async detalhesSeminario(professorId: number, id: number | string): Promise<Seminario> {
    const resposta = await api.get<SeminarioDTO>(
      `/api/professor/seminarios/detalhes.php?professor_id=${professorId}&id=${toApiId(id)}`,
    )
    return mapSeminario(dados(resposta))
  },

  async cadastrarSeminario(professorId: number, payload: SeminarioPayload): Promise<Seminario> {
    const resposta = await api.post<SeminarioDTO>(
      `/api/professor/seminarios/cadastrar.php?professor_id=${professorId}`,
      payload,
    )
    return mapSeminario(dados(resposta))
  },

  async editarSeminario(
    professorId: number,
    id: number | string,
    payload: SeminarioPayload,
  ): Promise<Seminario> {
    const resposta = await api.put<SeminarioDTO>(
      `/api/professor/seminarios/editar.php?professor_id=${professorId}&id=${toApiId(id)}`,
      payload,
    )
    return mapSeminario(dados(resposta))
  },

  async excluirSeminario(professorId: number, id: number | string): Promise<void> {
    await api.del(`/api/professor/seminarios/excluir.php?professor_id=${professorId}&id=${toApiId(id)}`)
  },

  async listarGrupos(professorId: number, dataId: number): Promise<ReturnType<typeof mapGrupo>[]> {
    const resposta = await api.get<Parameters<typeof mapGrupo>[0][]>(
      `/api/professor/grupos/listar.php?professor_id=${professorId}&data_id=${dataId}`,
    )
    return dados(resposta).map(mapGrupo)
  },

  async criarGrupo(
    professorId: number,
    dataId: number,
    tema: string,
    integrantesIds: number[],
  ): Promise<ReturnType<typeof mapGrupo>> {
    const resposta = await api.post<Parameters<typeof mapGrupo>[0]>(
      `/api/professor/grupos/cadastrar.php?professor_id=${professorId}&data_id=${dataId}`,
      { tema, integrantesIds },
    )
    return mapGrupo(dados(resposta))
  },

  async editarGrupo(
    professorId: number,
    grupoId: number | string,
    tema: string,
    integrantesIds: number[],
  ): Promise<ReturnType<typeof mapGrupo>> {
    const resposta = await api.put<Parameters<typeof mapGrupo>[0]>(
      `/api/professor/grupos/editar.php?professor_id=${professorId}&id=${toApiId(grupoId)}`,
      { tema, integrantesIds },
    )
    return mapGrupo(dados(resposta))
  },

  async excluirGrupo(professorId: number, grupoId: number | string): Promise<void> {
    await api.del(`/api/professor/grupos/excluir.php?professor_id=${professorId}&id=${toApiId(grupoId)}`)
  },

  async sortearGrupos(
    professorId: number,
    dataId: number,
    gruposIds: number[],
  ): Promise<ReturnType<typeof mapGrupo>[]> {
    const resposta = await api.post<Parameters<typeof mapGrupo>[0][]>(
      `/api/professor/grupos/sortear.php?professor_id=${professorId}&data_id=${dataId}`,
      { gruposIds },
    )
    return dados(resposta).map(mapGrupo)
  },

  async listarPresencas(
    professorId: number,
    dataId: number,
  ): Promise<Array<{ alunoId: number; nome: string; presente: boolean }>> {
    const resposta = await api.get<Array<{ alunoId: number; nome: string; presente: boolean }>>(
      `/api/professor/presencas/listar.php?professor_id=${professorId}&data_id=${dataId}`,
    )
    return dados(resposta)
  },

  async salvarChamada(
    professorId: number,
    dataId: number,
    presencas: Array<{ alunoId: number; presente: boolean }>,
  ): Promise<Array<{ alunoId: number; nome: string; presente: boolean }>> {
    const resposta = await api.post<Array<{ alunoId: number; nome: string; presente: boolean }>>(
      `/api/professor/presencas/salvar.php?professor_id=${professorId}&data_id=${dataId}`,
      { presencas },
    )
    return dados(resposta)
  },

  async carregarApresentacao(professorId: number, dataId: number) {
    const resposta = await api.get<{
      seminarioId: number
      dataId: number
      dataSessao: string
      status: string
      grupos: Array<{
        id: number
        tema: string
        ordem_apresentacao: number | null
        status: string
        integrantes_ids: number[]
        avaliado: boolean
        notas?: Array<{ criterioId: number; alunoId: number | null; nota: number }>
        feedback?: string
      }>
      gruposAvaliados: number
      totalGrupos: number
    }>(`/api/professor/apresentacao/carregar.php?professor_id=${professorId}&data_id=${dataId}`)
    return dados(resposta)
  },

  async salvarAvaliacao(
    professorId: number,
    dataId: number,
    grupoId: number,
    notas: Array<{ criterioId: number; alunoId: number | null; nota: number }>,
    feedback: string,
  ) {
    const resposta = await api.post<unknown>(
      `/api/professor/apresentacao/salvar-avaliacao.php?professor_id=${professorId}&data_id=${dataId}&grupo_id=${grupoId}`,
      { notas, feedback },
    )
    return resposta
  },

  async encerrarApresentacao(professorId: number, dataId: number) {
    const resposta = await api.post<{ dataId: number; status: string; gruposPendentes: number }>(
      `/api/professor/apresentacao/encerrar.php?professor_id=${professorId}&data_id=${dataId}`,
      {},
    )
    return dados(resposta)
  },

  async compilarResultados(professorId: number, dataId: number) {
    const resposta = await api.get<ResultadoDTO>(
      `/api/professor/resultados/compilar.php?professor_id=${professorId}&data_id=${dataId}`,
    )
    return dados(resposta)
  },
}
