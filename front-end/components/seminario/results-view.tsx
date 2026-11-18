'use client'

import { Medal, Trophy, Users } from 'lucide-react'
import { calcularNotaGrupo, useApp } from '@/lib/app-context'
import { nomeUsuario } from '@/lib/mock-data'
import { formatarNota } from '@/lib/format'
import type { Avaliacao, Seminario } from '@/lib/types'
import { cn } from '@/lib/utils'
import { Avatar, AvatarFallback } from '@/components/ui/avatar'
import { Badge } from '@/components/ui/badge'
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card'
import { Progress } from '@/components/ui/progress'
import { iniciais } from '@/lib/mock-data'

function calcularNotaIndividualFinal(seminario: Seminario, avaliacoes: Avaliacao[], grupoId: string, alunoId: string) {
  const avaliacaoProfessor = avaliacoes.find((avaliacao) => avaliacao.grupoId === grupoId && avaliacao.tipo === 'professor')
  const pesoTotal = seminario.criterios.reduce((total, criterio) => total + criterio.peso, 0) || 1
  const notaProfessor = avaliacaoProfessor
    ? seminario.criterios.reduce((total, criterio) => {
        const nota = criterio.tipo === 'individual'
          ? avaliacaoProfessor.notas[`${criterio.id}:${alunoId}`] ?? avaliacaoProfessor.notas[criterio.id] ?? 0
          : avaliacaoProfessor.notas[criterio.id] ?? 0
        return total + nota * criterio.peso
      }, 0) / pesoTotal
    : null
  const mediaAlunos = 10
  return notaProfessor == null
    ? mediaAlunos
    : notaProfessor * seminario.pesoProfessor + mediaAlunos * (1 - seminario.pesoProfessor)
}

