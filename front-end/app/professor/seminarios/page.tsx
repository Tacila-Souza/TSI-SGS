
'use client'

import * as React from 'react'
import Link from 'next/link'
import { Plus, Presentation } from 'lucide-react'
import { useApp } from '@/lib/app-context'
import { toApiId } from '@/lib/services'
import type { SeminarioStatus } from '@/lib/types'
import { statusSeminarioLabel } from '@/lib/format'
import { PageHeader } from '@/components/page-header'
import { SeminarioCard } from '@/components/seminario/seminario-card'
import { Button } from '@/components/ui/button'
import {
  ToggleGroup,
  ToggleGroupItem,
} from '@/components/ui/toggle-group'
import {
  Empty,
  EmptyContent,
  EmptyDescription,
  EmptyMedia,
  EmptyTitle,
} from '@/components/ui/empty'

type Filtro = 'todos' | SeminarioStatus

const filtros: { value: Filtro; label: string }[] = [
  { value: 'todos', label: 'Todos' },
  { value: 'rascunho', label: statusSeminarioLabel.rascunho },
  { value: 'agendado', label: statusSeminarioLabel.agendado },
  { value: 'em_andamento', label: statusSeminarioLabel.em_andamento },
  { value: 'finalizado', label: statusSeminarioLabel.finalizado },
]

export default function SeminariosPage() {
  const {
    professorId,
    carregandoPerfil,
    seminarios,
    carregandoSeminarios,
  } = useApp()

  const [filtro, setFiltro] = React.useState<Filtro>('todos')

  // O AppProvider já carrega os seminários da API; aqui só filtramos pelo professor logado.
  const doProfessor = React.useMemo(
    () => professorId === null
      ? []
      : seminarios.filter(
          (seminario) => toApiId(seminario.professorId) === professorId,
        ),
    [seminarios, professorId],
  )

  const lista = filtro === 'todos'
    ? doProfessor
    : doProfessor.filter((seminario) => seminario.status === filtro)

  return (
    <>
      <PageHeader
        title="Seminários"
        description="Todos os seminários que você criou, organizados por status."
      >
        <Button nativeButton={false} render={<Link href="/professor/seminarios/novo" />}>
          <Plus data-icon="inline-start" />
          Novo seminário
        </Button>
      </PageHeader>

      <ToggleGroup
        value={[filtro]}
        onValueChange={(v) => {
          const next = (v as Filtro[])[0]
          if (next) setFiltro(next)
        }}
        className="flex-wrap"
      >
        {filtros.map((f) => (
          <ToggleGroupItem key={f.value} value={f.value}>
            {f.label}
          </ToggleGroupItem>
        ))}
      </ToggleGroup>

      {carregandoPerfil || carregandoSeminarios ? (
        <p className="py-8 text-sm text-muted-foreground">
          Carregando seminários...
        </p>
      ) : lista.length === 0 ? (
        <Empty className="py-12">
          <EmptyMedia variant="icon">
            <Presentation />
          </EmptyMedia>

          <EmptyTitle>
            {doProfessor.length === 0
              ? 'Nenhum seminário cadastrado'
              : 'Nenhum seminário neste status'}
          </EmptyTitle>

          <EmptyDescription>
            {doProfessor.length === 0
              ? 'Nenhum seminário cadastrado no banco de dados.'
              : 'Não há seminários com esse status no momento.'}
          </EmptyDescription>

          <EmptyContent>
            <Button
              render={<Link href="/professor/seminarios/novo" />}
            >
              <Plus data-icon="inline-start" />
              Criar seminário
            </Button>
          </EmptyContent>
        </Empty>
      ) : (
        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
          {lista.map((seminario) => (
            <SeminarioCard
              key={seminario.id}
              seminario={seminario}
              basePath="/professor/seminarios"
            />
          ))}
        </div>
      )}
    </>
  )
}
