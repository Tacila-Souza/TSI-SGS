'use client'

import * as React from 'react'
import Link from 'next/link'
import { useParams, useRouter, useSearchParams } from 'next/navigation'
import { CheckCircle, Circle, Pencil, Save, Trophy } from 'lucide-react'
import { toast } from 'sonner'
import { useApp } from '@/lib/app-context'
import { formatarDataCurta } from '@/lib/format'
import { obterSessoesSeminario } from '@/lib/types'
import type { Seminario } from '@/lib/types'
import { professorService, toApiId } from '@/lib/services'
import { PageHeader } from '@/components/page-header'
import { LiveTimer } from '@/components/seminario/live-timer'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'

type GrupoApresentacao = {
  id: number
  tema: string
  ordem_apresentacao: number | null
  status: string
  integrantes_ids: number[]
  avaliado: boolean
  notas?: Array<{ criterioId: number; alunoId: number | null; nota: number }>
  feedback?: string
}

function mensagemErro(erro: unknown): string {
  return erro instanceof Error ? erro.message : 'Erro inesperado.'
}

export default function ApresentarSeminarioPage() {
  const { id } = useParams<{ id: string }>()
  const router = useRouter()
  const searchParams = useSearchParams()
  const { live, startLive, pauseLive, resumeLive, resetLive, finishLive, selectLiveGroup } = useApp()

  const professorId = React.useMemo(() => {
    const configurado = process.env.NEXT_PUBLIC_PROFESSOR_ID
    return configurado ? Number(configurado) : 1
  }, [])

  const [seminario, setSeminario] = React.useState<Seminario | null>(null)
  const [grupos, setGrupos] = React.useState<GrupoApresentacao[]>([])
  const [carregando, setCarregando] = React.useState(true)
  const [dataSessao, setDataSessao] = React.useState('')
  const [grupoSelecionado, setGrupoSelecionado] = React.useState<number | null>(null)
  const [notas, setNotas] = React.useState<Record<string, number | undefined>>({})
  const [feedback, setFeedback] = React.useState('')
  const [notaErro, setNotaErro] = React.useState('')
  const [showFinish, setShowFinish] = React.useState(false)
  const [showAttendanceAlert, setShowAttendanceAlert] = React.useState(false)
  const [erroCarregamento, setErroCarregamento] = React.useState('')
  const inicializado = React.useRef(false)

  const sessoes = seminario ? obterSessoesSeminario(seminario) : []
  const sessao = sessoes.find((item) => item.data === dataSessao) ?? sessoes[0]
  const dataId = sessao?.id

  
const carregar = React.useCallback(async () => {
  setCarregando(true)
  setErroCarregamento('')

  try {
    const detalhes = await professorService.detalhesSeminario(
      professorId,
      id
    )

    setSeminario(detalhes)

    const sessoesDisponiveis = detalhes.sessoes ?? []

    const dataPretendida =
      dataSessao || searchParams.get('data') || detalhes.data

    const sessaoAtual =
      sessoesDisponiveis.find(
        (sessao) => sessao.data === dataPretendida
      ) ?? sessoesDisponiveis[0]

    if (!sessaoAtual) {
      setGrupos([])
      return
    }

    if (dataSessao !== sessaoAtual.data) {
      setDataSessao(sessaoAtual.data)
    }

    if (sessaoAtual.id == null) {
      setGrupos([])
      return
    }

    const apresentacao = await professorService.carregarApresentacao(
      professorId,
      sessaoAtual.id
    )

    setGrupos(apresentacao.grupos as GrupoApresentacao[])
  } catch (erro) {
    setGrupos([])
    setErroCarregamento(mensagemErro(erro))
    toast.error(mensagemErro(erro))
  } finally {
    setCarregando(false)
  }
}, [professorId, id, searchParams, dataSessao])

  React.useEffect(() => { void carregar() }, [carregar])

  const primeiroGrupo = grupos.slice().sort((a, b) => (a.ordem_apresentacao ?? 99) - (b.ordem_apresentacao ?? 99))[0]
  const grupoAtual = grupos.find((grupo) => grupo.id === grupoSelecionado) ?? primeiroGrupo

  React.useEffect(() => {
    if (!grupoAtual || grupoSelecionado !== null) return
    setGrupoSelecionado(grupoAtual.id)
    selectLiveGroup(id, `g-${grupoAtual.id}`)
  }, [grupoAtual, grupoSelecionado, id, selectLiveGroup])

  React.useEffect(() => {
    if (!grupoAtual) return
    const mapa: Record<string, number> = {}
    for (const nota of grupoAtual.notas ?? []) {
      const chave = nota.alunoId === null
        ? `cr-${nota.criterioId}`
        : `cr-${nota.criterioId}:u-al-${nota.alunoId}`
      mapa[chave] = nota.nota
    }
    setNotas(mapa)
    setFeedback(grupoAtual.feedback ?? '')
    setNotaErro('')
}, [grupoAtual])

  if (carregando && !seminario) {
    return <p className="text-sm text-muted-foreground">Carregando apresentação…</p>
  }

  if (!seminario || !sessao || !dataId) {
    return <p className="text-sm text-muted-foreground">Seminário não encontrado.</p>
  }
  const sessaoFinalizada = sessao.status === 'finalizado'
  const chamadaRealizada = Object.values(sessao.presenca).some(Boolean)
  const gruposAvaliados = grupos.filter((grupo) => grupo.avaliado).length
  const todosAvaliados = grupos.length > 0 && gruposAvaliados === grupos.length

if (erroCarregamento) {
  return (
    <Card className="mx-auto w-full max-w-xl">
      <CardHeader>
        <CardTitle>Erro ao carregar apresentação</CardTitle>
        <p className="text-sm text-muted-foreground">
          {erroCarregamento}
        </p>
      </CardHeader>
      <CardContent>
        <Button onClick={() => void carregar()}>
          Tentar novamente
        </Button>
      </CardContent>
    </Card>
  )
}
  if (!grupos.length || !chamadaRealizada) {
    const faltaGrupo = !grupos.length
    return (
      <Card className="mx-auto w-full max-w-xl">
        <CardHeader>
          <CardTitle>Apresentação não liberada</CardTitle>
          <p className="text-sm text-muted-foreground">
            {faltaGrupo && !chamadaRealizada
              ? 'Forme ao menos um grupo e registre e salve a chamada desta data antes de iniciar.'
              : faltaGrupo
                ? 'Forme ao menos um grupo para esta data antes de iniciar.'
                : 'Registre e salve a chamada desta data antes de iniciar a apresentação.'}
          </p>
        </CardHeader>
        <CardContent>
          <Button variant="outline" nativeButton={false} render={<Link href={`/professor/seminarios/${id}?data=${encodeURIComponent(sessao.data)}${faltaGrupo ? '' : '&tab=presenca'}`} />}>
            Voltar e concluir preparação
          </Button>
        </CardContent>
      </Card>
    )
  }

  function selecionarGrupo(grupoId: number) {
    setGrupoSelecionado(grupoId)
    selectLiveGroup(id, `g-${grupoId}`)
  }

  async function salvarAvaliacao() {
    if (!grupoAtual) return
    const notasPayload: Array<{ criterioId: number; alunoId: number | null; nota: number }> = []
    for (const criterio of seminario!.criterios) {
      const criterioId = toApiId(criterio.id)
      if (criterio.tipo === 'grupo') {
        const nota = notas[criterio.id]
        if (nota === undefined) {
          setNotaErro('Preencha uma nota para cada critério antes de salvar.')
          return
        }
        notasPayload.push({ criterioId, alunoId: null, nota })
      } else {
        for (const alunoId of grupoAtual.integrantes_ids) {
          const nota = notas[`${criterio.id}:u-al-${alunoId}`]
          if (nota === undefined) {
            setNotaErro('Preencha uma nota para cada critério antes de salvar.')
            return
          }
          notasPayload.push({ criterioId, alunoId, nota })
        }
      }
    }
    try {
      await professorService.salvarAvaliacao(professorId, dataId!, grupoAtual.id, notasPayload, feedback)
   const atualizado = await professorService.carregarApresentacao(
  professorId,
  dataId!
)

setGrupos(atualizado.grupos as GrupoApresentacao[])
setNotaErro('')

toast.success('Avaliação salva e grupo atualizado com sucesso.')
    } catch (erro) {
      toast.error(mensagemErro(erro))
    }
  }


async function concluirSessao() {
  try {
    await professorService.encerrarApresentacao(
      professorId,
      dataId!
    )

    finishLive()

    // Busca novamente o status da sessão no banco
    await carregar()

    setShowFinish(true)

    toast.success('Sessão finalizada com sucesso.')
  } catch (erro) {
    toast.error(mensagemErro(erro))
  }
}


  return (
    <div className="flex flex-col gap-6">
      <PageHeader
        title="Apresentação ao vivo"
        description={`${seminario.titulo} · ${formatarDataCurta(sessao.data)} — ${gruposAvaliados}/${grupos.length} avaliados`}
      >
        <Button variant="outline" nativeButton={false} render={<Link href={`/professor/seminarios/${id}?data=${encodeURIComponent(sessao.data)}`} />}>
          Voltar ao seminário
        </Button>
      </PageHeader>

{sessaoFinalizada && (
  <div
    role="status"
    className="flex items-center gap-3 rounded-lg border border-emerald-500/40 bg-emerald-500/10 p-4 text-emerald-600"
  >
    <CheckCircle className="size-6 shrink-0" />

    <div>
      <p className="font-semibold">
        Trabalho encerrado!
      </p>
      <p className="text-sm">
        A apresentação desta data foi finalizada.
        As avaliações permanecem salvas no sistema.
      </p>
    </div>
  </div>
)}

      <div className="grid gap-6 lg:grid-cols-[280px_minmax(0,1fr)]">
        <Card className="h-fit">
          <CardHeader><CardTitle className="text-base">Grupos</CardTitle></CardHeader>
          <CardContent className="flex flex-col gap-2">
            {grupos.slice().sort((a, b) => (a.ordem_apresentacao ?? 99) - (b.ordem_apresentacao ?? 99)).map((grupo) => (
              <div key={grupo.id} className="flex flex-col gap-2 rounded-lg border p-2">
                <button type="button" onClick={() => selecionarGrupo(grupo.id)} className={`flex items-center justify-between rounded-md p-1 text-left transition-colors hover:bg-muted ${grupo.id === grupoSelecionado ? 'bg-primary/5' : ''}`}>
                  <span className="flex items-center gap-2">
                    {grupo.avaliado ? <CheckCircle className="text-emerald-600" /> : <Circle className="text-muted-foreground" />}
                    <span>
                      <span className="block text-sm font-medium">Grupo {grupo.ordem_apresentacao ?? grupo.id}</span>
                      <span className="block text-xs text-muted-foreground">{grupo.tema}</span>
                    </span>
                  </span>
                  <Badge variant={grupo.avaliado ? 'outline' : 'secondary'} className={grupo.avaliado ? 'border-emerald-300 bg-emerald-100 text-emerald-900 font-semibold' : ''}>
                    {grupo.avaliado ? 'Avaliado' : 'Pendente'}
                  </Badge>
                </button>
                {grupo.avaliado && (
                  <Button type="button" size="sm" variant="outline" className="w-full" onClick={() => selecionarGrupo(grupo.id)}>
                    <Pencil data-icon="inline-start" />Editar Avaliação
                  </Button>
                )}
              </div>
            ))}
          </CardContent>
        </Card>

        <div className="flex flex-col gap-6">
          <Card>
            <CardContent className="flex flex-col items-center gap-4 py-8">
              <LiveTimer
                live={live}
                canStart={grupos.length > 0}
                onStart={() => {
                  if (!chamadaRealizada) { setShowAttendanceAlert(true); return }
                  if (grupoAtual) startLive(id, `g-${grupoAtual.id}`)
                }}
                onPause={pauseLive}
                onResume={resumeLive}
                onReset={resetLive}
              />
              <div className="flex w-full max-w-xl flex-col items-center gap-3">
                <p className="rounded-md border bg-muted/50 px-4 py-3 text-center text-sm text-muted-foreground">
                  Você pode clicar em “Editar Avaliação” em qualquer grupo acima para ajustar notas antes de finalizar esta data.
                </p>
                <div className="flex flex-wrap justify-center gap-2">
                  {todosAvaliados ? (
                    <Button onClick={concluirSessao}><Trophy data-icon="inline-start" />Finalizar essa data</Button>
                  ) : (
                    <Button variant="outline" onClick={() => { finishLive(); toast.success('Apresentação encerrada. Avalie os grupos restantes para finalizar esta data.') }}>
                      Encerrar apresentação
                    </Button>
                  )}
                </div>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                {grupoAtual?.avaliado ? <Pencil /> : <Save />}
                {grupoAtual ? `Avaliação — Grupo ${grupoAtual.ordem_apresentacao ?? grupoAtual.id}` : 'Selecione um grupo'}
              </CardTitle>
            </CardHeader>
            <CardContent className="flex flex-col gap-4">
              {grupoAtual?.avaliado && (
                <div className="rounded-md border border-emerald-300 bg-emerald-100 px-4 py-3 text-sm font-semibold text-emerald-900">
                  Grupo já avaliado. Você pode revisar e salvar as notas novamente.
                </div>
              )}
              {seminario.criterios.filter((criterio) => criterio.tipo === 'grupo').map((criterio) => (
                <div key={criterio.id} className="flex flex-col gap-2">
                  <Label htmlFor={`nota-${criterio.id}`}>{criterio.nome} ({criterio.peso}%)</Label>
                  <Input
                    id={`nota-${criterio.id}`}
                    type="number"
                    min={0}
                    max={10}
                    step="0.5"
                    value={notas[criterio.id] ?? ''}
                    onChange={(event) => {
                      const raw = event.target.value
                      if (raw === '') { setNotas((itens) => ({ ...itens, [criterio.id]: undefined })); return }
                      const nota = Number(raw)
                      if (!Number.isFinite(nota) || nota < 0 || nota > 10) { setNotaErro('As notas devem estar entre 0 e 10.'); return }
                      setNotas((itens) => ({ ...itens, [criterio.id]: nota }))
                      setNotaErro('')
                    }}
                  />
                </div>
              ))}
              {seminario.criterios.filter((criterio) => criterio.tipo === 'individual').map((criterio) => (
                <fieldset key={criterio.id} className="flex flex-col gap-3 rounded-lg border border-primary/30 bg-primary/[0.03] p-4">
                  <legend className="px-2 text-sm font-semibold">{criterio.nome} ({criterio.peso}%) — por integrante</legend>
                  {grupoAtual.integrantes_ids.map((alunoId) => {
                    const chave = `${criterio.id}:u-al-${alunoId}`
                    return (
                      <div key={chave} className="flex flex-col gap-2">
                        <Label htmlFor={`nota-${chave}`}>Aluno {alunoId}</Label>
                        <Input
                          id={`nota-${chave}`}
                          type="number"
                          min={0}
                          max={10}
                          step="0.5"
                          value={notas[chave] ?? ''}
                          onChange={(event) => {
                            const raw = event.target.value
                            if (raw === '') { setNotas((itens) => ({ ...itens, [chave]: undefined })); return }
                            const nota = Number(raw)
                            if (!Number.isFinite(nota) || nota < 0 || nota > 10) { setNotaErro('As notas devem estar entre 0 e 10.'); return }
                            setNotas((itens) => ({ ...itens, [chave]: nota }))
                            setNotaErro('')
                          }}
                        />
                      </div>
                    )
                  })}
                </fieldset>
              ))}
              {notaErro && <p className="text-sm font-medium text-destructive">{notaErro}</p>}
              <div className="flex flex-col gap-2">
                <Label htmlFor="feedback">Feedback</Label>
                <Textarea id="feedback" value={feedback} onChange={(event) => setFeedback(event.target.value)} placeholder="Comentários sobre a apresentação" />
              </div>
              <div className="flex justify-end">
                <Button onClick={salvarAvaliacao} disabled={!grupoAtual}>
                  <Save data-icon="inline-start" />Salvar avaliação
                </Button>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>

      <Dialog open={showAttendanceAlert} onOpenChange={setShowAttendanceAlert}>
        <DialogContent>
          <DialogHeader><DialogTitle>Chamada obrigatória</DialogTitle><DialogDescription>Registre e salve a presença dos alunos matriculados nesta disciplina antes de iniciar as apresentações desta data.</DialogDescription></DialogHeader>
          <DialogFooter>
            <Button variant="outline" onClick={() => setShowAttendanceAlert(false)}>Agora não</Button>
            <Button nativeButton={false} render={<Link href={`/professor/seminarios/${id}?data=${encodeURIComponent(sessao.data)}&tab=presenca`} />}>Registrar chamada</Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      <Dialog open={showFinish} onOpenChange={setShowFinish}>
        <DialogContent className="sm:max-w-md">
          <DialogHeader><DialogTitle>Apresentações da data concluídas</DialogTitle><DialogDescription>Todos os grupos desta data foram avaliados. Escolha o próximo passo.</DialogDescription></DialogHeader>
          <div className="mt-4 flex w-full flex-col gap-3">
            <Button className="w-full" variant="outline" onClick={() => setShowFinish(false)}>Editar a avaliação</Button>
            <Button className="w-full" nativeButton={false} render={<Link href={`/professor/seminarios/${id}?data=${encodeURIComponent(sessao.data)}&tab=resultados`} />}>Ver Resultado Final da Turma</Button>
            <Button className="w-full" variant="secondary" onClick={() => router.push('/professor')}>Ir para o Dashboard Inicial</Button>
          </div>
        </DialogContent>
      </Dialog>
    </div>
  )
}
