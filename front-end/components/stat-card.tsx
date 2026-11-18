import type { LucideIcon } from 'lucide-react'
import { Card, CardContent } from '@/components/ui/card'
import { cn } from '@/lib/utils'

interface StatCardProps {
  label: string
  value: React.ReactNode
  icon: LucideIcon
  hint?: string
  accent?: 'primary' | 'success' | 'warning' | 'muted'
}

const accents: Record<NonNullable<StatCardProps['accent']>, string> = {
  primary: 'bg-primary/10 text-primary',
  success: 'bg-success/15 text-success',
  warning: 'bg-orange-100 text-orange-600 dark:bg-amber-500/20 dark:text-amber-400',
  muted: 'bg-muted text-muted-foreground',
}

export function StatCard({
  label,
  value,
  icon: Icon,
  hint,
  accent = 'primary',
}: StatCardProps) {
  return (
    <Card>
      <CardContent className="flex items-center gap-4 py-1">
        <div
          className={cn(
            'flex size-11 shrink-0 items-center justify-center rounded-xl',
            accents[accent],
          )}
        >
          <Icon className="size-5" />
        </div>
        <div className="flex flex-col gap-0.5">
          <span className="text-sm text-muted-foreground">{label}</span>
          <span className="font-display text-2xl font-semibold leading-none text-foreground">
            {value}
          </span>
          {hint && <span className="text-xs text-muted-foreground">{hint}</span>}
        </div>
      </CardContent>
    </Card>
  )
}
