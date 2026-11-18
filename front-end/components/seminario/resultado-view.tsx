'use client'

import { Medal, Trophy, Users } from 'lucide-react'
import type { ResultadoDTO } from '@/lib/types-admin'
import { Badge } from '@/components/ui/badge'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Progress } from '@/components/ui/progress'

function fmt(n: number): string {
  return n.toFixed(2).replace('.', ',')
}

export function ResultadoView({ resultado }: { resultado: ResultadoDTO }) {
  const top3 = resultado.ranking.slice(0, 3)
  const feedbacksPorGrupo = new Map<number, ResultadoDTO['feedbacks']>()
  for (const feedback of resultado.feedbacks) {
    const atual = feedbacksPorGrupo.get(feedback.grupoId) ?? []
    atual.push(feedback)
    feedbacksPorGrupo.set(feedback.grupoId, atual)
  }

  return (
    <div className="flex flex-col gap-6">
      {resultado.semAvaliadores && (
        <p className="rounded-md border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900">
          Não houve avaliadores elegíveis nesta data. A nota dos pares foi substituída pela nota do professor.
        </p>
      )}

      <div className="grid gap-3 sm:grid-cols-3">
        {top3.map((grupo) => (
          <Card key={grupo.grupoId} className={grupo.posicao === 1 ? 'border-amber-400 bg-amber-50/60 dark:bg-amber-950/20' : ''}>
            <CardContent className="flex flex-col items-center gap-2 py-6 text-center">
              {grupo.posicao === 1 ? <Trophy className="size-7 text-amber-500" /> : <Medal className="size-7 text-muted-foreground" />}
              <span className="text-sm font-medium">{grupo.grupoNome}</span>
              <span className="text-3xl font-semibold text-foreground">{fmt(grupo.mediaGeral)}</span>
              <Badge variant="secondary">{grupo.posicao}º lugar</Badge>
            </CardContent>
          </Card>
        ))}
        {top3.length === 0 && <p className="text-sm text-muted-foreground">Nenhum grupo avaliado.</p>}
      </div>

      <Card>
        <CardHeader><CardTitle className="text-base">Ranking completo</CardTitle></CardHeader>
        <CardContent className="flex flex-col gap-4">
          {resultado.ranking.map((grupo) => (
            <div key={grupo.grupoId} className="flex flex-col gap-3 rounded-xl border p-4">
              <div className="flex flex-wrap items-center justify-between gap-2">
                <div className="flex items-center gap-2">
                  <span className="font-semibold">{grupo.posicao}º · {grupo.grupoNome}</span>
                  <Badge variant="outline">{grupo.tema}</Badge>
                </div>
                <span className="text-lg font-semibold">{fmt(grupo.mediaGeral)}</span>
              </div>
              <div className="grid gap-3 sm:grid-cols-3">
                <div><p className="text-xs text-muted-foreground">Professor</p><p className="font-medium">{fmt(grupo.notaProfessor)}</p><Progress value={grupo.notaProfessor * 10} /></div>
                <div><p className="text-xs text-muted-foreground">Pares</p><p className="font-medium">{fmt(grupo.notaAlunos)}</p><Progress value={grupo.notaAlunos * 10} /></div>
                <div><p className="text-xs text-muted-foreground">Avaliadores</p><p className="font-medium">{grupo.avaliadoresConsiderados}</p></div>
              </div>
              {grupo.integrantes.length > 0 && (
                <div className="flex flex-col gap-1 border-t pt-3">
                  <span className="flex items-center gap-2 text-xs font-medium text-muted-foreground"><Users className="size-3" />Integrantes</span>
                  {grupo.integrantes.map((integrante) => (
                    <div key={integrante.alunoId} className="flex items-center justify-between text-sm">
                      <span>{integrante.nome}</span>
                      <span className="font-medium">{fmt(integrante.notaFinal)}</span>
                    </div>
                  ))}
                </div>
              )}
              {(feedbacksPorGrupo.get(grupo.grupoId) ?? []).map((feedback, index) => (
                <p key={index} className="rounded-md bg-muted/50 p-3 text-sm text-muted-foreground">
                  <strong className="text-foreground">{feedback.avaliadorNome ?? feedback.tipo}:</strong> {feedback.texto}
                </p>
              ))}
            </div>
          ))}
        </CardContent>
      </Card>
    </div>
  )
}
