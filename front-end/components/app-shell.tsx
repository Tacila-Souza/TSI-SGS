
'use client'

import * as React from 'react'
import Link from 'next/link'
import { usePathname, useRouter } from 'next/navigation'
import {
  BookOpen,
  ChevronsUpDown,
  ClipboardList,
  GraduationCap,
  LayoutDashboard,
  LogOut,
  Presentation,
  Radio,
  UserCircle,
  Users,
  LibraryBig,
} from 'lucide-react'
import type { LucideIcon } from 'lucide-react'
import { useApp } from '@/lib/app-context'
import type { Role, User } from '@/lib/types'
import { cn } from '@/lib/utils'

import {
  Sidebar,
  SidebarContent,
  SidebarFooter,
  SidebarGroup,
  SidebarGroupContent,
  SidebarGroupLabel,
  SidebarHeader,
  SidebarInset,
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
  SidebarProvider,
  SidebarTrigger,
} from '@/components/ui/sidebar'

import {
  Avatar,
  AvatarFallback,
} from '@/components/ui/avatar'

import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'

import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuGroup,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'

import { Separator } from '@/components/ui/separator'

function iniciais(nome: string): string {
  const partes = nome.trim().split(/\s+/)

  return (
    (partes[0]?.[0] ?? '') +
    (partes[partes.length - 1]?.[0] ?? '')
  )
}

interface NavItem {
  label: string
  href: string
  icon: LucideIcon
  match?: (path: string) => boolean
}

const navByRole: Record<
  Role,
  {
    home: string
    label: string
    items: NavItem[]
  }
> = {
  coordenador: {
    home: '/admin',
    label: 'Admin',
    items: [
      {
        label: 'Painel',
        href: '/admin',
        icon: LayoutDashboard,
      },
      {
        label: 'Alunos',
        href: '/admin/alunos',
        icon: Users,
        match: (p) => p.startsWith('/admin/alunos'),
      },
      {
        label: 'Professores',
        href: '/admin/professores',
        icon: GraduationCap,
        match: (p) =>
          p.startsWith('/admin/professores'),
      },
      {
        label: 'Cursos e disciplinas',
        href: '/admin/cursos',
        icon: LibraryBig,
        match: (p) =>
          p.startsWith('/admin/cursos'),
      },
    ],
  },

  professor: {
    home: '/professor',
    label: 'Professor',
    items: [
      {
        label: 'Painel',
        href: '/professor',
        icon: LayoutDashboard,
      },
      {
        label: 'Seminários',
        href: '/professor/seminarios',
        icon: Presentation,
        match: (p) =>
          p.startsWith('/professor/seminarios'),
      },
    ],
  },

  aluno: {
    home: '/aluno',
    label: 'Aluno',
    items: [
      {
        label: 'Painel',
        href: '/aluno',
        icon: LayoutDashboard,
      },
      {
        label: 'Seminários',
        href: '/aluno/seminarios',
        icon: BookOpen,
        match: (p) =>
          p.startsWith('/aluno/seminarios'),
      },
      {
        label: 'Meus resultados',
        href: '/aluno/resultados',
        icon: ClipboardList,
      },
    ],
  },
}

const roleLabel: Record<Role, string> = {
  coordenador: 'Admin',
  professor: 'Professor(a)',
  aluno: 'Aluno(a)',
}

