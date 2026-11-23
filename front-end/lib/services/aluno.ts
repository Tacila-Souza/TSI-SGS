import { api } from './api'
import { mapCriterio, mapGrupo, mapSeminario, toApiId } from './mappers'
import type { ResultadoDTO } from '@/lib/types-admin'
import type { Seminario } from '@/lib/types'

type SeminarioDTO = Parameters<typeof mapSeminario>[0]
type CriterioDTO = Parameters<typeof mapCriterio>[0]
type GrupoDTO = Parameters<typeof mapGrupo>[0]

interface Envelope<T> {
  sucesso: boolean
  dados?: T
}

function dados<T>(envelope: Envelope<T>): T {
  if (envelope.dados === undefined) throw new Error('A API não retornou dados.')
  return envelope.dados
}

export interface FormularioAvaliacao {
  seminarioId: number
  dataId: number
  dataSessao: string
  criterios: Array<{ id: number; nome: string; peso: number; tipo: 'individual' | 'grupo' }>
  grupos: Array<{
    id: number
    nome: string
    tema: string
    integrantesIds: number[]
    integrantes: Array<{ alunoId: number; nome: string }>
    ordem: number
  }>
  minhasAvaliacoes: Array<{
    grupoId: number
    notas: Array<{ criterioId: number; alunoId: number | null; nota: number }>
    feedback: string
  }>
}

export const alunoService = {
  async listarSeminarios(alunoId: number): Promise<Seminario[]> {
    const resposta = await api.get<SeminarioDTO[]>(
      `/api/aluno/seminarios/listar.php?aluno_id=${alunoId}`,
    )
    return dados(resposta).map(mapSeminario)
  },

  async detalhesSeminario(alunoId: number, id: number | string): Promise<Seminario> {
    const resposta = await api.get<SeminarioDTO>(
      `/api/aluno/seminarios/detalhes.php?aluno_id=${alunoId}&id=${toApiId(id)}`,
    )
    return mapSeminario(dados(resposta))
  },

  async formulario(alunoId: number, dataId: number): Promise<FormularioAvaliacao> {
    const resposta = await api.get<FormularioAvaliacao>(
      `/api/aluno/avaliacoes/formulario.php?aluno_id=${alunoId}&data_id=${dataId}`,
    )
    return dados(resposta)
  },

  async salvarAvaliacao(
    alunoId: number,
    dataId: number,
    grupoId: number,
    notas: Array<{ criterioId: number; alunoId: number | null; nota: number }>,
    feedback: string,
  ) {
    const resposta = await api.post<FormularioAvaliacao>(
      `/api/aluno/avaliacoes/salvar.php?aluno_id=${alunoId}&data_id=${dataId}&grupo_id=${grupoId}`,
      { notas, feedback },
    )
    return dados(resposta)
  },

  async compilarResultados(alunoId: number, dataId: number): Promise<ResultadoDTO> {
    const resposta = await api.get<ResultadoDTO>(
      `/api/aluno/resultados/compilar.php?aluno_id=${alunoId}&data_id=${dataId}`,
    )
    return dados(resposta)
  },
}
