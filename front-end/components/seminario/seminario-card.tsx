import Link from 'next/link'
import { CalendarDays, Clock, Users } from 'lucide-react'
import { formatarDataCurta } from '@/lib/format'
import { obterSessoesSeminario } from '@/lib/types'
import type { Seminario } from '@/lib/types'
import { Badge } from '@/components/ui/badge'
import { StatusBadge } from '@/components/status-badge'
import {
  Card,
  CardContent,
  CardFooter,
  CardHeader,
  CardTitle,
} from '@/components/ui/card'

export function SeminarioCard({
  seminario,
  basePath,
}: {
  seminario: Seminario
  basePath: string
}) {
  const sessoes = obterSessoesSeminario(seminario)
  const totalIntegrantes = seminario.grupos.reduce(
    (acc, g) => acc + g.integrantesIds.length,
    0,
  )
  return (
    <Card className="group relative flex h-full flex-col transition-colors hover:border-primary/40">
      <CardHeader>
        <div className="flex items-start justify-between gap-2">
          <CardTitle className="text-pretty">
            <Link
              href={`${basePath}/${seminario.id}`}
              className="outline-none after:absolute after:inset-0 focus-visible:underline"
            >
              {seminario.titulo}
            </Link>
          </CardTitle>
        </div>
        <StatusBadge status={seminario.status} />
      </CardHeader>
      <CardContent className="flex flex-1 flex-col gap-3 text-sm text-muted-foreground">
        <p className="line-clamp-2 text-pretty">{seminario.descricao}</p>
        <div className="flex flex-wrap gap-1.5" aria-label="Datas e status das sessões">
          {sessoes.map((sessao) => <Badge key={sessao.data} variant={sessao.status === 'finalizado' ? 'outline' : 'secondary'} className={sessao.status === 'finalizado' ? 'border-emerald-300 bg-emerald-100 text-emerald-900' : ''}>{formatarDataCurta(sessao.data)} — {sessao.status === 'finalizado' ? 'Concluída' : 'Pendente'}</Badge>)}
        </div>
      </CardContent>
      <CardFooter className="mt-auto flex flex-wrap gap-x-4 gap-y-1.5 border-t bg-muted/30 text-xs text-muted-foreground">
        <span className="flex items-center gap-1.5">
          <CalendarDays className="size-3.5" />
          {sessoes.length} {sessoes.length === 1 ? 'data' : 'datas'}
        </span>
        <span className="flex items-center gap-1.5">
          <Clock className="size-3.5" />
          {seminario.horario}
        </span>
        <span className="flex items-center gap-1.5">
          <Users className="size-3.5" />
          {seminario.grupos.length} grupos · {totalIntegrantes} alunos
        </span>
      </CardFooter>
    </Card>
  )
}
