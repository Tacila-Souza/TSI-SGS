'use client'

import * as React from 'react'
import { BookOpen, Pencil, Plus, Search, Trash2, Users } from 'lucide-react'
import { toast } from 'sonner'
import { PageHeader } from '@/components/page-header'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Checkbox } from '@/components/ui/checkbox'
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog'
import { AlertDialog, AlertDialogAction, AlertDialogCancel, AlertDialogContent, AlertDialogDescription, AlertDialogFooter, AlertDialogHeader, AlertDialogTitle } from '@/components/ui/alert-dialog'
import { Empty, EmptyDescription, EmptyMedia, EmptyTitle } from '@/components/ui/empty'
import { Field, FieldGroup, FieldLabel } from '@/components/ui/field'
import { Input } from '@/components/ui/input'
import { Separator } from '@/components/ui/separator'
import { adminService, toApiId } from '@/lib/services'
import type { Curso, DisciplinaAdmin } from '@/lib/types-admin'
import type { User } from '@/lib/types'

function mensagemErro(erro: unknown): string {
  return erro instanceof Error ? erro.message : 'Erro inesperado.'
}

export default function CursosPage() {
  const [cursos, setCursos] = React.useState<Curso[]>([])
  const [professores, setProfessores] = React.useState<User[]>([])
  const [alunos, setAlunos] = React.useState<User[]>([])
  const [carregando, setCarregando] = React.useState(true)
  const [cursoSelecionado, setCursoSelecionado] = React.useState('')
  const [cursoParaExcluir, setCursoParaExcluir] = React.useState<Curso | null>(null)
  const [dialog, setDialog] = React.useState<'curso' | 'disciplina' | null>(null)
  const [editCurso, setEditCurso] = React.useState<Curso | null>(null)
  const [editDisc, setEditDisc] = React.useState<DisciplinaAdmin | null>(null)
  const [sigla, setSigla] = React.useState('')
  const [nome, setNome] = React.useState('')
  const [descricao, setDescricao] = React.useState('')
  const [codigo, setCodigo] = React.useState('')
  const [profSelecionados, setProfSelecionados] = React.useState<string[]>([])
  const [alunosSelecionados, setAlunosSelecionados] = React.useState<string[]>([])
  const [buscaProfessor, setBuscaProfessor] = React.useState('')
  const [buscaAluno, setBuscaAluno] = React.useState('')

  React.useEffect(() => {
    let ativo = true
    Promise.all([
      adminService.listarCursos(),
      adminService.listarProfessores(),
      adminService.listarAlunos(),
      adminService.listarDisciplinas(),
    ])
      .then(([listaCursos, listaProfessores, listaAlunos, listaDisciplinas]) => {
        if (!ativo) return
        const porCurso = new Map<string, DisciplinaAdmin[]>()
        for (const disciplina of listaDisciplinas) {
          const atual = porCurso.get(disciplina.cursoId) ?? []
          atual.push(disciplina)
          porCurso.set(disciplina.cursoId, atual)
        }
        setCursos(listaCursos.map((curso) => ({ ...curso, disciplinas: porCurso.get(curso.id) ?? [] })))
        setProfessores(listaProfessores)
        setAlunos(listaAlunos)
        setCursoSelecionado(listaCursos[0]?.id ?? '')
      })
      .catch((erro) => toast.error(mensagemErro(erro)))
      .finally(() => { if (ativo) setCarregando(false) })
    return () => { ativo = false }
  }, [])

  const professoresFiltrados = professores.filter((professor) => `${professor.nome} ${professor.registro}`.toLocaleLowerCase('pt-BR').includes(buscaProfessor.trim().toLocaleLowerCase('pt-BR')))
  const alunosFiltrados = alunos.filter((aluno) => {
    const anoMatricula = aluno.anoMatricula ?? (aluno.registro.match(/^\d{4}/)?.[0] ?? '')
    return `${aluno.nome} ${anoMatricula}`.toLocaleLowerCase('pt-BR').includes(buscaAluno.trim().toLocaleLowerCase('pt-BR'))
  })
  const curso = cursos.find((c) => c.id === cursoSelecionado) ?? cursos[0]

  function substituirDisciplina(disciplina: DisciplinaAdmin) {
    setCursos((prev) => prev.map((c) => c.id !== disciplina.cursoId ? c : {
      ...c,
      disciplinas: editDisc
        ? c.disciplinas.map((d) => d.id === disciplina.id ? disciplina : d)
        : [...c.disciplinas, disciplina],
    }))
  }

  async function excluirCurso() {
    if (!cursoParaExcluir) return
    try {
      await adminService.excluirCurso(cursoParaExcluir.id)
      const restante = cursos.filter((c) => c.id !== cursoParaExcluir.id)
      setCursos(restante)
      if (cursoSelecionado === cursoParaExcluir.id) setCursoSelecionado(restante[0]?.id ?? '')
      toast.success('Curso removido.')
    } catch (erro) { toast.error(mensagemErro(erro)) }
    setCursoParaExcluir(null)
  }

  function novoCurso() { setEditCurso(null); setSigla(''); setNome(''); setDescricao(''); setDialog('curso') }

  async function salvarCurso() {
    if (!sigla.trim() || !nome.trim()) return toast.error('Informe a sigla e o nome do curso.')
    const payload = { sigla: sigla.trim().toUpperCase(), nome: nome.trim(), descricao }
    try {
      const salvo = editCurso
        ? await adminService.editarCurso(editCurso.id, payload)
        : await adminService.cadastrarCurso(payload)
      setCursos((prev) => editCurso
        ? prev.map((c) => c.id === editCurso.id ? { ...salvo, disciplinas: c.disciplinas } : c)
        : [...prev, salvo])
      toast.success(editCurso ? 'Curso atualizado.' : 'Curso criado.')
      setDialog(null)
    } catch (erro) { toast.error(mensagemErro(erro)) }
  }

  function novaDisciplina() {
    setEditDisc(null); setNome(''); setCodigo(''); setProfSelecionados([])
    setAlunosSelecionados([]); setBuscaProfessor(''); setBuscaAluno(''); setDialog('disciplina')
  }

  async function salvarDisciplina() {
    if (!nome.trim() || !curso) return toast.error('Informe o nome da disciplina.')
    const payload = {
      cursoId: toApiId(curso.id),
      professorId: profSelecionados[0] ? toApiId(profSelecionados[0]) : null,
      nome: nome.trim(),
      codigo: codigo.trim() || '—',
      alunoIds: alunosSelecionados.map(toApiId),
    }
    try {
      const salva = editDisc
        ? await adminService.editarDisciplina(editDisc.id, payload)
        : await adminService.cadastrarDisciplina(payload)
      substituirDisciplina(salva)
      toast.success(editDisc ? 'Disciplina atualizada.' : 'Disciplina criada.')
      setDialog(null)
    } catch (erro) { toast.error(mensagemErro(erro)) }
  }

  function editarCurso(c: Curso) { setEditCurso(c); setSigla(c.sigla); setNome(c.nome); setDescricao(c.descricao); setDialog('curso') }

  function editarDisciplina(d: DisciplinaAdmin) {
    setEditDisc(d); setNome(d.nome); setCodigo(d.codigo)
    setProfSelecionados(d.professorId ? [d.professorId] : [])
    setAlunosSelecionados(d.alunoIds)
    setBuscaProfessor(''); setBuscaAluno(''); setDialog('disciplina')
  }

  async function excluirDisciplina(id: string) {
    try {
      await adminService.excluirDisciplina(id)
      setCursos((prev) => prev.map((c) => c.id === curso?.id ? { ...c, disciplinas: c.disciplinas.filter((d) => d.id !== id) } : c))
      toast.success('Disciplina removida.')
    } catch (erro) { toast.error(mensagemErro(erro)) }
  }

  function toggle(setter: React.Dispatch<React.SetStateAction<string[]>>, id: string) {
    setter((prev) => prev.includes(id) ? prev.filter((x) => x !== id) : [...prev, id])
  }
  function selecionarProfessor(id: string) { setProfSelecionados((prev) => prev[0] === id ? [] : [id]) }

  return <>
    <PageHeader title="Cursos e disciplinas" description="Organize a hierarquia acadêmica e mantenha os vínculos de professores e alunos."><Button onClick={novoCurso}><Plus data-icon="inline-start" />Novo curso</Button></PageHeader>
    <div className="grid gap-6 lg:grid-cols-[280px_1fr]">
      <Card><CardHeader><CardTitle className="text-base">Cursos</CardTitle><CardDescription>{cursos.length} cursos cadastrados</CardDescription></CardHeader><CardContent className="flex flex-col gap-2">
        {carregando ? <p className="text-sm text-muted-foreground">Carregando…</p> : cursos.length === 0 ? <Empty className="py-8"><EmptyMedia variant="icon"><BookOpen /></EmptyMedia><EmptyTitle>Nenhum curso cadastrado</EmptyTitle><EmptyDescription>Nenhum curso cadastrado no banco de dados.</EmptyDescription></Empty> : cursos.map((c) => <button key={c.id} onClick={() => setCursoSelecionado(c.id)} className={`flex flex-col gap-1 rounded-lg border p-3 text-left transition-colors ${c.id === cursoSelecionado ? 'border-primary bg-primary/5' : 'border-border hover:bg-muted/50'}`}><span className="font-medium">{c.nome}</span><Badge variant="outline">{c.sigla}</Badge><span className="text-xs text-muted-foreground">{c.disciplinas.length} disciplinas</span></button>)}
      </CardContent></Card>
      {curso && <Card><CardHeader className="flex-row items-start justify-between"><div><CardTitle>{curso.sigla} — {curso.nome}</CardTitle><CardDescription>{curso.descricao}</CardDescription></div><div className="flex flex-wrap gap-2"><Button variant="destructive" size="sm" onClick={() => setCursoParaExcluir(curso)}><Trash2 data-icon="inline-start" />Excluir curso</Button><Button variant="outline" size="sm" onClick={() => editarCurso(curso)}><Pencil data-icon="inline-start" />Editar curso</Button></div></CardHeader><CardContent id="disciplinas" className="flex flex-col gap-4"><div className="flex items-center justify-between"><div className="flex items-center gap-2 text-sm text-muted-foreground"><BookOpen />{curso.disciplinas.length} disciplinas vinculadas</div><Button size="sm" onClick={novaDisciplina}><Plus data-icon="inline-start" />Nova disciplina</Button></div><Separator />{curso.disciplinas.length === 0 ? <div className="rounded-lg border border-dashed p-8 text-center text-sm text-muted-foreground">Nenhuma disciplina cadastrada neste curso.</div> : <div className="grid gap-3 md:grid-cols-2">{curso.disciplinas.map((d) => <div key={d.id} className="rounded-xl border p-4"><div className="flex items-start justify-between gap-3"><div><Badge variant="outline">{d.codigo}</Badge><h3 className="mt-2 font-semibold">{d.nome}</h3></div><div className="flex gap-1"><Button variant="ghost" size="icon" onClick={() => editarDisciplina(d)} aria-label={`Editar ${d.nome}`}><Pencil /></Button><Button variant="ghost" size="icon" onClick={() => excluirDisciplina(d.id)} aria-label={`Excluir ${d.nome}`}><Trash2 /></Button></div></div><div className="mt-4 flex flex-wrap gap-2 text-xs text-muted-foreground"><Badge variant="secondary"><Users data-icon="inline-start" />{d.alunoIds.length} alunos</Badge><Badge variant="secondary">{d.professorId ? '1 professor(a)' : 'Sem professor(a)'}</Badge></div><p className="mt-3 text-xs text-muted-foreground">{d.professorNome || 'Sem professor(a) atribuído'}</p></div>)}</div>}</CardContent></Card>}
    </div>

    <AlertDialog open={cursoParaExcluir !== null} onOpenChange={(open) => !open && setCursoParaExcluir(null)}><AlertDialogContent><AlertDialogHeader><AlertDialogTitle>Excluir {cursoParaExcluir?.nome}?</AlertDialogTitle><AlertDialogDescription>Esta ação removerá o curso se ele não tiver disciplinas vinculadas.</AlertDialogDescription></AlertDialogHeader><AlertDialogFooter><AlertDialogCancel>Cancelar</AlertDialogCancel><AlertDialogAction onClick={excluirCurso} variant="destructive"><Trash2 data-icon="inline-start" />Excluir</AlertDialogAction></AlertDialogFooter></AlertDialogContent></AlertDialog>

    <Dialog open={dialog !== null} onOpenChange={(open) => !open && setDialog(null)}><DialogContent className={dialog === 'disciplina' ? 'max-h-[calc(100vh-2rem)] min-h-[min(680px,calc(100vh-3rem))] max-w-[calc(100%-2rem)] overflow-y-auto sm:max-w-4xl' : ''}><DialogHeader><DialogTitle>{dialog === 'curso' ? (editCurso ? 'Editar curso' : 'Novo curso') : (editDisc ? 'Editar disciplina' : 'Nova disciplina')}</DialogTitle><DialogDescription>O professor(a) responsável e os alunos podem ser atualizados a qualquer momento.</DialogDescription></DialogHeader>
      {dialog === 'curso' ? <FieldGroup><Field><FieldLabel>Sigla</FieldLabel><Input value={sigla} onChange={(e) => setSigla(e.target.value)} placeholder="Ex.: TSI" /></Field><Field><FieldLabel>Nome do curso</FieldLabel><Input value={nome} onChange={(e) => setNome(e.target.value)} placeholder="Ex.: Análise e Desenvolvimento de Sistemas" /></Field><Field><FieldLabel>Descrição</FieldLabel><Input value={descricao} onChange={(e) => setDescricao(e.target.value)} placeholder="Descrição breve" /></Field></FieldGroup>
        : <FieldGroup><div className="grid gap-3 sm:grid-cols-[1fr_160px]"><Field><FieldLabel>Nome da disciplina</FieldLabel><Input value={nome} onChange={(e) => setNome(e.target.value)} /></Field><Field><FieldLabel>Código</FieldLabel><Input value={codigo} onChange={(e) => setCodigo(e.target.value)} /></Field></div><div className="grid gap-4 md:grid-cols-2"><div className="flex flex-col gap-2"><FieldLabel>Professor(a)</FieldLabel><div className="relative"><Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" /><Input value={buscaProfessor} onChange={(event) => setBuscaProfessor(event.target.value)} placeholder="Nome ou registro" aria-label="Buscar professor por nome ou registro funcional" className="pl-9" /></div><div className="max-h-[48vh] overflow-auto rounded-lg border p-3">{professoresFiltrados.length ? professoresFiltrados.map((p) => <label key={p.id} className="flex cursor-pointer items-center gap-3 rounded-md py-2 text-sm hover:bg-muted/60"><Checkbox checked={profSelecionados.includes(p.id)} onCheckedChange={() => selecionarProfessor(p.id)} /><span className="flex min-w-0 flex-col"><span className="font-medium">{p.nome}</span><span className="text-xs text-muted-foreground">Registro funcional: {p.registro}</span></span></label>) : <p className="py-3 text-sm text-muted-foreground">Nenhum professor encontrado.</p>}</div></div><div className="flex flex-col gap-2"><FieldLabel>Alunos</FieldLabel><div className="relative"><Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" /><Input value={buscaAluno} onChange={(event) => setBuscaAluno(event.target.value)} placeholder="Nome ou ano de matrícula" aria-label="Buscar aluno por nome ou ano de matrícula" className="pl-9" /></div><div className="max-h-[48vh] overflow-auto rounded-lg border p-3">{alunosFiltrados.length ? alunosFiltrados.map((a) => <label key={a.id} className="flex cursor-pointer items-center gap-3 rounded-md py-2 text-sm hover:bg-muted/60"><Checkbox checked={alunosSelecionados.includes(a.id)} onCheckedChange={() => toggle(setAlunosSelecionados, a.id)} /><span className="flex min-w-0 flex-col"><span className="font-medium">{a.nome}</span><span className="text-xs text-muted-foreground">Matrícula: {a.registro}</span></span></label>) : <p className="py-3 text-sm text-muted-foreground">Nenhum aluno encontrado.</p>}</div></div></div></FieldGroup>}
      <DialogFooter><DialogClose render={<Button variant="outline">Cancelar</Button>} /><Button onClick={dialog === 'curso' ? salvarCurso : salvarDisciplina}>Salvar</Button></DialogFooter>
    </DialogContent></Dialog>
  </>
}
