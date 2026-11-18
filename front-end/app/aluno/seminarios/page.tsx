'use client'

import * as React from 'react'
import { BookOpen } from 'lucide-react'
import { toast } from 'sonner'
import { alunoService } from '@/lib/services'
import type { Seminario } from '@/lib/types'
import { PageHeader } from '@/components/page-header'
import { SeminarioCard } from '@/components/seminario/seminario-card'
import { Empty, EmptyDescription, EmptyMedia, EmptyTitle } from '@/components/ui/empty'

export default function AlunoSeminariosPage() {
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
      .catch((erro) => { if (ativo) toast.error(erro instanceof Error ? erro.message : 'Erro ao carregar.') })
      .finally(() => { if (ativo) setCarregando(false) })
    return () => { ativo = false }
  }, [alunoId])

  return <div className="flex flex-col gap-6">
    <PageHeader title="Seminários" description="Acompanhe os seminários das suas disciplinas." />
    {carregando ? <p className="text-sm text-muted-foreground">Carregando…</p> : seminarios.length ? (
      <div className="grid gap-4 md:grid-cols-2">{seminarios.map((seminario) => <SeminarioCard key={seminario.id} seminario={seminario} basePath="/aluno/seminarios" />)}</div>
    ) : (
      <Empty className="rounded-xl border py-10"><EmptyMedia variant="icon"><BookOpen /></EmptyMedia><EmptyTitle>Nenhum seminário disponível</EmptyTitle><EmptyDescription>Não há seminários cadastrados para as suas disciplinas.</EmptyDescription></Empty>
    )}
  </div>
}
