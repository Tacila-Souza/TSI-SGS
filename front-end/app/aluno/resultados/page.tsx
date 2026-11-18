'use client'

import * as React from 'react'
import { ClipboardList } from 'lucide-react'
import { toast } from 'sonner'
import { alunoService } from '@/lib/services'
import type { ResultadoDTO } from '@/lib/types-admin'
import type { Seminario } from '@/lib/types'
import { obterSessoesSeminario } from '@/lib/types'
import { PageHeader } from '@/components/page-header'
import { ResultadoView } from '@/components/seminario/resultado-view'
import { Card, CardContent } from '@/components/ui/card'

export default function AlunoResultadosPage() {
  const [resultados, setResultados] = React.useState<ResultadoDTO[]>([])
  const [titulos, setTitulos] = React.useState<Record<number, string>>({})
  const [carregando, setCarregando] = React.useState(true)

  const alunoId = React.useMemo(() => {
    const configurado = process.env.NEXT_PUBLIC_ALUNO_ID
    return configurado ? Number(configurado) : 1
  }, [])

  React.useEffect(() => {
    let ativo = true
    setCarregando(true)
    alunoService.listarSeminarios(alunoId)
      .then(async (seminarios: Seminario[]) => {
        const finais = seminarios.flatMap((seminario) =>
          obterSessoesSeminario(seminario)
            .filter((sessao) => sessao.status === 'finalizado' && sessao.id)
            .map((sessao) => ({ seminario, dataId: sessao.id as number }))
        )
        const carregados = await Promise.all(finais.map(async ({ seminario, dataId }) => {
          try {
            return await alunoService.compilarResultados(alunoId, dataId)
          } catch {
            return null
          }
        }))
        if (ativo) {
          setResultados(carregados.filter((item): item is ResultadoDTO => item !== null))
          setTitulos(Object.fromEntries(finais.map(({ seminario, dataId }) => [dataId, seminario.titulo])))
        }
      })
      .catch((erro) => { if (ativo) toast.error(erro instanceof Error ? erro.message : 'Erro ao carregar resultados.') })
      .finally(() => { if (ativo) setCarregando(false) })
    return () => { ativo = false }
  }, [alunoId])

  return <div className="flex flex-col gap-6">
    <PageHeader title="Meus resultados" description="Histórico de notas, critérios e feedbacks dos seus seminários." />
    {carregando ? (
      <p className="text-sm text-muted-foreground">Carregando resultados…</p>
    ) : resultados.length ? (
      resultados.map((resultado) => <section key={resultado.dataId} className="flex flex-col gap-3"><h2 className="font-display text-lg font-semibold">{titulos[resultado.dataId] ?? `Sessão ${resultado.dataSessao}`}</h2><ResultadoView resultado={resultado} /></section>)
    ) : (
      <Card><CardContent className="flex flex-col items-center gap-3 p-10 text-center"><ClipboardList className="size-8 text-muted-foreground" /><p className="text-sm text-muted-foreground">Ainda não há seminários concluídos para exibir.</p></CardContent></Card>
    )}
  </div>
}