export function AppShell({
  role,
  children,
}: {
  role: Role
  children: React.ReactNode
}) {
  const {
    currentUser,
    carregandoPerfil,
    logout,
    live,
    usuarios,
    carregandoUsuarios,
    loginAs,
  } = useApp()

  const router = useRouter()
  const pathname = usePathname()

  React.useEffect(() => {
    if (carregandoPerfil) return

    if (!currentUser) {
      router.replace('/')
    } else if (currentUser.role !== role) {
      router.replace(
        navByRole[currentUser.role].home
      )
    }
  }, [
    carregandoPerfil,
    currentUser,
    role,
    router,
  ])

  if (carregandoPerfil) {
    return (
      <div className="flex min-h-svh items-center justify-center bg-background text-sm text-muted-foreground">
        Carregando…
      </div>
    )
  }

  if (!currentUser || currentUser.role !== role) {
    return (
      <div className="flex min-h-svh items-center justify-center bg-background text-sm text-muted-foreground">
        Redirecionando…
      </div>
    )
  }

  const config = navByRole[role]

  // Aceita tanto o ID da API ("1") quanto o ID do cliente ("s-1"), usado nos links.
  const idApiLive = live.seminarioId?.replace(/^s-/, '') ?? ''
  const liveActive = Boolean(
    idApiLive &&
      /^\d+$/.test(idApiLive) &&
      live.status === 'em_andamento'
  )

  const liveHref =
    role === 'professor'
      ? `/professor/seminarios/${live.seminarioId}/apresentar`
      : `/aluno/seminarios/${live.seminarioId}/ao-vivo`

  const professoresAtivos = usuarios.filter(
    (usuario) =>
      usuario.role === 'professor' &&
      usuario.status === 'ativo'
  )

  function handleLogout() {
    logout()
    router.replace('/')
  }

  function handleTrocarProfessor(professor: User) {
    loginAs(professor)
    window.location.assign('/professor')
  }

  function listaDeProfessores() {
    if (carregandoUsuarios) {
      return (
        <DropdownMenuItem disabled>
          Carregando professores...
        </DropdownMenuItem>
      )
    }

    if (professoresAtivos.length === 0) {
      return (
        <DropdownMenuItem disabled>
          Nenhum professor ativo encontrado
        </DropdownMenuItem>
      )
    }

    return professoresAtivos.map((professor) => (
      <DropdownMenuItem
        key={professor.id}
        disabled={
          currentUser?.id === professor.id &&
          currentUser?.nome === professor.nome
        }
        onClick={() =>
          handleTrocarProfessor(professor)
        }
      >
        <UserCircle />

        <span className="truncate">
          {professor.nome}
        </span>
      </DropdownMenuItem>
    ))
  }

  return (
    <SidebarProvider>
      <Sidebar>
        <SidebarHeader>
          <div className="flex items-center gap-2.5 px-2 py-1.5">
            <div className="flex size-9 shrink-0 items-center justify-center rounded-xl bg-primary text-primary-foreground">
              <Presentation className="size-5" />
            </div>

            <div className="flex flex-col leading-tight">
              <span className="font-display text-sm font-semibold text-foreground">
                The Seven
              </span>

              <span className="text-xs text-muted-foreground">
                {config.label}
              </span>
            </div>
          </div>
        </SidebarHeader>

        <SidebarContent>
          <SidebarGroup>
            <SidebarGroupLabel>
              Navegação
            </SidebarGroupLabel>

            <SidebarGroupContent>
              <SidebarMenu>
                {config.items.map((item) => {
                  const active = item.match
                    ? item.match(pathname)
                    : pathname === item.href

                  return (
                    <SidebarMenuItem key={item.href}>
                      <SidebarMenuButton
                        isActive={active}
                        render={
                          <Link href={item.href} />
                        }
                      >
                        <item.icon />
                        <span>{item.label}</span>
                      </SidebarMenuButton>
                    </SidebarMenuItem>
                  )
                })}
              </SidebarMenu>
            </SidebarGroupContent>
          </SidebarGroup>

          {liveActive && (
            <SidebarGroup>
              <SidebarGroupLabel>
                Ao vivo
              </SidebarGroupLabel>

              <SidebarGroupContent>
                <SidebarMenu>
                  <SidebarMenuItem>
                    <SidebarMenuButton
                      className={
                        role === 'aluno'
                          ? 'bg-emerald-500/10 text-emerald-700 hover:bg-emerald-500/15 dark:text-emerald-400'
                          : 'bg-amber-500/10 text-amber-700 hover:bg-amber-500/15 dark:text-amber-400'
                      }
                      render={
                        <Link href={liveHref} />
                      }
                    >
                      <Radio
                        className={
                          role === 'aluno'
                            ? 'animate-pulse text-emerald-600'
                            : 'animate-pulse text-amber-600'
                        }
                      />

                      <span>
                        Apresentação em curso
                      </span>
                    </SidebarMenuButton>
                  </SidebarMenuItem>
                </SidebarMenu>
              </SidebarGroupContent>
            </SidebarGroup>
          )}
        </SidebarContent>

        {/* PERFIL NO CANTO INFERIOR ESQUERDO */}
        <SidebarFooter>
          {role === 'professor' ||
          role === 'coordenador' ? (
            <DropdownMenu>
              <DropdownMenuTrigger
                render={
                  <button
                    type="button"
                    aria-label="Abrir menu para trocar professor"
                    className="flex w-full items-center gap-2 rounded-lg px-2 py-1.5 text-left hover:bg-accent focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                  >
                    <Avatar className="size-8">
                      <AvatarFallback className="bg-primary/10 text-xs font-medium text-primary">
                        {iniciais(currentUser.nome)}
                      </AvatarFallback>
                    </Avatar>

                    <div className="flex min-w-0 flex-1 flex-col leading-tight">
                      <span className="truncate text-sm font-medium text-foreground">
                        {currentUser.nome}
                      </span>

                      <span className="truncate text-xs text-muted-foreground">
                        {roleLabel[role]} · Trocar professor
                      </span>
                    </div>

                    <ChevronsUpDown className="size-4 shrink-0 text-muted-foreground" />
                  </button>
                }
              />

              <DropdownMenuContent
                side="top"
                align="start"
                className="w-72"
              >
                <div className="px-2 py-1.5 text-sm font-semibold">
                  {role === 'coordenador'
                    ? 'Entrar como professor'
                    : 'Trocar professor'}
                </div>

                <DropdownMenuSeparator />

                {listaDeProfessores()}
              </DropdownMenuContent>
            </DropdownMenu>
          ) : (
            <div className="flex items-center gap-2 rounded-lg px-2 py-1.5">
              <Avatar className="size-8">
                <AvatarFallback className="bg-primary/10 text-xs font-medium text-primary">
                  {iniciais(currentUser.nome)}
                </AvatarFallback>
              </Avatar>

              <div className="flex min-w-0 flex-col leading-tight">
                <span className="truncate text-sm font-medium text-foreground">
                  {currentUser.nome}
                </span>

                <span className="truncate text-xs text-muted-foreground">
                  {roleLabel[role]}
                </span>
              </div>
            </div>
          )}
        </SidebarFooter>
      </Sidebar>

      <SidebarInset>
        <header className="sticky top-0 z-10 flex h-14 items-center gap-2 border-b border-border bg-background/80 px-4 backdrop-blur">
          <SidebarTrigger />

          <Separator
            orientation="vertical"
            className="mr-1 h-5"
          />

          <span className="font-display text-sm font-medium text-foreground">
            {config.label}
          </span>

          <Badge
            variant="outline"
            className="ml-1 border-dashed text-[10px] font-normal text-muted-foreground"
          >
            Ambiente de testes
          </Badge>

          <div className="ml-auto flex items-center gap-2">
            {liveActive && (
              <Button
                variant="outline"
                size="sm"
                nativeButton={false}
                className={
                  role === 'aluno'
                    ? 'border border-emerald-300 bg-emerald-100 text-emerald-700 hover:bg-emerald-200 dark:border-emerald-500/40 dark:bg-emerald-500/20 dark:text-emerald-300'
                    : 'border border-orange-300 bg-orange-100 text-orange-700 hover:bg-orange-200 dark:border-amber-500/40 dark:bg-amber-500/20 dark:text-amber-300'
                }
                render={<Link href={liveHref} />}
              >
                <span
                  aria-hidden="true"
                  className="relative mr-1 inline-flex size-2"
                >
                  <span
                    className={cn(
                      'absolute inline-flex size-full animate-ping rounded-full',
                      role === 'aluno'
                        ? 'bg-emerald-400 opacity-75'
                        : 'bg-amber-400 opacity-75'
                    )}
                  />

                  <span
                    className={cn(
                      'relative inline-flex size-2 rounded-full',
                      role === 'aluno'
                        ? 'bg-emerald-500'
                        : 'bg-amber-500'
                    )}
                  />
                </span>

                <Radio
                  data-icon="inline-start"
                  className="animate-pulse"
                />

                Apresentação em curso
              </Button>
            )}

            {/* MENU SUPERIOR DIREITO */}
            <DropdownMenu>
              <DropdownMenuTrigger
                render={
                  <button
                    type="button"
                    className="flex items-center gap-2 rounded-full outline-none focus-visible:ring-3 focus-visible:ring-ring/50"
                  >
                    <Avatar className="size-8">
                      <AvatarFallback className="bg-primary/10 text-xs font-medium text-primary">
                        {iniciais(currentUser.nome)}
                      </AvatarFallback>
                    </Avatar>
                  </button>
                }
              />

              <DropdownMenuContent
                align="end"
                className="w-56"
              >
                {/* Corrigido: sem DropdownMenuLabel */}
                <div className="px-2 py-1.5">
                  <div className="flex flex-col">
                    <span className="text-sm font-medium">
                      {currentUser.nome}
                    </span>

                    <span className="text-xs font-normal text-muted-foreground">
                      {currentUser.email}
                    </span>
                  </div>
                </div>

                <DropdownMenuSeparator />

                {role === 'professor' && (
                  <>
                    <div className="px-2 py-1.5 text-sm font-semibold">
                      Trocar professor
                    </div>

                    {listaDeProfessores()}

                    <DropdownMenuSeparator />
                  </>
                )}

                <DropdownMenuGroup>
                  <DropdownMenuItem
                    render={
                      <Link href="/perfil" />
                    }
                  >
                    <UserCircle />
                    Meu perfil
                  </DropdownMenuItem>

                  <DropdownMenuItem
                    onClick={handleLogout}
                  >
                    <LogOut />
                    Sair
                  </DropdownMenuItem>
                </DropdownMenuGroup>
              </DropdownMenuContent>
            </DropdownMenu>
          </div>
        </header>

        <main className="flex flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8">
          {children}
        </main>
      </SidebarInset>
    </SidebarProvider>
  )
}
