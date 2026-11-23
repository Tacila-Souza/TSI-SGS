'use client'

import * as React from 'react'
import Link from 'next/link'
import {
  BookOpen,
  GraduationCap,
  Presentation,
  Users,
} from 'lucide-react'
import { toast } from 'sonner'
import { useApp } from '@/lib/app-context'
import { adminService } from '@/lib/services'
import type { Curso, DisciplinaAdmin } from '@/lib/types-admin'
import { PageHeader } from '@/components/page-header'
import { StatCard } from '@/components/stat-card'
import { StatusBadge } from '@/components/status-badge'
import { Button } from '@/components/ui/button'
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Empty, EmptyDescription, EmptyMedia, EmptyTitle } from '@/components/ui/empty'

export default function AdminDashboard() {
  const { usuarios, seminarios, carregandoSeminarios } = useApp()
  const [cursos, setCursos] = React.useState<Curso[]>([])
  const [carregandoCursos, setCarregandoCursos] = React.useState(true)

  React.useEffect(() => {
    let ativo = true
    Promise.all([
      adminService.listarCursos(),
      adminService.listarDisciplinas(),
    ])
      .then(([listaCursos, listaDisciplinas]) => {
        if (!ativo) return
        const porCurso = new Map<string, DisciplinaAdmin[]>()
        for (const disciplina of listaDisciplinas) {
          const atual = porCurso.get(disciplina.cursoId) ?? []
          atual.push(disciplina)
          porCurso.set(disciplina.cursoId, atual)
        }
        setCursos(
          listaCursos.map((curso) => ({
            ...curso,
            disciplinas: porCurso.get(curso.id) ?? [],
          })),
        )
      })
      .catch(() => {
        if (ativo) toast.error('Não foi possível carregar os cursos da API.')
      })
      .finally(() => {
        if (ativo) setCarregandoCursos(false)
      })
    return () => {
      ativo = false
    }
  }, [])

  const alunos = usuarios.filter((u) => u.role === 'aluno')
  const professores = usuarios.filter((u) => u.role === 'professor')
  const profAtivos = professores.filter((p) => p.status === 'ativo')
  const disciplinas = cursos.flatMap((curso) => curso.disciplinas)

  function nomeUsuario(id: string): string {
    return usuarios.find((usuario) => usuario.id === id)?.nome ?? '—'
  }

  return (
    <>
      <PageHeader
        title="Painel da coordenação"
        description="Visão geral dos cursos, usuários e seminários da instituição."
      />

      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <StatCard label="Cursos" value={cursos.length} icon={BookOpen} accent="muted" hint="Cursos cadastrados" />
        <StatCard label="Alunos ativos" value={alunos.filter((a) => a.status === 'ativo').length} icon={Users} hint={`${alunos.length} no total`} />
        <StatCard label="Professores ativos" value={profAtivos.length} icon={GraduationCap} accent="success" hint={`${professores.length} cadastrados`} />
        <StatCard label="Seminários" value={seminarios.length} icon={Presentation} accent="warning" />
      </div>

      <div className="grid gap-6 lg:grid-cols-[1.4fr_1fr]">
        <Card>
          <CardHeader>
            <CardTitle>Cursos</CardTitle>
            <CardDescription>
              Cursos ativos, disciplinas e professores responsáveis.
            </CardDescription>
          </CardHeader>
          <CardContent className="grid grid-cols-1 gap-4 md:grid-cols-2">
            {carregandoCursos ? (
              <p className="col-span-full text-sm text-muted-foreground">Carregando cursos…</p>
            ) : disciplinas.length === 0 ? (
              <Empty className="col-span-full py-8">
                <EmptyMedia variant="icon"><BookOpen /></EmptyMedia>
                <EmptyTitle>Nenhuma disciplina cadastrada</EmptyTitle>
                <EmptyDescription>Nenhuma disciplina cadastrada no banco de dados.</EmptyDescription>
              </Empty>
            ) : disciplinas.map((disciplina) => {
              const curso = cursos.find((item) => item.id === disciplina.cursoId)
              return (
                <div
                  key={disciplina.id}
                  className="flex items-center justify-between gap-4 rounded-xl border border-border p-4"
                >
                  <div className="flex items-center gap-3">
                    <div className="flex size-10 items-center justify-center rounded-lg bg-primary/10 text-primary">
                      <BookOpen className="size-5" />
                    </div>
                    <div className="flex flex-col leading-tight">
                      <span className="font-medium text-foreground">
                        {disciplina.nome}
                      </span>
                      <span className="text-sm text-muted-foreground">
                        {curso?.nome ?? '—'} · Prof. {disciplina.professorNome || nomeUsuario(disciplina.professorId)}
                      </span>
                    </div>
                  </div>
                  <Badge variant="secondary" className="shrink-0 font-normal">
                    {disciplina.alunoIds.length} alunos
                  </Badge>
                </div>
              )
            })}
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Gestão rápida</CardTitle>
            <CardDescription>Atalhos de administração.</CardDescription>
          </CardHeader>
          <CardContent className="flex w-full flex-col gap-3">
            <Button variant="outline" nativeButton={false} className="h-auto min-h-16 w-full justify-start gap-3 p-4" render={<Link href="/admin/cursos" />}>
              <span className="flex size-10 items-center justify-center rounded-lg bg-primary/10 text-primary"><BookOpen className="size-5" /></span>
              <span className="flex flex-col items-start"><span className="font-semibold">Cursos</span><span className="text-xs text-muted-foreground">Cursos e disciplinas</span></span>
            </Button>
            <Button variant="outline" nativeButton={false} className="h-auto min-h-16 w-full justify-start gap-3 p-4" render={<Link href="/admin/alunos" />}>
              <span className="flex size-10 items-center justify-center rounded-lg bg-primary/10 text-primary"><Users className="size-5" /></span>
              <span className="flex flex-col items-start"><span className="font-semibold">Gerenciar alunos</span><span className="text-xs text-muted-foreground">Cadastros e matrículas</span></span>
            </Button>
            <Button variant="outline" nativeButton={false} className="h-auto min-h-16 w-full justify-start gap-3 p-4" render={<Link href="/admin/professores" />}>
              <span className="flex size-10 items-center justify-center rounded-lg bg-primary/10 text-primary"><GraduationCap className="size-5" /></span>
              <span className="flex flex-col items-start"><span className="font-semibold">Gerenciar professores</span><span className="text-xs text-muted-foreground">Cadastros e vínculos</span></span>
            </Button>
          </CardContent>
        </Card>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Seminários recentes</CardTitle>
          <CardDescription>
            Acompanhe o andamento dos seminários dos cursos.
          </CardDescription>
        </CardHeader>
        <CardContent className="flex flex-col gap-3">
          {carregandoSeminarios ? (
            <p className="py-8 text-sm text-muted-foreground">Carregando seminários…</p>
          ) : seminarios.length === 0 ? (
            <Empty className="py-8">
              <EmptyMedia variant="icon"><Presentation /></EmptyMedia>
              <EmptyTitle>Nenhum seminário cadastrado</EmptyTitle>
              <EmptyDescription>Nenhum seminário cadastrado no banco de dados.</EmptyDescription>
            </Empty>
          ) : seminarios.map((s) => (
            <div
              key={s.id}
              className="flex flex-col gap-2 rounded-xl border border-border p-4 sm:flex-row sm:items-center sm:justify-between"
            >
              <div className="flex flex-col leading-tight">
                <span className="font-medium text-foreground">{s.titulo}</span>
                <span className="text-sm text-muted-foreground">
                  {s.disciplina} · {s.grupos.length} grupos · Prof. {nomeUsuario(s.professorId)}
                </span>
              </div>
              <StatusBadge status={s.status} />
            </div>
          ))}
        </CardContent>
      </Card>
    </>
  )
}
