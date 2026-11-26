'use client'

import * as React from 'react'
import { toast } from 'sonner'
import { avaliacoesIniciais, seminarios as seminariosMock } from './mock-data'
import { adminService } from './services'
import type {
  Avaliacao,
  LiveState,
  Role,
  Seminario,
  User,
} from './types'

interface AppContextValue {
  currentUser: User | null
  carregandoPerfil: boolean
  usuarios: User[]
  carregandoUsuarios: boolean
  seminarios: Seminario[]
  avaliacoes: Avaliacao[]
  live: LiveState

  loginAs: (user: User) => void
  logout: () => void

  addUsuario: (u: Omit<User, 'id'>) => Promise<void>
  updateUsuario: (id: string, patch: Partial<User>) => Promise<void>
  toggleUsuarioStatus: (id: string) => Promise<void>
  removeUsuarios: (ids: string[]) => Promise<void>

  addSeminario: (s: Omit<Seminario, 'id'>) => string
  updateSeminario: (id: string, patch: Partial<Seminario>) => void
  removeSeminario: (id: string) => void
  setPresenca: (seminarioId: string, alunoId: string, presente: boolean, dataSessao?: string) => void
  salvarChamada: (seminarioId: string, dataSessao: string) => void
  atualizarGruposSessao: (seminarioId: string, dataSessao: string, grupos: Seminario['grupos']) => void
  finalizarSessao: (seminarioId: string, dataSessao: string) => void
  sortearOrdem: (seminarioId: string, dataSessao: string) => void

  addAvaliacao: (a: Omit<Avaliacao, 'id' | 'criadoEm'>) => void

  // Apresentação ao vivo
startLive: (
  seminarioId: string,
  grupoId: string,
  tempoApresentacaoMin?: number) => void
  pauseLive: () => void
  resumeLive: () => void
  resetLive: () => void
  finishLive: () => void
selectLiveGroup: (
  seminarioId: string,
  grupoId: string,
  tempoApresentacaoMin?: number
) => void
  stopLive: () => void
}

const AppContext = React.createContext<AppContextValue | null>(null)

const PERFIL_SESSION_KEY = 'sgs-perfil-atual'

function perfilArmazenado(): User | null {
  if (typeof window === 'undefined') return null
  try {
    const bruto = window.sessionStorage.getItem(PERFIL_SESSION_KEY)
    return bruto ? (JSON.parse(bruto) as User) : null
  } catch {
    return null
  }
}

function mensagemErro(erro: unknown): string {
  return erro instanceof Error ? erro.message : 'Erro inesperado. Tente novamente.'
}

const initialLive: LiveState = {
  seminarioId: '',
  grupoId: '',
  status: 'aguardando',
  running: false,
  durationSec: 15 * 60,
  remainingSec: 15 * 60,
}

