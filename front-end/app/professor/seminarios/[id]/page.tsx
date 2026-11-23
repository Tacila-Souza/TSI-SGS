'use client'

import Link from 'next/link'
import { ArrowLeft, CalendarDays, CheckCircle2, Edit, Pencil, Play, Search, Shuffle, Trash2, Users } from 'lucide-react'
import { useParams, useRouter, useSearchParams } from 'next/navigation'
import * as React from 'react'
import { useApp } from '@/lib/app-context'
import { toast } from 'sonner'
import { formatarDataCurta } from '@/lib/format'
import { obterSessoesSeminario } from '@/lib/types'
import type { Grupo, Seminario, User } from '@/lib/types'
import type { ResultadoDTO } from '@/lib/types-admin'
import { professorService, toApiId } from '@/lib/services'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Checkbox } from '@/components/ui/checkbox'
import { StatusBadge } from '@/components/status-badge'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { ResultadoView } from '@/components/seminario/resultado-view'
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog'
import { AlertDialog, AlertDialogAction, AlertDialogCancel, AlertDialogContent, AlertDialogDescription, AlertDialogFooter, AlertDialogHeader, AlertDialogTitle } from '@/components/ui/alert-dialog'

function mensagemErro(erro: unknown): string {
  return erro instanceof Error ? erro.message : 'Erro inesperado.'
}

