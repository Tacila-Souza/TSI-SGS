import { Badge } from '@/components/ui/badge'
import { cn } from '@/lib/utils'
import { statusSeminarioLabel } from '@/lib/format'
import type { SeminarioStatus } from '@/lib/types'

const styles: Record<SeminarioStatus, string> = {
  rascunho: 'bg-muted text-muted-foreground border-transparent',
  agendado: 'bg-primary/10 text-primary border-transparent',
  em_andamento:
    'bg-amber-100 text-amber-700 border-transparent dark:bg-amber-500/20 dark:text-amber-300',
  finalizado: 'bg-success/15 text-success border-transparent',
}

export function StatusBadge({ status }: { status: SeminarioStatus }) {
  return (
    <Badge className={cn('font-medium', styles[status])}>
      {status === 'em_andamento' && (
        <span className="mr-1 inline-block size-1.5 animate-pulse rounded-full bg-current" />
      )}
      {statusSeminarioLabel[status]}
    </Badge>
  )
}
