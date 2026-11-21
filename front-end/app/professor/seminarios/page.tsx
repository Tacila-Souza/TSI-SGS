
'use client'

import * as React from 'react'
import Link from 'next/link'
import { Plus, Presentation } from 'lucide-react'
import { useApp } from '@/lib/app-context'
import { professorService, toApiId } from '@/lib/services'
import type { Seminario, SeminarioStatus } from '@/lib/types'
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
  const { currentUser } = useApp()

  const [filtro, setFiltro] = React.useState<Filtro>('todos')
  const [seminarios, setSeminarios] = React.useState<Seminario[]>([])
  const [carregando, setCarregando] = React.useState(true)
  const [erro, setErro] = React.useState('')

  const professorId = React.useMemo(() => {
    const configurado = process.env.NEXT_PUBLIC_PROFESSOR_ID

    if (configurado) {
      const numero = Number(configurado)
      if (Number.isInteger(numero) && numero > 0) {
        return numero
      }
    }

    if (!currentUser?.id) return null

    try {
      return toApiId(currentUser.id)
    } catch {
      return null
    }
  }, [currentUser?.id])

  React.useEffect(() => {
    if (professorId === null) {
      if (currentUser) {
        setErro('Não foi possível identificar o professor.')
        setCarregando(false)
      }
      return
    }

    let ativo = true

    async function carregarSeminarios() {
      setCarregando(true)
      setErro('')

      try {
        const lista = await professorService.listarSeminarios(
          professorId as number
        )

        if (ativo) {
          setSeminarios(lista)
        }
      } catch (error) {
        if (ativo) {
          setErro(
            error instanceof Error
              ? error.message
              : 'Não foi possível carregar os seminários.'
          )
        }
      } finally {
        if (ativo) {
          setCarregando(false)
        }
      }
    }

    void carregarSeminarios()

    return () => {
      ativo = false
    }
  }, [professorId, currentUser])

  // A API já retorna somente os seminários do professor.
  const lista = filtro === 'todos'
    ? seminarios
    : seminarios.filter(
        (seminario) => seminario.status === filtro
      )

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

      {carregando ? (
        <p className="py-8 text-sm text-muted-foreground">
          Carregando seminários...
        </p>
      ) : erro ? (
        <div
          role="alert"
          className="rounded-lg border p-4 text-sm text-destructive"
        >
          {erro}
        </div>
      ) : lista.length === 0 ? (
        <Empty className="py-12">
          <EmptyMedia variant="icon">
            <Presentation />
          </EmptyMedia>

          <EmptyTitle>
            {seminarios.length === 0
              ? 'Nenhum seminário cadastrado'
              : 'Nenhum seminário neste status'}
          </EmptyTitle>

          <EmptyDescription>
            {seminarios.length === 0
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
