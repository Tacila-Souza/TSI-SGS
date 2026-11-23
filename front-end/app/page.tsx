'use client'

import { useRouter } from 'next/navigation'
import {
  ArrowRight,
  GraduationCap,
  ListChecks,
  Presentation,
  Radio,
  Star,
  Users,
} from 'lucide-react'
import { useApp } from '@/lib/app-context'
import type { Role, User } from '@/lib/types'
import { Button } from '@/components/ui/button'

const roleInfo: Record<
  Role,
  { titulo: string; icon: typeof Users; descricao: string; home: string }
> = {
  coordenador: {
    titulo: 'Admin',
    icon: Users,
    descricao: 'Gerencia alunos, professores e vínculos de disciplinas.',
    home: '/admin',
  },
  professor: {
    titulo: 'Professor',
    icon: GraduationCap,
    descricao: 'Cria seminários, define critérios e conduz apresentações.',
    home: '/professor',
  },
  aluno: {
    titulo: 'Aluno',
    icon: Presentation,
    descricao: 'Acompanha seminários, apresenta e avalia os colegas.',
    home: '/aluno',
  },
}

const destaques = [
  { icon: ListChecks, texto: 'Critérios de avaliação personalizáveis por seminário' },
  { icon: Radio, texto: 'Cronômetro sincronizado durante as apresentações ao vivo' },
  { icon: Star, texto: 'Avaliação por pares com peso maior para o professor' },
]

/** Admin do MVP: identidade fixa, sem registro no banco. */
const ADMIN_TESTE: User = {
  id: 'u-coord',
  nome: 'Administrador do Sistema',
  email: 'admin@instituto.edu.br',
  role: 'coordenador',
  status: 'ativo',
  registro: 'SIAPE 100234',
  turmaIds: [],
}

export default function LoginPage() {
  const { loginAs, usuarios, carregandoUsuarios } = useApp()
  const router = useRouter()

  function usuarioPara(role: Role): User | undefined {
    if (role === 'coordenador') return ADMIN_TESTE
    return usuarios.find((usuario) => usuario.role === role && usuario.status === 'ativo')
  }

  function entrar(role: Role) {
    const user = usuarioPara(role)
    if (!user) return
    loginAs(user)
    router.push(roleInfo[role].home)
  }

  return (
    <div className="grid min-h-svh lg:grid-cols-[1.1fr_1fr]">
      {/* Painel de marca */}
      <div className="relative hidden flex-col justify-between overflow-hidden bg-primary p-10 text-primary-foreground lg:flex">
        <div className="relative z-10 flex items-center gap-2.5">
          <div className="flex size-10 items-center justify-center rounded-xl bg-primary-foreground/15">
            <Presentation className="size-5" />
          </div>
          <span className="font-display text-lg font-semibold">The Seven</span>
        </div>

        <div className="relative z-10 flex flex-col gap-6">
          <h1 className="max-w-md font-display text-4xl font-semibold leading-tight text-balance">
            Organize e avalie seminários acadêmicos em um só lugar.
          </h1>
          <p className="max-w-md leading-relaxed text-primary-foreground/80 text-pretty">
            Uma plataforma para coordenação, professores e alunos conduzirem
            apresentações presenciais com critérios claros, cronômetro ao vivo e
            avaliação colaborativa.
          </p>
          <ul className="flex flex-col gap-3">
            {destaques.map((d) => (
              <li key={d.texto} className="flex items-center gap-3">
                <div className="flex size-8 shrink-0 items-center justify-center rounded-lg bg-primary-foreground/15">
                  <d.icon className="size-4" />
                </div>
                <span className="text-sm text-primary-foreground/90">{d.texto}</span>
              </li>
            ))}
          </ul>
        </div>

        <p className="relative z-10 text-xs text-primary-foreground/60">
          Ambiente de testes · três perfis fixos
        </p>

        <div
          aria-hidden
          className="pointer-events-none absolute -right-24 -bottom-24 size-96 rounded-full bg-primary-foreground/5"
        />
        <div
          aria-hidden
          className="pointer-events-none absolute -top-16 -left-10 size-64 rounded-full bg-primary-foreground/5"
        />
      </div>

      {/* Painel de acesso */}
      <div className="flex items-center justify-center bg-background p-6 sm:p-10">
        <div className="flex w-full max-w-md flex-col gap-8">
          <div className="flex flex-col gap-2 lg:hidden">
            <div className="flex items-center gap-2.5">
              <div className="flex size-9 items-center justify-center rounded-xl bg-primary text-primary-foreground">
                <Presentation className="size-5" />
              </div>
              <span className="font-display text-lg font-semibold">The Seven</span>
            </div>
          </div>

          <div className="flex flex-col gap-1.5">
            <h2 className="font-display text-2xl font-semibold tracking-tight">
              Acessar plataforma
            </h2>
            <p className="text-sm text-muted-foreground">
              Acesse diretamente com um dos três perfis fixos de teste.
            </p>
          </div>

          <div className="flex flex-col gap-3">
            {carregandoUsuarios ? (
              <p className="text-sm text-muted-foreground">Carregando perfis…</p>
            ) : (
              (Object.keys(roleInfo) as Role[]).map((role) => {
                const profile = roleInfo[role]
                const user = usuarioPara(role)
                if (!user) return null

                return (
                  <Button
                    key={role}
                    variant="outline"
                    className="h-auto justify-between gap-4 p-4 text-left"
                    onClick={() => entrar(role)}
                    aria-label={`Entrar como ${profile.titulo}: ${user.nome}`}
                  >
                    <span className="flex min-w-0 items-center gap-3">
                      <span className="flex size-10 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary">
                        <profile.icon className="size-5" />
                      </span>
                      <span className="flex min-w-0 flex-col">
                        <span className="font-medium">{profile.titulo}</span>
                        <span className="truncate text-xs text-muted-foreground">
                          {user.nome}
                        </span>
                      </span>
                    </span>
                    <ArrowRight data-icon="inline-end" />
                  </Button>
                )
              })
            )}
          </div>

          <p className="text-center text-xs text-muted-foreground">
            Acesso de demonstração sem senha; não representa autenticação real.
          </p>
        </div>
      </div>
    </div>
  )
}
