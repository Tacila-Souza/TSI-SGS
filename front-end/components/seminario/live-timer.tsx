'use client'

import { Pause, Play, RotateCcw } from 'lucide-react'
import type { LiveState } from '@/lib/types'
import { Button } from '@/components/ui/button'

export function LiveTimer({
  live,
  onStart,
  onPause,
  onResume,
  onReset,
  canStart = true,
}: {
  live: LiveState
  onStart: () => void
  onPause: () => void
  onResume: () => void
  onReset: () => void
  canStart?: boolean
}) {
  const minutos = Math.floor(live.remainingSec / 60).toString().padStart(2, '0')
  const segundos = (live.remainingSec % 60).toString().padStart(2, '0')
  return <div className="flex flex-col items-center gap-4"><div className="font-mono text-6xl font-semibold tracking-tight tabular-nums" aria-live="polite">{minutos}:{segundos}</div><div className="flex gap-2">{live.running ? <Button onClick={onPause}><Pause data-icon="inline-start" />Pausar</Button> : <Button disabled={live.status === 'aguardando' && !canStart} onClick={live.status === 'aguardando' ? onStart : onResume}><Play data-icon="inline-start" />{live.status === 'aguardando' ? 'Iniciar' : 'Continuar'}</Button>}<Button variant="outline" onClick={onReset}><RotateCcw data-icon="inline-start" />Reiniciar</Button></div></div>
}
