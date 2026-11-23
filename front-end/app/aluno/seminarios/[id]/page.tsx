'use client'

import * as React from 'react'
import Link from 'next/link'
import { ArrowLeft, Radio, Users } from 'lucide-react'
import { useParams } from 'next/navigation'
import { toast } from 'sonner'
import { alunoService } from '@/lib/services'
import type { ResultadoDTO } from '@/lib/types-admin'
import type { Seminario } from '@/lib/types'
import { obterSessoesSeminario } from '@/lib/types'
import { PageHeader } from '@/components/page-header'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { ResultadoView } from '@/components/seminario/resultado-view'

export default function AlunoSeminarioDetalhePage() {
  const { id } = useParams<{ id: string }>()
  const [seminario, setSeminario] = React.useState<Seminario | null>(null)
  const [resultado, setResultado] = React.useState<ResultadoDTO | null>(null)
  const [carregando, setCarregando] = React.useState(true)

  const alunoId = React.useMemo(() => {
    const configurado = process.env.NEXT_PUBLIC_ALUNO_ID
    return configurado ? Number(configurado) : 1
  }, [])

  React.useEffect(() => {
    let ativo = true
    setCarregando(true)
    alunoService.detalhesSeminario(alunoId, id)
      .then(async (carregado) => {
        if (!ativo) return
        setSeminario(carregado)
        const sessoes = obterSessoesSeminario(carregado)
        const finalizada = sessoes.find((sessao) => sessao.status === 'finalizado' && sessao.id)
        if (finalizada?.id) {
          try {
            const dados = await alunoService.compilarResultados(alunoId, finalizada.id)
            if (ativo) setResultado(dados)
          } catch (erro) {
            if (ativo) toast.error(erro instanceof Error ? erro.message : 'Erro ao carregar resultados.')
          }
        }
      })
      .catch((erro) => { if (ativo) toast.error(erro instanceof Error ? erro.message : 'Seminário não encontrado.') })
      .finally(() => { if (ativo) setCarregando(false) })
    return () => { ativo = false }
  }, [alunoId, id])

  if (carregando && !seminario) return <Card><CardContent className="p-8 text-center">Carregando seminário…</CardContent></Card>
  if (!seminario) return <Card><CardContent className="p-8 text-center">Seminário não encontrado.</CardContent></Card>

  const sessoes = obterSessoesSeminario(seminario)
  const sessao = sessoes.find((item) => item.status === 'finalizado') ?? sessoes[0]
  const sessaoAoVivo = sessoes.find((item) => item.status !== 'finalizado')
  const grupos = sessao?.grupos ?? seminario.grupos

  return <div className="flex flex-col gap-6">
    <PageHeader title={seminario.titulo} description={seminario.descricao}>
      <Button variant="outline" nativeButton={false} render={<Link href="/aluno/seminarios" />}><ArrowLeft data-icon="inline-start" />Voltar</Button>
      {sessaoAoVivo && <Button nativeButton={false} render={<Link href={`/aluno/seminarios/${seminario.id}/ao-vivo?data=${encodeURIComponent(sessaoAoVivo.data)}`} />}><Radio data-icon="inline-start" />Acompanhar ao vivo</Button>}
    </PageHeader>
    {sessao?.status === 'finalizado' ? (
      <section className="flex flex-col gap-3">
        <h2 className="text-lg font-semibold">Histórico e resultados</h2>
        <p className="text-sm text-muted-foreground">Ranking da sessão de {sessao.data}.</p>
        {resultado ? <ResultadoView resultado={resultado} /> : <p className="text-sm text-muted-foreground">Resultados indisponíveis.</p>}
      </section>
    ) : (
      <Card>
        <CardHeader><CardTitle>Grupos</CardTitle></CardHeader>
        <CardContent className="grid gap-3 md:grid-cols-2">
          {grupos.map((grupo) => <div key={grupo.id} className="rounded-lg border p-4"><div className="flex items-center justify-between"><span className="font-medium">{grupo.nome}</span><Badge variant="secondary"><Users data-icon="inline-start" className="mr-1 size-3" />{grupo.integrantesIds.length} alunos</Badge></div><p className="mt-2 text-sm text-muted-foreground">{grupo.tema}</p></div>)}
          {grupos.length === 0 && <p className="text-sm text-muted-foreground">Nenhum grupo definido.</p>}
        </CardContent>
      </Card>
    )}
  </div>
}
