'use client'

import * as React from 'react'
import { MoreHorizontal, Pencil, Plus, Search, Trash2, Users } from 'lucide-react'
import { toast } from 'sonner'
import { useApp } from '@/lib/app-context'
import type { Role, User } from '@/lib/types'
import { PageHeader } from '@/components/page-header'
import { Avatar, AvatarFallback } from '@/components/ui/avatar'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card } from '@/components/ui/card'
import { Checkbox } from '@/components/ui/checkbox'
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog'
import { DropdownMenu, DropdownMenuContent, DropdownMenuGroup, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu'
import { Field, FieldGroup, FieldLabel } from '@/components/ui/field'
import { Input } from '@/components/ui/input'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { Empty, EmptyDescription, EmptyMedia, EmptyTitle } from '@/components/ui/empty'

interface Props { role: Extract<Role, 'aluno' | 'professor'> }
const copy = {
  aluno: { title: 'Alunos', description: 'Cadastre e gerencie os alunos vinculados às disciplinas da instituição.', singular: 'aluno', registroLabel: 'Matrícula', addLabel: 'Novo aluno' },
  professor: { title: 'Professores', description: 'Cadastre e gerencie os professores e seus vínculos com as disciplinas.', singular: 'professor', registroLabel: 'Registro', addLabel: 'Novo professor' },
} as const
type FormState = { nome: string; email: string; registro: string; anoMatricula: string; semestreEntrada: '1' | '2' }
const emptyForm: FormState = { nome: '', email: '', registro: '', anoMatricula: '2026', semestreEntrada: '1' }

function iniciais(nome: string): string {
  const partes = nome.trim().split(/\s+/)
  return (partes[0]?.[0] ?? '') + (partes[partes.length - 1]?.[0] ?? '')
}

export function UserManager({ role }: Props) {
  const { usuarios, carregandoUsuarios, addUsuario, updateUsuario, removeUsuarios } = useApp()
  const [busca, setBusca] = React.useState('')
  const [dialog, setDialog] = React.useState<'novo' | 'editar' | null>(null)
  const [confirmDelete, setConfirmDelete] = React.useState(false)
  const [pendingDelete, setPendingDelete] = React.useState<User | null>(null)
  const [editing, setEditing] = React.useState<User | null>(null)
  const [selected, setSelected] = React.useState<string[]>([])
  const [form, setForm] = React.useState<FormState>(emptyForm)
  const c = copy[role]
  const usuariosDaFuncao = usuarios.filter((u) => u.role === role)
  const lista = usuariosDaFuncao.filter((u) => `${u.nome} ${u.email} ${u.registro}`.toLowerCase().includes(busca.toLowerCase()))
  const allSelected = lista.length > 0 && lista.every((u) => selected.includes(u.id))

  function openEdit(user: User) {
    setEditing(user)
    setForm({ nome: user.nome, email: user.email, registro: user.registro, anoMatricula: String(user.anoMatricula ?? 2026), semestreEntrada: user.semestreEntrada ?? '1' })
    setDialog('editar')
  }
  async function save() {
    if (!form.nome.trim() || !form.email.trim()) { toast.error('Preencha nome e e-mail.'); return }
    const patch = { nome: form.nome.trim(), email: form.email.trim(), registro: form.registro.trim() || '—', ...(role === 'aluno' ? { anoMatricula: Number(form.anoMatricula) || 2026, semestreEntrada: form.semestreEntrada } : {}) }
    try {
      if (dialog === 'editar' && editing) await updateUsuario(editing.id, patch)
      else await addUsuario({ ...patch, role, status: 'ativo', turmaIds: [] })
      toast.success(dialog === 'editar' ? 'Registro atualizado com sucesso.' : `${c.singular === 'aluno' ? 'Aluno' : 'Professor'} cadastrado com sucesso.`)
      setDialog(null); setEditing(null); setForm(emptyForm)
    } catch {
      // O contexto já exibiu o erro da API.
    }
  }
  function toggleAll() { setSelected(allSelected ? [] : lista.map((u) => u.id)) }
  function toggleOne(id: string) { setSelected((prev) => prev.includes(id) ? prev.filter((x) => x !== id) : [...prev, id]) }
  async function deleteSelected() {
    try {
      await removeUsuarios(selected)
      toast.success(`${selected.length} ${role === 'aluno' ? 'aluno(s)' : 'professor(es)'} excluído(s) com sucesso.`)
      setSelected([]); setConfirmDelete(false)
    } catch {
      // O contexto já exibiu o erro da API.
    }
  }
  async function deleteIndividual() {
    if (!pendingDelete) return
    try {
      await removeUsuarios([pendingDelete.id])
      toast.success(`${role === 'aluno' ? 'Aluno' : 'Professor'} excluído com sucesso.`)
      setPendingDelete(null)
    } catch {
      // O contexto já exibiu o erro da API.
    }
  }

  return <>
    <PageHeader title={c.title} description={c.description}>
      <Button onClick={() => { setForm(emptyForm); setDialog('novo') }}><Plus data-icon="inline-start" />{c.addLabel}</Button>
    </PageHeader>
    <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <div className="relative max-w-sm flex-1"><Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" /><Input value={busca} onChange={(e) => setBusca(e.target.value)} placeholder={`Buscar ${c.singular}…`} className="pl-9" /></div>
      {selected.length > 0 && <Button variant="destructive" onClick={() => setConfirmDelete(true)}><Trash2 data-icon="inline-start" />Excluir selecionados ({selected.length})</Button>}
    </div>
    <Card className="overflow-hidden py-0">
      {lista.length === 0 ? <Empty className="py-12"><EmptyMedia variant="icon">{usuariosDaFuncao.length === 0 ? <Users /> : <Search />}</EmptyMedia><EmptyTitle>{carregandoUsuarios ? 'Carregando…' : usuariosDaFuncao.length === 0 ? `Nenhum ${c.singular} cadastrado` : 'Nenhum resultado'}</EmptyTitle><EmptyDescription>{carregandoUsuarios ? 'Consultando a API…' : usuariosDaFuncao.length === 0 ? `Nenhum ${c.singular} cadastrado no banco de dados.` : `Nenhum ${c.singular} corresponde à busca atual.`}</EmptyDescription></Empty> : <div className="overflow-x-auto"><Table><TableHeader><TableRow>
        <TableHead className="w-14 pl-6"><Checkbox checked={allSelected} onCheckedChange={toggleAll} aria-label={`Selecionar todos os ${role === 'aluno' ? 'alunos' : 'professores'}`} /></TableHead>
        <TableHead className={role === 'aluno' ? '' : 'pl-6'}>Nome</TableHead><TableHead className="hidden md:table-cell">{c.registroLabel}</TableHead>{role === 'aluno' && <TableHead className="hidden lg:table-cell">Entrada</TableHead>}<TableHead className="w-24 pr-6 text-right">Ações</TableHead>
      </TableRow></TableHeader><TableBody>{lista.map((u) => <TableRow key={u.id}>
        <TableCell className="pl-6"><Checkbox checked={selected.includes(u.id)} onCheckedChange={() => toggleOne(u.id)} aria-label={`Selecionar ${u.nome}`} /></TableCell>
        <TableCell className={role === 'aluno' ? '' : 'pl-6'}><div className="flex items-center gap-3"><Avatar className="size-9"><AvatarFallback className="bg-primary/10 text-xs font-medium text-primary">{iniciais(u.nome)}</AvatarFallback></Avatar><div className="flex flex-col leading-tight"><span className="font-medium text-foreground">{u.nome}</span><span className="text-xs text-muted-foreground">{u.email}</span></div></div></TableCell>
        <TableCell className="hidden font-mono text-sm text-muted-foreground md:table-cell">{u.registro}</TableCell>
        {role === 'aluno' && <TableCell className="hidden lg:table-cell"><Badge variant="secondary" className="font-normal">{u.anoMatricula ?? 2026}.{u.semestreEntrada ?? '1'}</Badge></TableCell>}
        <TableCell className="pr-6 text-right"><div className="flex justify-end"><DropdownMenu><DropdownMenuTrigger render={<Button variant="ghost" size="icon" className="size-8"><MoreHorizontal /><span className="sr-only">Abrir ações</span></Button>} /><DropdownMenuContent align="end"><DropdownMenuGroup><DropdownMenuItem onClick={() => openEdit(u)}><Pencil />Editar</DropdownMenuItem><DropdownMenuItem className="text-destructive focus:text-destructive" onClick={() => setPendingDelete(u)}><Trash2 />Excluir {role === 'aluno' ? 'aluno' : 'professor'}</DropdownMenuItem></DropdownMenuGroup></DropdownMenuContent></DropdownMenu></div></TableCell>
      </TableRow>)}</TableBody></Table></div>}
    </Card>
    <Dialog open={dialog !== null} onOpenChange={(open) => !open && setDialog(null)}><DialogContent><DialogHeader><DialogTitle>{dialog === 'editar' ? `Editar ${c.singular}` : c.addLabel}</DialogTitle><DialogDescription>Atualize os dados cadastrais do registro.</DialogDescription></DialogHeader><FieldGroup><Field><FieldLabel htmlFor="nome">Nome completo</FieldLabel><Input id="nome" value={form.nome} onChange={(e) => setForm({ ...form, nome: e.target.value })} /></Field><Field><FieldLabel htmlFor="email">E-mail</FieldLabel><Input id="email" type="email" value={form.email} onChange={(e) => setForm({ ...form, email: e.target.value })} /></Field><Field><FieldLabel htmlFor="registro">{c.registroLabel}</FieldLabel><Input id="registro" value={form.registro} onChange={(e) => setForm({ ...form, registro: e.target.value })} /></Field>{role === 'aluno' && <div className="grid grid-cols-2 gap-3"><Field><FieldLabel htmlFor="ano">Ano de matrícula</FieldLabel><Input id="ano" type="number" min="2000" value={form.anoMatricula} onChange={(e) => setForm({ ...form, anoMatricula: e.target.value })} /></Field><Field><FieldLabel>Semestre de entrada</FieldLabel><Select value={form.semestreEntrada} onValueChange={(v) => v && setForm({ ...form, semestreEntrada: v as '1' | '2' })}><SelectTrigger className="w-full"><SelectValue /></SelectTrigger><SelectContent><SelectItem value="1">1º semestre</SelectItem><SelectItem value="2">2º semestre</SelectItem></SelectContent></Select></Field></div>}</FieldGroup><DialogFooter><DialogClose render={<Button variant="outline">Cancelar</Button>} /><Button onClick={save}>{dialog === 'editar' ? 'Salvar alterações' : 'Cadastrar'}</Button></DialogFooter></DialogContent></Dialog>
    <Dialog open={confirmDelete} onOpenChange={setConfirmDelete}><DialogContent><DialogHeader><DialogTitle>Excluir {role === 'aluno' ? 'alunos' : 'professores'} selecionados?</DialogTitle><DialogDescription>Esta ação removerá {selected.length} registro(s) da lista administrativa. Não será possível desfazê-la.</DialogDescription></DialogHeader><DialogFooter><DialogClose render={<Button variant="outline">Cancelar</Button>} /><Button variant="destructive" onClick={deleteSelected}><Trash2 data-icon="inline-start" />Confirmar exclusão</Button></DialogFooter></DialogContent></Dialog>
    <Dialog open={pendingDelete !== null} onOpenChange={(open) => !open && setPendingDelete(null)}><DialogContent><DialogHeader><DialogTitle>Excluir {role === 'aluno' ? 'aluno' : 'professor'}?</DialogTitle><DialogDescription>O {role === 'aluno' ? 'aluno' : 'professor'} {pendingDelete?.nome} será removido dos registros administrativos. Esta ação não pode ser desfeita.</DialogDescription></DialogHeader><DialogFooter><DialogClose render={<Button variant="outline">Cancelar</Button>} /><Button variant="destructive" onClick={deleteIndividual}><Trash2 data-icon="inline-start" />Excluir {role === 'aluno' ? 'aluno' : 'professor'}</Button></DialogFooter></DialogContent></Dialog>
  </>
}