export function AppProvider({ children }: { children: React.ReactNode }) {
  // Valor inicial idêntico no servidor e no cliente para evitar hydration mismatch.
  // O perfil salvo é lido depois da hidratação, em useEffect.
  const [currentUser, setCurrentUser] = React.useState<User | null>(null)
  const [carregandoPerfil, setCarregandoPerfil] = React.useState(true)
  const [usuarios, setUsuarios] = React.useState<User[]>([])
  const [carregandoUsuarios, setCarregandoUsuarios] = React.useState(true)
  const [seminarios, setSeminarios] = React.useState<Seminario[]>(seminariosMock)
  const [avaliacoes, setAvaliacoes] = React.useState<Avaliacao[]>(avaliacoesIniciais)
  const [live, setLive] = React.useState<LiveState>(initialLive)

  React.useEffect(() => {
    const perfil = perfilArmazenado()
    if (perfil) setCurrentUser(perfil)
    setCarregandoPerfil(false)
  }, [])

  React.useEffect(() => {
    let ativo = true
    setCarregandoUsuarios(true)
    Promise.all([adminService.listarAlunos(), adminService.listarProfessores()])
      .then(([alunos, professores]) => {
        if (ativo) setUsuarios([...alunos, ...professores])
      })
      .catch(() => {
        if (ativo) toast.error('Não foi possível carregar os usuários da API.')
      })
      .finally(() => {
        if (ativo) setCarregandoUsuarios(false)
      })
    return () => {
      ativo = false
    }
  }, [])

  // Cronômetro sincronizado na sessão.
  React.useEffect(() => {
    if (!live.running) return
    const t = setInterval(() => {
      setLive((prev) => {
        if (!prev.running) return prev
        const next = Math.max(0, prev.remainingSec - 1)
        return {
          ...prev,
          remainingSec: next,
          running: next > 0,
        }
      })
    }, 1000)
    return () => clearInterval(t)
  }, [live.running])

  const loginAs = React.useCallback((user: User) => {
    setCurrentUser(user)
    if (typeof window !== 'undefined') {
      window.sessionStorage.setItem(PERFIL_SESSION_KEY, JSON.stringify(user))
    }
  }, [])

  const logout = React.useCallback(() => {
    setCurrentUser(null)
    setLive(initialLive)
    if (typeof window !== 'undefined') {
      window.sessionStorage.removeItem(PERFIL_SESSION_KEY)
    }
  }, [])

  const addUsuario = React.useCallback(async (u: Omit<User, 'id'>) => {
    if (u.role !== 'aluno' && u.role !== 'professor') return
    const comum = { nome: u.nome, email: u.email, registro: u.registro }
    try {
      const criado = u.role === 'aluno'
        ? await adminService.cadastrarAluno({
            ...comum,
            anoMatricula: u.anoMatricula ?? 2026,
            semestreEntrada: u.semestreEntrada ?? '1',
          })
        : await adminService.cadastrarProfessor(comum)
      setUsuarios((prev) => [...prev, criado])
    } catch (erro) {
      toast.error(mensagemErro(erro))
      throw erro
    }
  }, [])

  const updateUsuario = React.useCallback(async (id: string, patch: Partial<User>) => {
    const atual = usuarios.find((u) => u.id === id)
    if (!atual || (atual.role !== 'aluno' && atual.role !== 'professor')) return
    const mesclado = { ...atual, ...patch }
    const comum = {
      nome: mesclado.nome,
      email: mesclado.email,
      registro: mesclado.registro,
      status: mesclado.status,
    }
    try {
      const atualizado = mesclado.role === 'aluno'
        ? await adminService.editarAluno(id, {
            ...comum,
            anoMatricula: mesclado.anoMatricula ?? 2026,
            semestreEntrada: mesclado.semestreEntrada ?? '1',
          })
        : await adminService.editarProfessor(id, comum)
      setUsuarios((prev) => prev.map((u) => (u.id === id ? atualizado : u)))
    } catch (erro) {
      toast.error(mensagemErro(erro))
      throw erro
    }
  }, [usuarios])

  const toggleUsuarioStatus = React.useCallback(async (id: string) => {
    const atual = usuarios.find((u) => u.id === id)
    if (!atual) return
    await updateUsuario(id, { status: atual.status === 'ativo' ? 'inativo' : 'ativo' })
  }, [usuarios, updateUsuario])

  const removeUsuarios = React.useCallback(async (ids: string[]) => {
    const alunos = ids.filter((id) => usuarios.find((u) => u.id === id)?.role === 'aluno')
    const professores = ids.filter((id) => usuarios.find((u) => u.id === id)?.role === 'professor')
    try {
      if (alunos.length) await adminService.excluirAlunosLote(alunos)
      if (professores.length) await adminService.excluirProfessoresLote(professores)
      setUsuarios((prev) => prev.filter((u) => !ids.includes(u.id)))
    } catch (erro) {
      toast.error(mensagemErro(erro))
      throw erro
    }
  }, [usuarios])

  const addSeminario = React.useCallback((s: Omit<Seminario, 'id'>) => {
    const id = `s-${Date.now()}`
    setSeminarios((prev) => [...prev, { ...s, id }])
    return id
  }, [])

  const updateSeminario = React.useCallback(
    (id: string, patch: Partial<Seminario>) => {
      setSeminarios((prev) =>
        prev.map((s) => (s.id === id ? { ...s, ...patch } : s)),
      )
    },
    [],
  )

  const removeSeminario = React.useCallback((id: string) => {
    setSeminarios((prev) => prev.filter((seminario) => seminario.id !== id))
    setAvaliacoes((prev) => prev.filter((avaliacao) => avaliacao.seminarioId !== id))
    setLive((prev) => prev.seminarioId === id ? initialLive : prev)
  }, [])

  const setPresenca = React.useCallback(
    (seminarioId: string, alunoId: string, presente: boolean, dataSessao?: string) => {
      setSeminarios((prev) => prev.map((s) => {
        if (s.id !== seminarioId) return s
        if (!s.sessoes?.length || !dataSessao) return { ...s, presenca: { ...s.presenca, [alunoId]: presente } }
        const sessoes = s.sessoes.map((sessao) => sessao.data === dataSessao ? { ...sessao, presenca: { ...sessao.presenca, [alunoId]: presente }, chamadaRealizada: false } : sessao)
        const primeira = sessoes.find((sessao) => sessao.data === s.data)
        return { ...s, sessoes, presenca: primeira?.presenca ?? s.presenca }
      }))
    },
    [],
  )

  const salvarChamada = React.useCallback((seminarioId: string, dataSessao: string) => {
    setSeminarios((prev) => prev.map((s) => {
      if (s.id !== seminarioId) return s
      const sessoes = s.sessoes?.map((sessao) => sessao.data === dataSessao ? { ...sessao, chamadaRealizada: true } : sessao)
      if (!sessoes?.length) return { ...s, sessoes: [{ data: s.data, horario: s.horario, grupos: s.grupos, presenca: s.presenca, chamadaRealizada: true, status: s.status === 'finalizado' ? 'finalizado' : 'agendado' }] }
      return { ...s, sessoes }
    }))
  }, [])

  const atualizarGruposSessao = React.useCallback((seminarioId: string, dataSessao: string, grupos: Seminario['grupos']) => {
    setSeminarios((prev) => prev.map((s) => {
      if (s.id !== seminarioId) return s
      const sessoes = s.sessoes?.map((sessao) => sessao.data === dataSessao ? { ...sessao, grupos } : sessao)
      if (!sessoes?.length) return { ...s, grupos }
      const primeira = sessoes.find((sessao) => sessao.data === s.data)
      return { ...s, sessoes, grupos: primeira?.grupos ?? s.grupos }
    }))
  }, [])

  const finalizarSessao = React.useCallback((seminarioId: string, dataSessao: string) => {
    setSeminarios((prev) => prev.map((s) => {
      if (s.id !== seminarioId) return s
      const sessoes = s.sessoes?.map((sessao) => sessao.data === dataSessao ? { ...sessao, status: 'finalizado' as const } : sessao)
      if (!sessoes?.length) return { ...s, status: 'finalizado' }
      const primeira = sessoes.find((sessao) => sessao.data === s.data)
      return { ...s, sessoes, status: sessoes.every((sessao) => sessao.status === 'finalizado') ? 'finalizado' : 'agendado', grupos: primeira?.grupos ?? s.grupos, presenca: primeira?.presenca ?? s.presenca }
    }))
  }, [])

  const sortearOrdem = React.useCallback((seminarioId: string, dataSessao: string) => {
    setSeminarios((prev) => prev.map((seminario) => {
      if (seminario.id !== seminarioId) return seminario
      const sessoes = seminario.sessoes?.map((sessao) => {
        if (sessao.data !== dataSessao) return sessao
        const embaralhados = [...sessao.grupos]
        for (let i = embaralhados.length - 1; i > 0; i--) {
          const j = Math.floor(Math.random() * (i + 1))
          ;[embaralhados[i], embaralhados[j]] = [embaralhados[j], embaralhados[i]]
        }
        return { ...sessao, grupos: embaralhados.map((grupo, index) => ({ ...grupo, ordem: index + 1 })) }
      })
      if (!sessoes?.length) {
        const embaralhados = [...seminario.grupos]
        for (let i = embaralhados.length - 1; i > 0; i--) {
          const j = Math.floor(Math.random() * (i + 1))
          ;[embaralhados[i], embaralhados[j]] = [embaralhados[j], embaralhados[i]]
        }
        return { ...seminario, grupos: embaralhados.map((grupo, index) => ({ ...grupo, ordem: index + 1 })) }
      }
      const sessaoPrincipal = sessoes.find((sessao) => sessao.data === seminario.data)
      return { ...seminario, sessoes, grupos: sessaoPrincipal?.grupos ?? seminario.grupos }
    }))
  }, [])

  const addAvaliacao = React.useCallback(
    (a: Omit<Avaliacao, 'id' | 'criadoEm'>) => {
      setAvaliacoes((prev) => {
        const existingIndex = prev.findIndex((item) => item.seminarioId === a.seminarioId && item.dataSessao === a.dataSessao && item.grupoId === a.grupoId && item.avaliadorId === a.avaliadorId && item.tipo === a.tipo)
        const updated = { ...a, id: existingIndex >= 0 ? prev[existingIndex].id : `av-${Date.now()}`, criadoEm: new Date().toISOString() }
        return existingIndex >= 0 ? prev.map((item, index) => index === existingIndex ? updated : item) : [...prev, updated]
      })
    },
    [],
  )


const startLive = React.useCallback(
  (
    seminarioId: string,
    grupoId: string,
    tempoApresentacaoMin = 15
  ) => {
    const dur =
      Number.isFinite(tempoApresentacaoMin) &&
      tempoApresentacaoMin > 0
        ? Math.round(tempoApresentacaoMin * 60)
        : 15 * 60

    setLive({
      seminarioId,
      grupoId,
      status: 'em_andamento',
      running: true,
      durationSec: dur,
      remainingSec: dur,
    })

    updateSeminario(seminarioId, {
      status: 'em_andamento',
    })
  },
  [updateSeminario],
)


const selectLiveGroup = React.useCallback(
  (
    seminarioId: string,
    grupoId: string,
    tempoApresentacaoMin = 15
  ) => {
    const dur =
      Number.isFinite(tempoApresentacaoMin) &&
      tempoApresentacaoMin > 0
        ? Math.round(tempoApresentacaoMin * 60)
        : 15 * 60

    setLive({
      seminarioId,
      grupoId,
      status: 'aguardando',
      running: false,
      durationSec: dur,
      remainingSec: dur,
    })
  },
  [],
)

  const pauseLive = React.useCallback(
    () => setLive((p) => ({ ...p, running: false })),
    [],
  )
  const resumeLive = React.useCallback(
    () =>
      setLive((p) => ({
        ...p,
        running: p.remainingSec > 0,
        status: 'em_andamento',
      })),
    [],
  )
  const resetLive = React.useCallback(
    () =>
      setLive((p) => ({
        ...p,
        running: false,
        status: 'aguardando',
        remainingSec: p.durationSec,
      })),
    [],
  )
  const finishLive = React.useCallback(
    () => setLive((p) => ({ ...p, running: false, status: 'finalizada' })),
    [],
  )
  const stopLive = React.useCallback(() => setLive(initialLive), [])

  const value: AppContextValue = {
    currentUser,
    carregandoPerfil,
    usuarios,
    carregandoUsuarios,
    seminarios,
    avaliacoes,
    live,
    loginAs,
    logout,
    addUsuario,
    updateUsuario,
    toggleUsuarioStatus,
    removeUsuarios,
    addSeminario,
    updateSeminario,
    removeSeminario,
    setPresenca,
    salvarChamada,
    atualizarGruposSessao,
    finalizarSessao,
    sortearOrdem,
    addAvaliacao,
    startLive,
    pauseLive,
    resumeLive,
    resetLive,
    finishLive,
    selectLiveGroup,
    stopLive,
  }

  return <AppContext.Provider value={value}>{children}</AppContext.Provider>
}

