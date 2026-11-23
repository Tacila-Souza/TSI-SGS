'use client'

import * as React from 'react'
import Link from 'next/link'
import { ArrowRight, BookOpen, CalendarDays, Clock, Radio, Users } from 'lucide-react'
import { toast } from 'sonner'
import { useApp } from '@/lib/app-context'
import { alunoService } from '@/lib/services'
import { obterSessoesSeminario, type Seminario } from '@/lib/types'
import { formatarDataCurta } from '@/lib/format'
import { PageHeader } from '@/components/page-header'
import { StatusBadge } from '@/components/status-badge'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardFooter, CardHeader, CardTitle } from '@/components/ui/card'
import { Empty, EmptyDescription, EmptyMedia, EmptyTitle } from '@/components/ui/empty'

function mensagemErro(erro: unknown): string {
  return erro instanceof Error ? erro.message : 'Erro inesperado.'
}

export default function AlunoDashboardPage() {
  const { currentUser, live } = useApp()
  const [seminarios, setSeminarios] = React.useState<Seminario[]>([])
  const [carregando, setCarregando] = React.useState(true)

  const alunoId = React.useMemo(() => {
    const configurado = process.env.NEXT_PUBLIC_ALUNO_ID
    return configurado ? Number(configurado) : 1
  }, [])

  React.useEffect(() => {
    let ativo = true
    setCarregando(true)
    alunoService.listarSeminarios(alunoId)
      .then((lista) => { if (ativo) setSeminarios(lista) })
      .catch((erro) => { if (ativo) toast.error(mensagemErro(erro)) })
      .finally(() => { if (ativo) setCarregando(false) })
    return () => { ativo = false }
  }, [alunoId])

  const aoVivo = seminarios.find((seminario) => seminario.id === live.seminarioId)
    ?? seminarios.find((seminario) => seminario.status === 'em_andamento')
  const grupoAoVivo = aoVivo?.grupos.find((grupo) => grupo.id === live.grupoId)
    ?? aoVivo?.grupos.find((grupo) => grupo.ordem === 1)
  const sessaoAoVivo = aoVivo
    ? obterSessoesSeminario(aoVivo).find((sessao) => sessao.status !== 'finalizado')
    : undefined
  const hrefAoVivo = aoVivo
    ? `/aluno/seminarios/${aoVivo.id}/ao-vivo${sessaoAoVivo?.data ? `?data=${encodeURIComponent(sessaoAoVivo.data)}` : ''}`
    : '#'

  return (
    <div className="flex flex-col gap-8">
      <PageHeader
        title={`Olá, ${currentUser?.nome.split(' ')[0] ?? 'aluno'}`}
        description="Acompanhe os seminários das suas disciplinas e participe das avaliações em tempo real."
      />

      {aoVivo && grupoAoVivo ? (
        <Card className="border-emerald-500/40 bg-emerald-500/10 shadow-sm">
          <CardContent className="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
            <div className="flex items-start gap-3">
              <Radio className="mt-0.5 size-5 shrink-0 text-emerald-600" />
              <div className="flex flex-col gap-1">
                <p className="flex flex-wrap items-center gap-2 font-semibold text-emerald-800 dark:text-emerald-300">
                  Apresentação acontecendo agora
                  <Badge className="bg-emerald-500 text-white hover:bg-emerald-500">Ao vivo</Badge>
                </p>
                <p className="text-sm text-foreground">
                  <strong>{grupoAoVivo.nome}</strong> apresenta “{grupoAoVivo.tema}” em {aoVivo.disciplina}.
                </p>
              </div>
            </div>
            <Button nativeButton={false} render={<Link href={hrefAoVivo} />} size="sm">
              Acompanhar apresentação <ArrowRight data-icon="inline-end" />
            </Button>
          </CardContent>
        </Card>
      ) : null}

      <section className="flex flex-col gap-4" aria-labelledby="seminarios-title">
        <div className="flex items-end justify-between gap-4">
          <div>
            <p className="text-sm font-medium text-primary">Minhas disciplinas</p>
            <h2 id="seminarios-title" className="text-xl font-semibold tracking-tight">Seminários da disciplina</h2>
          </div>
          <div className="flex flex-wrap gap-2">
            <Button variant="outline" size="sm" nativeButton={false} render={<Link href="/aluno/resultados" />}>
              Histórico e resultados
            </Button>
            <Button variant="outline" size="sm" nativeButton={false} render={<Link href="/aluno/seminarios" />}>
              Ver todos <ArrowRight data-icon="inline-end" />
            </Button>
          </div>
        </div>

        {carregando ? (
          <p className="text-sm text-muted-foreground">Carregando seminários…</p>
        ) : seminarios.length === 0 ? (
          <Empty className="rounded-xl border py-10"><EmptyMedia variant="icon"><BookOpen /></EmptyMedia><EmptyTitle>Nenhum seminário disponível</EmptyTitle><EmptyDescription>Não há seminários cadastrados para as suas disciplinas.</EmptyDescription></Empty>
        ) : (
          <div className="grid gap-4 lg:grid-cols-2">
            {seminarios.map((seminario) => {
              const estaAoVivo = aoVivo?.id === seminario.id
              const totalIntegrantes = seminario.grupos.reduce((total, grupo) => total + grupo.integrantesIds.length, 0)
              return (
                <Card key={seminario.id} className={`group relative flex h-full flex-col ${estaAoVivo ? 'border-emerald-500/50 shadow-md' : ''}`}>
                  <CardHeader className="gap-3">
                    <div className="flex items-start justify-between gap-3">
                      <CardTitle className="text-pretty">{seminario.titulo}</CardTitle>
                      {estaAoVivo ? <Badge className="shrink-0 bg-emerald-500 text-white hover:bg-emerald-500"><Radio data-icon="inline-start" /> Ao vivo</Badge> : <StatusBadge status={seminario.status} />}
                    </div>
                    <div className="flex flex-wrap gap-2 text-sm">
                      <Badge variant="secondary">Professor responsável</Badge>
                      <Badge variant="outline">{seminario.disciplina}</Badge>
                    </div>
                  </CardHeader>
                  <CardContent className="flex flex-1 flex-col gap-3 text-sm text-muted-foreground">
                    <p className="line-clamp-2 text-pretty">{seminario.descricao}</p>
                    {estaAoVivo && grupoAoVivo ? <p className="rounded-md bg-emerald-500/10 px-3 py-2 text-emerald-800 dark:text-emerald-300"><strong>{grupoAoVivo.nome}:</strong> {grupoAoVivo.tema}</p> : null}
                  </CardContent>
                  <CardFooter className="mt-auto flex flex-wrap items-center justify-between gap-3 border-t text-xs text-muted-foreground">
                    <div className="grid w-full grid-cols-2 gap-3 sm:grid-cols-3">
                      <span className="flex min-h-9 items-center gap-1.5"><CalendarDays className="size-3.5 shrink-0" />{formatarDataCurta(seminario.data)}</span>
                      <span className="flex min-h-9 items-center gap-1.5"><Clock className="size-3.5 shrink-0" />{seminario.horario}</span>
                      <span className="flex min-h-9 items-center gap-1.5"><Users className="size-3.5 shrink-0" />{totalIntegrantes} alunos</span>
                    </div>
                    {estaAoVivo ? <Button size="sm" nativeButton={false} render={<Link href={estaAoVivo ? hrefAoVivo : `/aluno/seminarios/${seminario.id}/ao-vivo`} />}>Avaliar agora <ArrowRight data-icon="inline-end" /></Button> : null}
                  </CardFooter>
                </Card>
              )
            })}
          </div>
        )}
      </section>
    </div>
  )
}
