'use client'

import * as React from 'react'
import Link from 'next/link'
import {
  CalendarClock,
  CheckCircle2,
  Plus,
  Presentation,
  Radio,
} from 'lucide-react'
import { useApp } from '@/lib/app-context'
import { professorService } from '@/lib/services'
import type { Seminario } from '@/lib/types'
import { toast } from 'sonner'
import { formatarDataCurta } from '@/lib/format'
import { obterSessoesSeminario } from '@/lib/types'
import { Badge } from '@/components/ui/badge'
import { PageHeader } from '@/components/page-header'
import { StatCard } from '@/components/stat-card'
import { StatusBadge } from '@/components/status-badge'
import { SeminarioCard } from '@/components/seminario/seminario-card'
import { Button } from '@/components/ui/button'
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card'
import {
  Empty,
  EmptyContent,
  EmptyDescription,
  EmptyMedia,
  EmptyTitle,
} from '@/components/ui/empty'

export default function ProfessorDashboard() {
  const { currentUser, live, professorId, carregandoPerfil } = useApp()
  const [meus, setMeus] = React.useState<Seminario[]>([])
  const [carregando, setCarregando] = React.useState(true)

  React.useEffect(() => {
    let ativo = true
    if (carregandoPerfil || professorId === null) {
      setMeus([])
      setCarregando(false)
      return () => { ativo = false }
    }
    setCarregando(true)
    professorService.listarSeminarios(professorId)
      .then((lista) => { if (ativo) setMeus(lista) })
      .catch((erro) => {
        if (ativo) toast.error(erro instanceof Error ? erro.message : 'Erro ao carregar seminários.')
      })
      .finally(() => { if (ativo) setCarregando(false) })
    return () => { ativo = false }
  }, [professorId, carregandoPerfil])

  const proximos = meus
    .filter((seminario) => seminario.status !== 'rascunho' && obterSessoesSeminario(seminario).some((sessao) => sessao.status !== 'finalizado'))
    .sort((a, b) => {
      const proximaData = (seminario: typeof a) => obterSessoesSeminario(seminario).find((sessao) => sessao.status !== 'finalizado')?.data ?? seminario.data
      return proximaData(a).localeCompare(proximaData(b))
    })

  return (
    <>
      <PageHeader
        title={`Olá, ${currentUser?.nome.split(' ')[0]} 👋`}
        description="Gerencie seus seminários, conduza apresentações e avalie os grupos."
      >
        <Button nativeButton={false} render={<Link href="/professor/seminarios/novo" />}>
          <Plus data-icon="inline-start" />
          Novo seminário
        </Button>
      </PageHeader>

      <div className="grid gap-4 sm:grid-cols-3">
        <StatCard
          label="Seminários"
          value={meus.length}
          icon={Presentation}
        />
        <StatCard
          label="Agendados"
          value={meus.filter((s) => s.status === 'agendado').length}
          icon={CalendarClock}
          accent="warning"
        />
        <StatCard
          label="Finalizados"
          value={meus.filter((s) => s.status === 'finalizado').length}
          icon={CheckCircle2}
          accent="success"
        />
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Próximas apresentações</CardTitle>
          <CardDescription>
            Seminários agendados ou em andamento sob sua responsabilidade.
          </CardDescription>
        </CardHeader>
        <CardContent className="flex flex-col gap-3">
          {proximos.length === 0 ? (
            <Empty className="py-8">
              <EmptyMedia variant="icon">
                <CalendarClock />
              </EmptyMedia>
              <EmptyTitle>Nenhum seminário agendado</EmptyTitle>
              <EmptyDescription>
                {carregando ? 'Carregando seminários…' : 'Crie um novo seminário para começar a organizar as apresentações.'}
              </EmptyDescription>
              <EmptyContent>
                <Button nativeButton={false} render={<Link href="/professor/seminarios/novo" />}>
                  <Plus data-icon="inline-start" />
                  Novo seminário
                </Button>
              </EmptyContent>
            </Empty>
          ) : (
            proximos.map((s) => {
              const estaAoVivo = live.seminarioId === s.id && live.status === 'em_andamento'
              return (
              <div
                key={s.id}
                className={`flex flex-col gap-3 rounded-xl border p-4 sm:flex-row sm:items-center sm:justify-between ${estaAoVivo ? 'border-2 border-amber-500 bg-gradient-to-r from-amber-500/10 via-transparent to-transparent shadow-lg shadow-amber-500/10 dark:border-amber-400/70' : 'border-border'}`}
              >
                <div className="flex flex-col gap-1">
                  <div className="flex flex-wrap items-center gap-2">
                    <span className="font-medium text-foreground">{s.titulo}</span>
                    {estaAoVivo && (
                      <Badge className="gap-1.5 border-amber-500/40 bg-amber-500/15 text-amber-800 dark:text-amber-300">
                        <span aria-hidden="true" className="relative inline-flex size-2">
                          <span className="absolute inline-flex size-full animate-ping rounded-full bg-amber-400 opacity-75" />
                          <span className="relative inline-flex size-2 rounded-full bg-amber-500" />
                        </span>
                        AO VIVO · Em Curso
                      </Badge>
                    )}
                    <StatusBadge status={s.status} />
                  </div>
                  <div className="flex flex-wrap items-center gap-1.5 text-sm text-muted-foreground">
                    <span>{s.horario} · {s.grupos.length} grupos</span>
                    {obterSessoesSeminario(s).map((sessao) => <Badge key={sessao.data} variant={sessao.status === 'finalizado' ? 'outline' : 'secondary'} className={sessao.status === 'finalizado' ? 'border-emerald-300 bg-emerald-100 text-emerald-900' : ''}>{formatarDataCurta(sessao.data)} — {sessao.status === 'finalizado' ? 'Concluída' : 'Pendente'}</Badge>)}
                  </div>
                </div>
                <div className="flex items-center gap-2">
                  {estaAoVivo && (
                    <Button
                      nativeButton={false}
                      size="sm"
                      className="bg-amber-600 text-white hover:bg-amber-700 dark:bg-amber-500 dark:text-zinc-950 dark:hover:bg-amber-400"
                      render={
                        <Link href={`/professor/seminarios/${s.id}/apresentar`} />
                      }
                    >
                      <Radio data-icon="inline-start" className="animate-pulse" />
                      Continuar Avaliação
                    </Button>
                  )}
                  <Button
                    variant="outline"
                    size="sm"
                    nativeButton={false}
                    render={<Link href={`/professor/seminarios/${s.id}`} />}
                  >
                    Abrir
                  </Button>
                </div>
              </div>
              )
            })
          )}
        </CardContent>
      </Card>

      <div className="flex flex-col gap-3">
        <h2 className="font-display text-lg font-semibold text-foreground">
          Todos os seminários
        </h2>
        {meus.length === 0 ? (
          <Empty className="rounded-xl border py-10">
            <EmptyMedia variant="icon"><Presentation /></EmptyMedia>
            <EmptyTitle>Nenhum seminário cadastrado</EmptyTitle>
            <EmptyDescription>Nenhum seminário cadastrado no banco de dados.</EmptyDescription>
            <EmptyContent>
              <Button nativeButton={false} render={<Link href="/professor/seminarios/novo" />}>
                <Plus data-icon="inline-start" />
                Novo seminário
              </Button>
            </EmptyContent>
          </Empty>
        ) : (
          <div className="grid auto-rows-fr gap-4 md:grid-cols-2 xl:grid-cols-3">
            {meus.map((s) => (
              <SeminarioCard key={s.id} seminario={s} basePath="/professor/seminarios" />
            ))}
          </div>
        )}
      </div>
    </>
  )
}
