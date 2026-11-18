'use client'

import * as React from 'react'
import Link from 'next/link'
import { useParams, useSearchParams } from 'next/navigation'
import { ArrowLeft, CheckCircle2, Radio, Save } from 'lucide-react'
import { toast } from 'sonner'
import { alunoService } from '@/lib/services'
import type { FormularioAvaliacao } from '@/lib/services/aluno'
import type { ResultadoDTO } from '@/lib/types-admin'
import type { Seminario } from '@/lib/types'
import { obterSessoesSeminario } from '@/lib/types'
import { PageHeader } from '@/components/page-header'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import { ResultadoView } from '@/components/seminario/resultado-view'

function mensagemErro(erro: unknown): string {
  return erro instanceof Error ? erro.message : 'Erro inesperado.'
}

export default function AlunoAoVivoPage() {
  const { id } = useParams<{ id: string }>()
  const searchParams = useSearchParams()
  const [seminario, setSeminario] = React.useState<Seminario | null>(null)
  const [formulario, setFormulario] = React.useState<FormularioAvaliacao | null>(null)
  const [resultado, setResultado] = React.useState<ResultadoDTO | null>(null)
  const [carregando, setCarregando] = React.useState(true)
  const [grupoId, setGrupoId] = React.useState<number | null>(null)
  const [notas, setNotas] = React.useState<Record<string, string>>({})
  const [feedback, setFeedback] = React.useState('')
  const [erro, setErro] = React.useState('')
  const [salva, setSalva] = React.useState(false)

  const alunoId = React.useMemo(() => {
    const configurado = process.env.NEXT_PUBLIC_ALUNO_ID
    return configurado ? Number(configurado) : 1
  }, [])

  const carregar = React.useCallback(async () => {
    setCarregando(true)
    try {
      const detalhes = await alunoService.detalhesSeminario(alunoId, id)
      setSeminario(detalhes)
      const sessoes = obterSessoesSeminario(detalhes)
      const pretendida = searchParams.get('data')
      const sessao = sessoes.find((item) => item.data === pretendida) ?? sessoes[0]
      if (!sessao?.id) return

      if (sessao.status === 'finalizado') {
        setResultado(await alunoService.compilarResultados(alunoId, sessao.id))
        return
      }

      const form = await alunoService.formulario(alunoId, sessao.id)
      setFormulario(form)
      if (!grupoId && form.grupos[0]) setGrupoId(form.grupos[0].id)
    } catch (e) {
      toast.error(mensagemErro(e))
    } finally {
      setCarregando(false)
    }
  }, [alunoId, id, searchParams, grupoId])

  React.useEffect(() => { void carregar() }, [carregar])

  const grupo = formulario?.grupos.find((item) => item.id === grupoId) ?? formulario?.grupos[0]
  const avaliacaoAnterior = formulario?.minhasAvaliacoes.find((item) => item.grupoId === grupo?.id)

  React.useEffect(() => {
    if (!grupo) return
    const mapa: Record<string, string> = {}
    for (const nota of avaliacaoAnterior?.notas ?? []) {
      const chave = nota.alunoId === null
        ? `cr-${nota.criterioId}`
        : `cr-${nota.criterioId}:u-al-${nota.alunoId}`
      mapa[chave] = String(nota.nota)
    }
    setNotas(mapa)
    setFeedback(avaliacaoAnterior?.feedback ?? '')
    setSalva(Boolean(avaliacaoAnterior))
    setErro('')
  }, [grupo?.id])

  const finalizada = Boolean(seminario && obterSessoesSeminario(seminario).some((s) => s.status === 'finalizado'))

  if (carregando && !seminario) return <p className="text-sm text-muted-foreground">Carregando avaliação…</p>
  if (!seminario) return <Card><CardContent className="p-8 text-center">Seminário não encontrado.</CardContent></Card>

  if (resultado) {
    return <div className="flex flex-col gap-6">
      <PageHeader title="Resultado da apresentação" description={seminario.titulo}>
        <Button variant="outline" nativeButton={false} render={<Link href={`/aluno/seminarios/${seminario.id}`} />}><ArrowLeft data-icon="inline-start" />Voltar</Button>
      </PageHeader>
      <ResultadoView resultado={resultado} />
    </div>
  }

  function atualizarNota(chave: string, valor: string) {
    if (valor !== '' && (!Number.isFinite(Number(valor)) || Number(valor) < 0 || Number(valor) > 10)) return
    setNotas((atuais) => ({ ...atuais, [chave]: valor }))
    setSalva(false)
    setErro('')
  }

  async function enviar(event: React.FormEvent) {
    event.preventDefault()
    if (!formulario || !grupo) return
    const obrigatorias = formulario.criterios.flatMap((criterio) => criterio.tipo === 'grupo'
      ? [`cr-${criterio.id}`]
      : grupo.integrantesIds.map((alunoId) => `cr-${criterio.id}:u-al-${alunoId}`))
    if (obrigatorias.some((chave) => notas[chave] === undefined || notas[chave] === '')) {
      setErro('Preencha uma nota para cada critério antes de enviar.')
      return
    }
    const payload = Object.entries(notas)
      .filter(([, valor]) => valor !== '')
      .map(([chave, valor]) => {
        const [prefixo, criterio, aluno] = chave.split(':')
        return {
          criterioId: Number(prefixo.replace('cr-', '')),
          alunoId: aluno ? Number(aluno.replace('u-al-', '')) : null,
          nota: Number(valor),
        }
      })
    try {
      await alunoService.salvarAvaliacao(alunoId, formulario.dataId, grupo.id, payload, feedback)
      toast.success('Sua avaliação foi salva.')
      setSalva(true)
      setErro('')
      await carregar()
    } catch (e) {
      toast.error(mensagemErro(e))
    }
  }

  return <div className="flex flex-col gap-6">
    <PageHeader title="Avaliação do seminário" description={seminario.titulo}>
      <Button variant="outline" nativeButton={false} render={<Link href={`/aluno/seminarios/${seminario.id}`} />}><ArrowLeft data-icon="inline-start" />Voltar</Button>
    </PageHeader>
    <Card className="border-emerald-500/40 bg-emerald-500/5">
      <CardContent className="flex flex-wrap items-center justify-between gap-3 p-5">
        <div className="flex items-center gap-3">
          <Radio className="size-5 animate-pulse text-emerald-600" />
          <div><p className="font-semibold">Avaliação por pares</p><p className="text-sm text-muted-foreground">Selecione um grupo e registre suas notas.</p></div>
        </div>
        <Badge variant="secondary">Peso discente {Math.round((1 - seminario.pesoProfessor) * 100)}%</Badge>
      </CardContent>
    </Card>
    {finalizada ? (
      <Card><CardContent className="p-5"><p className="font-semibold">Apresentação encerrada</p><p className="text-sm text-muted-foreground">O resultado será exibido em breve.</p></CardContent></Card>
    ) : !formulario ? (
      <p className="text-sm text-muted-foreground">Carregando formulário…</p>
    ) : (
      <div className="grid gap-6 lg:grid-cols-[280px_minmax(0,1fr)]">
        <Card className="h-fit">
          <CardHeader><CardTitle className="text-base">Grupos para avaliar</CardTitle><CardDescription>Seu grupo não aparece nesta lista.</CardDescription></CardHeader>
          <CardContent className="flex flex-col gap-2">
            {formulario.grupos.map((item) => {
              const concluida = formulario.minhasAvaliacoes.some((avaliacao) => avaliacao.grupoId === item.id)
              return <button key={item.id} type="button" onClick={() => setGrupoId(item.id)} className={`flex items-start justify-between gap-2 rounded-lg border p-3 text-left transition-colors hover:bg-muted ${grupo?.id === item.id ? 'border-primary bg-primary/5' : ''}`}><span><span className="block font-medium">{item.nome}</span><span className="block text-xs text-muted-foreground">{item.tema}</span></span>{concluida && <CheckCircle2 className="size-4 shrink-0 text-primary" aria-label="Avaliação salva" />}</button>
            })}
            {formulario.grupos.length === 0 && <p className="text-sm text-muted-foreground">Não há grupos disponíveis para avaliação.</p>}
          </CardContent>
        </Card>
        <Card>
          <CardHeader><CardTitle className="flex items-center gap-2"><Save />{grupo ? `Avaliação — ${grupo.nome}` : 'Selecione um grupo'}</CardTitle></CardHeader>
          <CardContent>
            {grupo ? <form className="flex flex-col gap-6" onSubmit={enviar}>
              {formulario.criterios.filter((criterio) => criterio.tipo === 'grupo').map((criterio) => <div key={criterio.id} className="flex flex-col gap-2"><Label htmlFor={`nota-cr-${criterio.id}`}>{criterio.nome} ({criterio.peso}%)</Label><Input id={`nota-cr-${criterio.id}`} type="number" min={0} max={10} step="0.5" value={notas[`cr-${criterio.id}`] ?? ''} onChange={(event) => atualizarNota(`cr-${criterio.id}`, event.target.value)} /></div>)}
              {formulario.criterios.filter((criterio) => criterio.tipo === 'individual').map((criterio) => <fieldset key={criterio.id} className="flex flex-col gap-3 rounded-lg border p-4"><legend className="px-1 text-sm font-medium">{criterio.nome} ({criterio.peso}%) — por integrante</legend>{grupo.integrantesIds.map((alunoId) => {
                const chave = `cr-${criterio.id}:u-al-${alunoId}`
                return <div key={chave} className="flex flex-col gap-2"><Label htmlFor={`nota-${chave}`}>Aluno {alunoId}</Label><Input id={`nota-${chave}`} type="number" min={0} max={10} step="0.5" value={notas[chave] ?? ''} onChange={(event) => atualizarNota(chave, event.target.value)} /></div>
              })}</fieldset>)}
              <div className="flex flex-col gap-2"><Label htmlFor="feedback">Feedback para o grupo</Label><Textarea id="feedback" value={feedback} onChange={(event) => { setFeedback(event.target.value); setSalva(false) }} rows={4} /></div>
              {erro && <p className="text-sm font-medium text-destructive" role="alert">{erro}</p>}
              <div><Button type="submit">{salva ? 'Atualizar avaliação' : 'Salvar avaliação'}</Button></div>
            </form> : <p className="text-sm text-muted-foreground">Selecione um grupo para avaliar.</p>}
          </CardContent>
        </Card>
      </div>
    )}
  </div>
}
