'use client'

import { AppShell } from '@/components/app-shell'
import { Avatar, AvatarFallback } from '@/components/ui/avatar'
import { Badge } from '@/components/ui/badge'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Separator } from '@/components/ui/separator'
import { useApp } from '@/lib/app-context'
import { iniciais } from '@/lib/mock-data'
import { turmas } from '@/lib/mock-data'
import { BookOpen, Mail, Hash, GraduationCap } from 'lucide-react'

const roleNames = {
  coordenador: 'Coordenador(a)',
  professor: 'Professor(a)',
  aluno: 'Aluno(a)',
} as const

export default function PerfilPage() {
  const { currentUser } = useApp()

  if (!currentUser) {
    return <div className="flex min-h-svh items-center justify-center text-sm text-muted-foreground">Redirecionando…</div>
  }

  const isProfessor = currentUser.role === 'professor'
  const isAluno = currentUser.role === 'aluno'
  const disciplinas = isProfessor
    ? turmas.filter((turma) => turma.professorId === currentUser.id)
    : isAluno
      ? turmas.filter((turma) => currentUser.turmaIds.includes(turma.id))
      : []

  return (
    <AppShell role={currentUser.role}>
      <div className="mx-auto flex w-full max-w-5xl flex-col gap-6">
        <header className="flex flex-col gap-2">
          <p className="text-sm font-medium text-primary">Conta e identidade acadêmica</p>
          <h1 className="font-display text-2xl font-semibold tracking-tight text-foreground sm:text-3xl">Meu perfil</h1>
          <p className="max-w-2xl text-sm text-muted-foreground">Confira seus dados cadastrados e os vínculos acadêmicos associados à sua conta.</p>
        </header>

        <div className="grid gap-6 lg:grid-cols-[minmax(0,0.8fr)_minmax(0,1.2fr)]">
          <Card className="overflow-hidden">
            <div className="h-24 bg-primary/10" />
            <CardContent className="relative -mt-10 flex flex-col gap-5 px-6 pb-6">
              <Avatar className="size-20 border-4 border-card shadow-sm">
                <AvatarFallback className="bg-primary text-xl font-semibold text-primary-foreground">{iniciais(currentUser.nome)}</AvatarFallback>
              </Avatar>
              <div className="flex flex-col gap-1">
                <h2 className="font-display text-xl font-semibold text-foreground">{currentUser.nome}</h2>
                <Badge variant="secondary" className="w-fit">{roleNames[currentUser.role]}</Badge>
              </div>
              <Separator />
              <div className="flex flex-col gap-4 text-sm">
                <div className="flex items-start gap-3">
                  <Mail className="mt-0.5 size-4 shrink-0 text-muted-foreground" />
                  <div className="flex min-w-0 flex-col gap-1"><span className="text-xs text-muted-foreground">E-mail institucional</span><span className="break-all font-medium text-foreground">{currentUser.email}</span></div>
                </div>
                <div className="flex items-start gap-3">
                  {isAluno ? <Hash className="mt-0.5 size-4 shrink-0 text-muted-foreground" /> : <GraduationCap className="mt-0.5 size-4 shrink-0 text-muted-foreground" />}
                  <div className="flex flex-col gap-1"><span className="text-xs text-muted-foreground">{isAluno ? 'Número de matrícula' : 'Registro funcional'}</span><span className="font-medium text-foreground">{currentUser.registro}</span></div>
                </div>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <div className="flex items-center gap-3">
                <div className="flex size-10 items-center justify-center rounded-xl bg-primary/10 text-primary"><BookOpen className="size-5" /></div>
                <div><CardTitle>{isProfessor ? 'Disciplinas ministradas' : isAluno ? 'Disciplinas matriculadas' : 'Vínculos acadêmicos'}</CardTitle><CardDescription>{isProfessor ? 'Disciplinas sob sua responsabilidade.' : isAluno ? 'Disciplinas vinculadas à sua matrícula.' : 'Informações acadêmicas da conta.'}</CardDescription></div>
              </div>
            </CardHeader>
            <CardContent>
              {disciplinas.length > 0 ? (
                <div className="grid gap-3 sm:grid-cols-2">
                  {disciplinas.map((turma) => (
                    <div key={turma.id} className="flex flex-col gap-3 rounded-xl border border-border bg-muted/20 p-4">
                      <div className="flex items-start justify-between gap-3"><span className="font-medium text-foreground">{turma.disciplina}</span></div>
                      <span className="text-sm text-muted-foreground">Disciplina {turma.nome}</span>
                    </div>
                  ))}
                </div>
              ) : (
                <div className="rounded-xl border border-dashed border-border p-6 text-center text-sm text-muted-foreground">Nenhuma disciplina vinculada no momento.</div>
              )}
            </CardContent>
          </Card>
        </div>
      </div>
    </AppShell>
  )
}