export function useApp() {
  const ctx = React.useContext(AppContext)
  if (!ctx) throw new Error('useApp deve ser usado dentro de AppProvider')
  return ctx
}

// Selectors utilitários
export function calcularNotaGrupo(
  seminario: Seminario,
  avaliacoes: Avaliacao[],
  grupoId: string,
): {
  notaProfessor: number | null
  mediaPares: number | null
  notaFinal: number | null
  totalPares: number
} {
  const doGrupo = avaliacoes.filter(
    (a) => a.seminarioId === seminario.id && a.grupoId === grupoId,
  )
  const pesoTotal = seminario.criterios.reduce((acc, c) => acc + c.peso, 0) || 1

  const grupo = [...seminario.grupos, ...(seminario.sessoes ?? []).flatMap((sessao) => sessao.grupos)].find((item) => item.id === grupoId)
  const notaPonderada = (a: Avaliacao) =>
    seminario.criterios.reduce((acc, criterio) => {
      const notaCriterio = criterio.tipo === 'individual' && grupo?.integrantesIds.length
        ? (() => {
            const notasIntegrantes = grupo.integrantesIds.map((alunoId) => a.notas[`${criterio.id}:${alunoId}`]).filter((nota): nota is number => nota !== undefined)
            return notasIntegrantes.length ? notasIntegrantes.reduce((total, nota) => total + nota, 0) / notasIntegrantes.length : (a.notas[criterio.id] ?? 0)
          })()
        : (a.notas[criterio.id] ?? 0)
      return acc + notaCriterio * criterio.peso
    }, 0) / pesoTotal

  const prof = doGrupo.find((a) => a.tipo === 'professor')
  const pares = doGrupo.filter((a) => a.tipo === 'par')

  const notaProfessor = prof ? notaPonderada(prof) : null
  const mediaPares =
    pares.length > 0
      ? pares.reduce((acc, a) => acc + notaPonderada(a), 0) / pares.length
      : null

  let notaFinal: number | null = null
  const pp = seminario.pesoProfessor
  if (notaProfessor != null && mediaPares != null) {
    notaFinal = notaProfessor * pp + mediaPares * (1 - pp)
  } else if (notaProfessor != null) {
    notaFinal = notaProfessor
  } else if (mediaPares != null) {
    notaFinal = mediaPares
  }

  return { notaProfessor, mediaPares, notaFinal, totalPares: pares.length }
}