export default function ProfessorSeminarioDetalhePage() {
  const { id } = useParams<{ id: string }>()
  const router = useRouter()
  const searchParams = useSearchParams()

  
const { carregandoPerfil, professorId: professorIdContext, recarregarSeminarios } = useApp()

const professorId = professorIdContext ?? 0


  const [seminario, setSeminario] = React.useState<Seminario | null>(null)
  const [alunos, setAlunos] = React.useState<User[]>([])
  const [presencas, setPresencas] = React.useState<Array<{ alunoId: number; nome: string; presente: boolean }>>([])
  const [carregando, setCarregando] = React.useState(true)
  const [dataSelecionada, setDataSelecionada] = React.useState('')
  const [aba, setAba] = React.useState(searchParams.get('tab') === 'presenca' ? 'presenca' : 'grupos')
  const [dialogGrupoAberto, setDialogGrupoAberto] = React.useState(false)
  const [grupoEmEdicao, setGrupoEmEdicao] = React.useState<string | null>(null)
  const [showStartAlert, setShowStartAlert] = React.useState(false)
  const [showDeleteAlert, setShowDeleteAlert] = React.useState(false)
  const [temaGrupo, setTemaGrupo] = React.useState('')
  const [buscaAluno, setBuscaAluno] = React.useState('')
  const [alunosSelecionados, setAlunosSelecionados] = React.useState<string[]>([])
  const [presencaLocal, setPresencaLocal] = React.useState<Record<string, boolean>>({})
  const [resultado, setResultado] = React.useState<ResultadoDTO | null>(null)
  const chamadaRef = React.useRef<HTMLDivElement>(null)
  const dadosInicializados = React.useRef(false)

  const sessoes = seminario ? obterSessoesSeminario(seminario) : []
  const sessao = sessoes.find((item) => item.data === dataSelecionada) ?? sessoes[0]
  const resultadosDisponiveis = sessao?.status === 'finalizado'

  
const carregar = React.useCallback(async () => {
  if (carregandoPerfil || professorId === 0) {
    setCarregando(false)
    return
  }
  setCarregando(true)

    try {
      const carregado = await professorService.detalhesSeminario(professorId, id)
      setSeminario(carregado)
      void recarregarSeminarios()
      const listaAlunos = await professorService.listarAlunos(professorId, toApiId(carregado.turmaId))
      setAlunos(listaAlunos)
      if (!dadosInicializados.current) {
        dadosInicializados.current = true
        const pretendida = searchParams.get('data')
        const primeira = carregado.sessoes?.[0]?.data ?? carregado.data
        setDataSelecionada(pretendida ?? primeira)
      }
    } catch (erro) {
      toast.error(mensagemErro(erro))
    } finally {
      setCarregando(false)
    }
}, [carregandoPerfil, professorId, id, searchParams, recarregarSeminarios])

  React.useEffect(() => { void carregar() }, [carregar])

  React.useEffect(() => {
    if (!sessao?.id) return
    professorService.listarPresencas(professorId, sessao.id)
      .then((linhas) => {
        setPresencas(linhas)
        setPresencaLocal(Object.fromEntries(linhas.map((linha) => [`u-al-${linha.alunoId}`, linha.presente])))
      })
      .catch((erro) => toast.error(mensagemErro(erro)))
  }, [professorId, sessao?.id])

  React.useEffect(() => {
    if (!resultadosDisponiveis || !sessao?.id) {
      setResultado(null)
      return
    }
    professorService.compilarResultados(professorId, sessao.id)
      .then(setResultado)
      .catch((erro) => toast.error(mensagemErro(erro)))
  }, [professorId, resultadosDisponiveis, sessao?.id])

  React.useEffect(() => {
    if (!sessao) return
    setAba(resultadosDisponiveis ? 'resultados' : 'grupos')
  }, [sessao?.data, resultadosDisponiveis])

  if (carregando && !seminario) {
    return <Card><CardContent className="p-8 text-center">Carregando seminário…</CardContent></Card>
  }

  if (!seminario || !sessao) {
    return <Card><CardContent className="p-8 text-center">Seminário não encontrado.</CardContent></Card>
  }

  const presentes = Object.values(sessao.presenca).filter(Boolean).length
  const chamadaCompleta = Object.keys(sessao.presenca).length > 0

  function abrirCriacaoGrupo() {
    if (resultadosDisponiveis) return
    setGrupoEmEdicao(null)
    setTemaGrupo('')
    setBuscaAluno('')
    setAlunosSelecionados([])
    setDialogGrupoAberto(true)
  }

  function abrirEdicaoGrupo(grupoId: string) {
    if (resultadosDisponiveis) return
    const grupo = sessao.grupos.find((item) => item.id === grupoId)
    if (!grupo) return
    setGrupoEmEdicao(grupo.id)
    setTemaGrupo(grupo.tema)
    setAlunosSelecionados(grupo.integrantesIds)
    setBuscaAluno('')
    setDialogGrupoAberto(true)
  }

  async function criarGrupo() {
    if (resultadosDisponiveis || !sessao?.id) {
      setDialogGrupoAberto(false)
      if (resultadosDisponiveis) toast.error('Os grupos desta data estão bloqueados porque as avaliações foram encerradas.')
      return
    }
    if (!temaGrupo.trim()) {
      toast.error('Informe o tema do grupo.')
      return
    }
    try {
      const payloadIds = alunosSelecionados.map(toApiId)
      if (grupoEmEdicao) {
        await professorService.editarGrupo(professorId, grupoEmEdicao, temaGrupo.trim(), payloadIds)
        toast.success('Grupo atualizado com sucesso.')
      } else {
        await professorService.criarGrupo(professorId, sessao.id, temaGrupo.trim(), payloadIds)
        toast.success('Grupo criado com sucesso.')
      }
      setDialogGrupoAberto(false)
      await carregar()
    } catch (erro) {
      toast.error(mensagemErro(erro))
    }
  }

  async function excluirGrupo(grupoId: string) {
    if (resultadosDisponiveis || !sessao?.id) return
    const grupo = sessao.grupos.find((item) => item.id === grupoId)
    if (!grupo || !window.confirm(`Deseja excluir ${grupo.nome}? Esta ação não pode ser desfeita.`)) return
    try {
      await professorService.excluirGrupo(professorId, grupoId)
      toast.success('Grupo excluído.')
      await carregar()
    } catch (erro) {
      toast.error(mensagemErro(erro))
    }
  }

  async function excluirSeminario() {
    try {
      await professorService.excluirSeminario(professorId, id)
      toast.success('Seminário excluído com sucesso.')
      router.replace('/professor/seminarios')
    } catch (erro) {
      toast.error(mensagemErro(erro))
    }
  }

  function iniciarApresentacao() {
    if (!sessao.grupos.length || !chamadaCompleta) {
      setShowStartAlert(true)
      return
    }
    router.push(`/professor/seminarios/${id}/apresentar?data=${encodeURIComponent(sessao.data)}`)
  }

  async function sortearOrdemDaSessao() {
    if (resultadosDisponiveis || !sessao?.id) return
    try {
      await professorService.sortearGrupos(professorId, sessao.id, sessao.grupos.map((grupo) => toApiId(grupo.id)))
      toast.success('Ordem de apresentação sorteada!', { className: 'border-emerald-500/40' })
      await carregar()
    } catch (erro) {
      toast.error(mensagemErro(erro))
    }
  }

  async function salvarChamada() {
    if (!sessao?.id || Object.values(presencaLocal).filter(Boolean).length < 1) return
    try {
      const linhas = await professorService.salvarChamada(
        professorId,
        sessao.id,
        alunos.map((aluno) => ({
          alunoId: toApiId(aluno.id),
          presente: presencaLocal[aluno.id] === true,
        })),
      )
      setPresencas(linhas)
      setPresencaLocal(Object.fromEntries(linhas.map((linha) => [`u-al-${linha.alunoId}`, linha.presente])))
      toast.success(chamadaCompleta ? 'Chamada atualizada com sucesso.' : 'Chamada salva com sucesso.')
      await carregar()
    } catch (erro) {
      toast.error(mensagemErro(erro))
    }
  }

  function selecionarData(data: string) {
    setDataSelecionada(data)
    const proximaSessao = sessoes.find((item) => item.data === data)
    setAba(proximaSessao?.status === 'finalizado' ? 'resultados' : 'grupos')
    setPresencas([])
    setPresencaLocal({})
  }

  function irParaChamada() {
    setShowStartAlert(false)
    setAba('presenca')
    router.replace(`/professor/seminarios/${id}?data=${encodeURIComponent(sessao.data)}&tab=presenca`, { scroll: false })
    window.requestAnimationFrame(() => chamadaRef.current?.scrollIntoView({ behavior: 'smooth', block: 'start' }))
  }

  const gruposFiltrados = alunos.filter((aluno) => `${aluno.nome} ${aluno.registro}`.toLocaleLowerCase('pt-BR').includes(buscaAluno.toLocaleLowerCase('pt-BR')))

  return <div className="flex flex-col gap-6">
    <header className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <div className="flex items-start gap-3">
        <Button className="shrink-0" variant="outline" nativeButton={false} render={<Link href="/professor/seminarios" />}>
          <ArrowLeft data-icon="inline-start" />Voltar
        </Button>
        <div className="flex flex-col gap-1">
          <h1 className="font-display text-2xl font-semibold tracking-tight text-foreground text-balance">{seminario.titulo}</h1>
          <p className="max-w-2xl text-sm leading-relaxed text-muted-foreground text-pretty">{seminario.disciplina} · Professor responsável</p>
        </div>
      </div>
      <div className="flex shrink-0 flex-wrap items-center gap-2">
        <Button variant="destructive" onClick={() => setShowDeleteAlert(true)}><Trash2 data-icon="inline-start" />Excluir Seminário</Button>
        <Button variant="outline" nativeButton={false} render={<Link href={`/professor/seminarios/novo?edit=${seminario.id}`} />}><Edit data-icon="inline-start" />Editar Seminário</Button>
        {!resultadosDisponiveis && <Button onClick={iniciarApresentacao}><Play data-icon="inline-start" />Apresentar</Button>}
      </div>
    </header>

    <Card><CardHeader><CardTitle className="text-base">Sessão da apresentação</CardTitle></CardHeader><CardContent className="grid gap-4 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-end">
      <div className="flex flex-col gap-2"><span className="text-sm font-medium">Data agendada</span><Select value={`${formatarDataCurta(sessao.data)} — ${sessao.status === 'finalizado' ? 'Concluída' : 'Agendada'}`} onValueChange={(value) => { const selected = sessoes.find((item) => `${formatarDataCurta(item.data)} — ${item.status === 'finalizado' ? 'Concluída' : 'Agendada'}` === value); if (selected) selecionarData(selected.data) }}><SelectTrigger aria-label="Selecionar data da sessão" className="w-full text-foreground"><SelectValue /></SelectTrigger><SelectContent>{sessoes.map((item) => { const label = `${formatarDataCurta(item.data)} — ${item.status === 'finalizado' ? 'Concluída' : 'Agendada'}`; return <SelectItem key={item.data} value={label}>{label}</SelectItem> })}</SelectContent></Select></div>
      <Badge className={resultadosDisponiveis ? 'border-emerald-300 bg-emerald-100 text-emerald-900 dark:border-emerald-800 dark:bg-emerald-950/70 dark:text-emerald-200' : 'bg-muted text-foreground'} variant={resultadosDisponiveis ? 'outline' : 'secondary'}>{formatarDataCurta(sessao.data)} — {resultadosDisponiveis ? 'Concluída' : 'Agendada'}</Badge>
    </CardContent></Card>

    <div className="grid gap-4 md:grid-cols-3">
      <Card><CardHeader><CardTitle className="text-base">Status do seminário</CardTitle></CardHeader><CardContent><StatusBadge status={seminario.status} /></CardContent></Card>
      <Card><CardHeader><CardTitle className="text-base">Agendamento</CardTitle></CardHeader><CardContent className="flex items-center gap-2 text-sm text-muted-foreground"><CalendarDays className="size-4" />{formatarDataCurta(sessao.data)} às {sessao.horario ?? seminario.horario}</CardContent></Card>
      <Card><CardHeader><CardTitle className="text-base">Presença desta data</CardTitle></CardHeader><CardContent className="flex items-center gap-2 text-sm text-muted-foreground"><Users className="size-4" />{presentes}/{alunos.length} confirmados</CardContent></Card>
    </div>

    <Tabs value={aba} onValueChange={setAba} className="flex flex-col gap-4">
      <TabsList className={`grid w-full ${resultadosDisponiveis ? 'grid-cols-4' : 'grid-cols-3'}`}><TabsTrigger value="grupos" className="text-foreground">Grupos</TabsTrigger><TabsTrigger value="presenca" className="text-foreground">Presença</TabsTrigger><TabsTrigger value="criterios" className="text-foreground">Critérios</TabsTrigger>{resultadosDisponiveis && <TabsTrigger value="resultados" className="text-foreground">Resultados</TabsTrigger>}</TabsList>
      <TabsContent value="grupos"><Card><CardHeader><div className="flex flex-wrap items-center justify-between gap-3"><div className="flex flex-col gap-2"><CardTitle>Grupos de apresentação — {formatarDataCurta(sessao.data)}</CardTitle>{resultadosDisponiveis && <Badge variant="secondary" className="w-fit">Edição bloqueada: avaliações desta data encerradas</Badge>}</div>{!resultadosDisponiveis && <div className="flex flex-wrap gap-2"><Button type="button" variant="outline" size="sm" onClick={sortearOrdemDaSessao} disabled={sessao.grupos.length < 2}><Shuffle data-icon="inline-start" />Sortear Ordem de Apresentação</Button><Button type="button" size="sm" onClick={abrirCriacaoGrupo}>Adicionar grupo</Button></div>}</div></CardHeader><CardContent className="grid gap-3 md:grid-cols-2">{sessao.grupos.map((grupo) => <div key={grupo.id} className="rounded-lg border p-4"><div className="flex flex-wrap items-center justify-between gap-3"><div className="flex items-center gap-2"><span className="font-medium">{grupo.nome}</span><Badge variant="secondary">{grupo.ordem || '—'}º a apresentar</Badge></div>{!resultadosDisponiveis && <div className="flex gap-1"><Button type="button" variant="ghost" size="sm" onClick={() => abrirEdicaoGrupo(grupo.id)}><Pencil data-icon="inline-start" />Editar</Button><Button type="button" variant="ghost" size="icon" aria-label={`Excluir ${grupo.nome}`} onClick={() => excluirGrupo(grupo.id)}><Trash2 /></Button></div>}</div><p className="mt-2 text-sm text-muted-foreground">{grupo.tema}</p><div className="mt-3 flex flex-col gap-1"><span className="text-xs font-medium text-muted-foreground">Integrantes ({grupo.integrantesIds.length})</span><p className="text-sm leading-relaxed">{grupo.integrantesIds.map((alunoId) => alunos.find((aluno) => aluno.id === alunoId)?.nome ?? 'Aluno não encontrado').join(', ') || 'Nenhum integrante vinculado.'}</p></div></div>)}{sessao.grupos.length === 0 && <p className="text-sm text-muted-foreground">Nenhum grupo definido para esta data.</p>}</CardContent></Card></TabsContent>
      <TabsContent value="presenca"><div ref={chamadaRef}><Card><CardHeader><CardTitle>Chamada — {formatarDataCurta(sessao.data)}</CardTitle><p className="text-sm text-muted-foreground">A presença é registrada separadamente para cada data.</p></CardHeader><CardContent className="flex flex-col gap-3">{alunos.map((aluno) => <label key={aluno.id} className="flex items-center gap-3 rounded-lg border p-3"><Checkbox checked={presencaLocal[aluno.id] === true} onCheckedChange={(checked) => setPresencaLocal((atual) => ({ ...atual, [aluno.id]: checked === true }))} /><span className="text-sm font-medium">{aluno.nome}</span><span className="ml-auto text-xs text-muted-foreground">{aluno.registro}</span></label>)}{alunos.length === 0 && <p className="text-sm text-muted-foreground">Nenhum aluno vinculado à disciplina.</p>}<div className="flex items-center gap-2 text-sm font-medium text-emerald-700"><CheckCircle2 className="size-4" />{Object.values(presencaLocal).filter(Boolean).length} presença(s) marcada(s)</div><p className="text-sm text-muted-foreground">Marque presença ou falta para cada aluno e salve a chamada antes de iniciar.</p><Button disabled={Object.values(presencaLocal).filter(Boolean).length < 1} onClick={salvarChamada}>{chamadaCompleta ? 'Chamada salva' : 'Salvar chamada'}</Button></CardContent></Card></div></TabsContent>
      <TabsContent value="criterios"><Card><CardHeader><CardTitle>Critérios de avaliação</CardTitle></CardHeader><CardContent className="grid gap-3">{seminario.criterios.map((criterio) => <div key={criterio.id} className="rounded-lg border p-4"><div className="flex flex-wrap items-center justify-between gap-2"><span className="font-medium">{criterio.nome}</span><div className="flex items-center gap-2"><Badge variant="outline">{criterio.tipo === 'individual' ? 'Individualmente' : 'Em Grupo'}</Badge><Badge>{criterio.peso}%</Badge></div></div></div>)}</CardContent></Card></TabsContent>
      {resultadosDisponiveis && <TabsContent value="resultados"><div className="mb-4"><h2 className="text-lg font-semibold">Resultados — {formatarDataCurta(sessao.data)}</h2><p className="text-sm text-muted-foreground">Ranking, médias ponderadas e feedbacks desta sessão.</p></div>{resultado ? <ResultadoView resultado={resultado} /> : <p className="text-sm text-muted-foreground">Carregando resultados…</p>}</TabsContent>}
    </Tabs>

    <Dialog open={dialogGrupoAberto} onOpenChange={setDialogGrupoAberto}><DialogContent className="max-h-[calc(100vh-2rem)] overflow-y-auto sm:max-w-2xl"><DialogHeader><DialogTitle>{grupoEmEdicao ? 'Editar grupo' : 'Criar grupo'}</DialogTitle><DialogDescription>Defina o tema e atribua os participantes da disciplina.</DialogDescription></DialogHeader><div className="flex flex-col gap-5"><div className="flex flex-col gap-2"><Label htmlFor="tema-grupo">Tema</Label><Input id="tema-grupo" value={temaGrupo} onChange={(event) => setTemaGrupo(event.target.value)} placeholder="Informe o tema do grupo" /></div><fieldset className="flex min-w-0 flex-col gap-2"><legend className="mb-1 text-sm font-medium">Participantes</legend><div className="relative"><Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" /><Input value={buscaAluno} onChange={(event) => setBuscaAluno(event.target.value)} placeholder="Pesquise pelo nome ou matrícula" aria-label="Pesquise pelo nome ou matrícula" className="pl-9" /></div><div className="max-h-[48vh] overflow-auto rounded-lg border p-3">{gruposFiltrados.map((aluno) => <label key={aluno.id} className="flex cursor-pointer items-center gap-3 rounded-md py-2 text-sm hover:bg-muted/60"><Checkbox checked={alunosSelecionados.includes(aluno.id)} onCheckedChange={(checked) => setAlunosSelecionados((selecionados) => checked === true ? [...new Set([...selecionados, aluno.id])] : selecionados.filter((alunoId) => alunoId !== aluno.id))} /><span className="min-w-0 flex-1 truncate">{aluno.nome}</span><span className="text-xs text-muted-foreground">{aluno.registro}</span></label>)}{gruposFiltrados.length === 0 && <p className="py-4 text-center text-sm text-muted-foreground">Nenhum aluno encontrado.</p>}</div></fieldset></div><DialogFooter><DialogCloseButton onClick={() => setDialogGrupoAberto(false)} /> <Button onClick={criarGrupo}>{grupoEmEdicao ? 'Salvar alterações' : 'Criar grupo'}</Button></DialogFooter></DialogContent></Dialog>

    <Dialog open={showStartAlert} onOpenChange={setShowStartAlert}><DialogContent><DialogHeader><DialogTitle>Não é possível iniciar a apresentação</DialogTitle><DialogDescription>{!sessao.grupos.length && !chamadaCompleta ? 'Forme ao menos um grupo e registre e salve a chamada desta data antes de iniciar.' : !sessao.grupos.length ? 'Forme ao menos um grupo para esta data antes de iniciar.' : 'Registre e salve a chamada desta data antes de iniciar a apresentação.'}</DialogDescription></DialogHeader><DialogFooter><Button variant="outline" onClick={() => setShowStartAlert(false)}>Fechar</Button>{sessao.grupos.length > 0 && !chamadaCompleta && <Button onClick={irParaChamada}>Registrar chamada</Button>}</DialogFooter></DialogContent></Dialog>

    <AlertDialog open={showDeleteAlert} onOpenChange={setShowDeleteAlert}>
      <AlertDialogContent>
        <AlertDialogHeader>
          <AlertDialogTitle>Excluir {seminario.titulo}?</AlertDialogTitle>
          <AlertDialogDescription>Esta ação removerá o seminário e suas avaliações vinculadas. Não será possível desfazê-la.</AlertDialogDescription>
        </AlertDialogHeader>
        <AlertDialogFooter>
          <AlertDialogCancel>Cancelar</AlertDialogCancel>
          <AlertDialogAction onClick={excluirSeminario} variant="destructive">
            <Trash2 data-icon="inline-start" />Excluir Seminário
          </AlertDialogAction>
        </AlertDialogFooter>
      </AlertDialogContent>
    </AlertDialog>
  </div>
}

function DialogCloseButton({ onClick }: { onClick: () => void }) {
  return <Button variant="outline" onClick={onClick}>Cancelar</Button>
}
