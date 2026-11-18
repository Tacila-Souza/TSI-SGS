'use client'

import * as React from 'react'
import Link from 'next/link'
import { Plus } from 'lucide-react'
import { useApp } from '@/lib/app-context'
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
import { Presentation } from 'lucide-react'

type Filtro = 'todos' | SeminarioStatus

const filtros: { value: Filtro; label: string }[] = [
  { value: 'todos', label: 'Todos' },
  { value: 'rascunho', label: statusSeminarioLabel.rascunho },
  { value: 'agendado', label: statusSeminarioLabel.agendado },
  { value: 'em_andamento', label: statusSeminarioLabel.em_andamento },
  { value: 'finalizado', label: statusSeminarioLabel.finalizado },
]

export default function SeminariosPage() {
  const { currentUser, seminarios } = useApp()
  const [filtro, setFiltro] = React.useState<Filtro>('todos')
  const meus = seminarios.filter((s) => s.professorId === currentUser?.id)
  const lista = filtro === 'todos' ? meus : meus.filter((s) => s.status === filtro)

  return (
    <>
      <PageHeader
        title="Seminários"
        description="Todos os seminários que você criou, organizados por status."
      >
        <Button render={<Link href="/professor/seminarios/novo" />}>
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

      {lista.length === 0 ? (
        <Empty className="py-12">
          <EmptyMedia variant="icon">
            <Presentation />
          </EmptyMedia>
          <EmptyTitle>{meus.length === 0 ? 'Nenhum seminário cadastrado' : 'Nenhum seminário neste status'}</EmptyTitle>
          <EmptyDescription>
            {meus.length === 0 ? 'Nenhum seminário cadastrado no banco de dados.' : 'Não há seminários com esse status no momento.'}
          </EmptyDescription>
          <EmptyContent>
            <Button render={<Link href="/professor/seminarios/novo" />}>
              <Plus data-icon="inline-start" />
              Criar seminário
            </Button>
          </EmptyContent>
        </Empty>
      ) : (
        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
          {lista.map((s) => (
            <SeminarioCard key={s.id} seminario={s} basePath="/professor/seminarios" />
          ))}
        </div>
      )}

    </>
  )
}
