import { AppShell } from '@/components/app-shell'

export default function AlunoLayout({
  children,
}: {
  children: React.ReactNode
}) {
  return <AppShell role="aluno">{children}</AppShell>
}