export function ResultsView({

  seminario,
  destaqueGrupoId,
  avaliacoes: avaliacoesRecebidas,
}: {
  seminario: Seminario
  destaqueGrupoId?: string
  avaliacoes?: Avaliacao[]
}) {
  const { avaliacoes: todasAvaliacoes, usuarios } = useApp()
  const avaliacoes = avaliacoesRecebidas ?? todasAvaliacoes.filter((avaliacao) => avaliacao.seminarioId === seminario.id)

  const pesoProfessor = seminario.pesoProfessor
  const pesoAlunos = 1 - pesoProfessor
  const ranking = seminario.grupos
    .map((g) => {
      const calculado = calcularNotaGrupo(seminario, avaliacoes, g.id)
      const mediaPares = 10
      return {
        grupo: g,
        ...calculado,
        mediaPares,
        notaFinal: calculado.notaProfessor == null
          ? mediaPares
          : calculado.notaProfessor * pesoProfessor + mediaPares * pesoAlunos,
      }
    })
    .sort((a, b) => (b.notaFinal ?? -1) - (a.notaFinal ?? -1))

  const medalha = ['text-warning-foreground', 'text-muted-foreground', 'text-primary']

  return (
    <div className="flex flex-col gap-6">
      {/* Pódio / ranking resumido */}
      <div className="grid gap-4 md:grid-cols-3">
        {ranking.slice(0, 3).map((r, i) => (
          <Card
            key={r.grupo.id}
            className={cn(
              i === 0 && 'border-warning/40 bg-warning/5',
            )}
          >
            <CardContent className="flex items-center gap-4 py-1">
              <div className="flex flex-col items-center">
                {i === 0 ? (
                  <Trophy className={cn('size-7', medalha[0])} />
                ) : (
                  <Medal className={cn('size-7', medalha[i] ?? 'text-muted-foreground')} />
                )}
                <span className="mt-1 text-xs font-medium text-muted-foreground">
                  {i + 1}º
                </span>
              </div>
              <div className="flex flex-col leading-tight">
                <span className="text-sm text-muted-foreground">{r.grupo.nome}</span>
                <span className="font-medium text-foreground text-pretty">
                  {r.grupo.tema}
                </span>
                <span className="font-display text-2xl font-semibold text-foreground">
                  {formatarNota(r.notaFinal)}
                </span>
              </div>
            </CardContent>
          </Card>
        ))}
      </div>

      {/* Detalhamento por grupo */}
      <div className="flex flex-col gap-4">
        {ranking.map((r) => (
          <Card
            key={r.grupo.id}
            className={cn(
              destaqueGrupoId === r.grupo.id && 'border-primary ring-1 ring-primary/20',
            )}
          >
            <CardHeader>
              <div className="flex flex-wrap items-center justify-between gap-3">
                <div className="flex flex-col gap-1">
                  <CardTitle className="flex items-center gap-2">
                    {r.grupo.nome}
                    <span className="text-sm font-normal text-muted-foreground">
                      · {r.grupo.tema}
                    </span>
                  </CardTitle>
                  <CardDescription className="flex items-center gap-1.5">
                    <Users className="size-3.5" />
                    {r.grupo.integrantesIds.map(nomeUsuario).join(', ')}
                  </CardDescription>
                </div>
                <div className="flex flex-col items-end">
                  <span className="text-xs text-muted-foreground">Média Geral</span>
                  <span className="font-display text-3xl font-semibold text-foreground">
                    {formatarNota(r.notaFinal)}
                  </span>
                </div>
              </div>
            </CardHeader>
            <CardContent className="flex flex-col gap-5">
              <div className="grid gap-4 sm:grid-cols-2">
                <div className="flex flex-col gap-1.5">
                  <div className="flex items-center justify-between text-sm">
                    <span className="text-muted-foreground">
                      Professor · peso {Math.round(seminario.pesoProfessor * 100)}%
                    </span>
                    <span className="font-medium text-foreground">
                      {formatarNota(r.notaProfessor)}
                    </span>
                  </div>
                  <Progress value={(r.notaProfessor ?? 0) * 10} />
                </div>
                <div className="flex flex-col gap-1.5">
                  <div className="flex items-center justify-between text-sm">
                    <span className="text-muted-foreground">
                      Alunos · peso {Math.round(pesoAlunos * 100)}% ({r.totalPares} avaliações)
                    </span>
                    <span className="font-medium text-foreground">
                      {formatarNota(r.mediaPares)}
                    </span>
                  </div>
                  <Progress value={(r.mediaPares ?? 0) * 10} />
                </div>
              </div>

              <div className="flex flex-col gap-2">
                <div className="flex flex-wrap items-center justify-between gap-2">
                  <span className="text-xs font-medium tracking-wide text-muted-foreground uppercase">Avaliação dos alunos · mock</span>
                  <Badge variant="outline">Peso na nota final: {Math.round(pesoAlunos * 100)}%</Badge>
                </div>
                <div className="flex flex-wrap gap-2">
                  {seminario.criterios.map((criterio) => <Badge key={criterio.id} variant="secondary" className="gap-1.5 font-normal">{criterio.nome}<span className="text-muted-foreground">{criterio.tipo === 'individual' ? 'Individual' : 'Em Grupo'} · {criterio.peso}%</span><span className="font-semibold text-foreground">10.0</span></Badge>)}
                </div>
                <p className="text-sm text-muted-foreground">Média dos alunos: <span className="font-semibold text-foreground">10.0</span></p>
              </div>

              {/* Notas por critério (avaliação do professor) */}
              <div className="flex flex-col gap-2">
                <span className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                  Notas do professor por critério
                </span>
                <div className="flex flex-wrap gap-2">
                  {seminario.criterios.map((c) => {
                    const av = avaliacoes.find(
                      (a) =>
                        a.seminarioId === seminario.id &&
                        a.grupoId === r.grupo.id &&
                        a.tipo === 'professor',
                    )
                    const notasIntegrantes = c.tipo === 'individual' ? r.grupo.integrantesIds.map((alunoId) => av?.notas[`${c.id}:${alunoId}`]).filter((valor): valor is number => valor !== undefined) : []
                    const nota = notasIntegrantes.length ? notasIntegrantes.reduce((total, valor) => total + valor, 0) / notasIntegrantes.length : av?.notas[c.id]
                    return (
                      <Badge
                        key={c.id}
                        variant="secondary"
                        className="gap-1.5 font-normal"
                      >
                        {c.nome}
                        <span className="text-muted-foreground">
                          {c.tipo === 'individual' ? 'Individual' : 'Em Grupo'} · {c.peso}%
                        </span>
                        <span className="font-semibold text-foreground">
                          {nota != null ? formatarNota(nota) : '—'}
                        </span>
                      </Badge>
                    )
                  })}
                </div>
              </div>

              <section className="flex flex-col gap-3">
                <h3 className="text-sm font-semibold">Nota final individual por integrante</h3>
                <div className="grid gap-2 sm:grid-cols-2">
                  {r.grupo.integrantesIds.map((alunoId) => {
                    const aluno = usuarios.find((usuario) => usuario.id === alunoId)
                    return <div key={alunoId} className="flex items-center justify-between gap-3 rounded-lg border p-3"><span className="truncate text-sm font-medium">{aluno?.nome ?? nomeUsuario(alunoId)}</span><Badge variant="secondary">{formatarNota(calcularNotaIndividualFinal(seminario, avaliacoes, r.grupo.id, alunoId))}</Badge></div>
                  })}
                </div>
              </section>

              <section className="flex flex-col gap-3">
                <h3 className="text-sm font-semibold">Feedbacks</h3>
                <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                  <div className="flex flex-col gap-2 rounded-lg border p-4">
                    <h4 className="text-sm font-semibold">Feedback do Professor</h4>
                    {avaliacoes.filter((avaliacao) => avaliacao.grupoId === r.grupo.id && avaliacao.tipo === 'professor' && avaliacao.feedback.trim()).map((avaliacao) => <div key={avaliacao.id} className="flex items-start gap-3 rounded-lg bg-muted/60 p-3"><Avatar className="size-8"><AvatarFallback className="bg-primary/10 text-[10px] font-medium text-primary">{iniciais(nomeUsuario(avaliacao.avaliadorId))}</AvatarFallback></Avatar><div><p className="text-xs font-semibold text-muted-foreground">{nomeUsuario(avaliacao.avaliadorId)}</p><p className="text-sm leading-relaxed text-foreground text-pretty">{avaliacao.feedback}</p></div></div>)}
                    {!avaliacoes.some((avaliacao) => avaliacao.grupoId === r.grupo.id && avaliacao.tipo === 'professor' && avaliacao.feedback.trim()) && <p className="text-sm text-muted-foreground">Nenhum feedback registrado.</p>}
                  </div>
                  <div className="flex flex-col gap-2 rounded-lg border p-4">
                    <div className="flex flex-wrap items-center justify-between gap-2"><h4 className="text-sm font-semibold">Feedback dos Alunos</h4><Badge variant="outline">Resumo do feedback de todos os alunos</Badge></div>
                    {avaliacoes.filter((avaliacao) => avaliacao.grupoId === r.grupo.id && avaliacao.tipo === 'par' && avaliacao.feedback.trim()).map((avaliacao) => <div key={avaliacao.id} className="flex items-start gap-3 rounded-lg bg-muted/60 p-3"><Avatar className="size-8"><AvatarFallback className="text-[10px] font-medium">{iniciais(nomeUsuario(avaliacao.avaliadorId))}</AvatarFallback></Avatar><div><p className="text-xs font-semibold text-muted-foreground">{nomeUsuario(avaliacao.avaliadorId)}</p><p className="text-sm leading-relaxed text-foreground text-pretty">{avaliacao.feedback}</p></div></div>)}
                    {!avaliacoes.some((avaliacao) => avaliacao.grupoId === r.grupo.id && avaliacao.tipo === 'par' && avaliacao.feedback.trim()) && <p className="text-sm text-muted-foreground">Nenhum feedback registrado.</p>}
                  </div>
                </div>
              </section>
            </CardContent>
          </Card>
        ))}
      </div>
    </div>
  )
}
