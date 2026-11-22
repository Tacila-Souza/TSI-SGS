
'use client'

import * as React from 'react'
import { useRouter, useSearchParams } from 'next/navigation'
import { CalendarPlus, Plus, Trash2 } from 'lucide-react'
import { toast } from 'sonner'
import { useApp } from '@/lib/app-context'
import { professorService, toApiId } from '@/lib/services'
import type { Curso, DisciplinaAdmin } from '@/lib/types-admin'
import type { CriterioTipo, Seminario } from '@/lib/types'
import { PageHeader } from '@/components/page-header'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import {
  Card,
  CardContent,
  CardHeader,
  CardTitle,
} from '@/components/ui/card'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { Progress } from '@/components/ui/progress'
import { Separator } from '@/components/ui/separator'

type DataForm = {
  key: string
  id?: number
  data: string
  horario: string
}

type CriterioForm = {
  key: string
  id?: number
  nome: string
  peso: number
  tipo: CriterioTipo
}

function mensagemErro(erro: unknown): string {
  return erro instanceof Error
    ? erro.message
    : 'Erro inesperado.'
}

export default function NovoSeminarioPage() {
  const router = useRouter()
  const searchParams = useSearchParams()
  const editId = searchParams.get('edit')
  const { currentUser } = useApp()

  const professorId = React.useMemo(() => {
    const configurado = process.env.NEXT_PUBLIC_PROFESSOR_ID

    if (configurado) {
      return Number(configurado)
    }

    if (currentUser?.id) {
      try {
        return toApiId(currentUser.id)
      } catch {
        return 1
      }
    }

    return 1
  }, [currentUser?.id])

  const [cursos, setCursos] = React.useState<Curso[]>([])
  const [todasDisciplinas, setTodasDisciplinas] =
    React.useState<DisciplinaAdmin[]>([])

  const [carregando, setCarregando] = React.useState(true)
  const [cursoId, setCursoId] = React.useState('')
  const [turmaId, setTurmaId] = React.useState('')
  const [titulo, setTitulo] = React.useState('')
  const [descricao, setDescricao] = React.useState('')

  const [datas, setDatas] = React.useState<DataForm[]>([
    {
      key: 'data-new-1',
      data: '',
      horario: '19:00',
    },
  ])

  const [tempo, setTempo] = React.useState('15')
  const [pesoProfessor, setPesoProfessor] = React.useState('60')
  const [pesoPares, setPesoPares] = React.useState('40')

  const [criterios, setCriterios] = React.useState<CriterioForm[]>([
    {
      key: 'criterio-new-1',
      nome: 'Domínio do conteúdo',
      peso: 50,
      tipo: 'grupo',
    },
    {
      key: 'criterio-new-2',
      nome: 'Clareza e didática',
      peso: 50,
      tipo: 'individual',
    },
  ])

  const editInitialized = React.useRef<string | null>(null)
  const editTurmaId = React.useRef<string | null>(null)

  const disciplinas = React.useMemo(
    () =>
      todasDisciplinas.filter(
        (item) => item.cursoId === cursoId
      ),
    [todasDisciplinas, cursoId]
  )

  // Identifica o nome do curso selecionado.
  const cursoSelecionado = React.useMemo(
    () => cursos.find((curso) => curso.id === cursoId),
    [cursos, cursoId]
  )

  // Identifica o nome da disciplina selecionada.
  const disciplinaSelecionada = React.useMemo(
    () =>
      disciplinas.find(
        (disciplina) => disciplina.id === turmaId
      ),
    [disciplinas, turmaId]
  )

  // Carrega os cursos e disciplinas cadastrados.
  React.useEffect(() => {
    let ativo = true

    setCarregando(true)

    Promise.all([
      professorService.listarCursos(professorId),
      professorService.listarDisciplinas(professorId),
    ])
      .then(([listaCursos, listaDisciplinas]) => {
        if (!ativo) return

        setCursos(listaCursos)
        setTodasDisciplinas(listaDisciplinas)
      })
      .catch((erro) => {
        if (ativo) {
          toast.error(mensagemErro(erro))
        }
      })
      .finally(() => {
        if (ativo) setCarregando(false)
      })

    return () => {
      ativo = false
    }
  }, [professorId])

  // Carrega o seminário quando estiver editando.
  React.useEffect(() => {
    if (!editId || editInitialized.current === editId) {
      return
    }

    let ativo = true

    professorService
      .detalhesSeminario(professorId, editId)
      .then((seminario: Seminario) => {
        if (!ativo) return

        editInitialized.current = editId
        editTurmaId.current = seminario.turmaId

        setTitulo(seminario.titulo)
        setDescricao(seminario.descricao)
        setTurmaId(seminario.turmaId)

        setTempo(
          String(seminario.tempoApresentacaoMin)
        )

        setPesoProfessor(
          String(
            Math.round(seminario.pesoProfessor * 100)
          )
        )

        setPesoPares(
          String(
            Math.round(
              (1 - seminario.pesoProfessor) * 100
            )
          )
        )

        setDatas(
          (seminario.sessoes ?? []).map(
            (sessao, index) => ({
              key: `data-edit-${sessao.id}-${index}`,
              id:
                sessao.id == null
                  ? undefined
                  : toApiId(sessao.id),
              data: sessao.data,
              horario: sessao.horario ?? '19:00',
            })
          )
        )

        setCriterios(
          seminario.criterios.map((criterio) => ({
            key: `criterio-edit-${criterio.id}`,
            id: toApiId(criterio.id),
            nome: criterio.nome,
            peso: criterio.peso,
            tipo: criterio.tipo,
          }))
        )
      })
      .catch((erro) => {
        if (ativo) {
          toast.error(mensagemErro(erro))
        }
      })

    return () => {
      ativo = false
    }
  }, [editId, professorId])

  React.useEffect(() => {
    if (
      !editTurmaId.current ||
      todasDisciplinas.length === 0
    ) {
      return
    }

    const disciplina = todasDisciplinas.find(
      (item) => item.id === editTurmaId.current
    )

    if (disciplina) {
      setCursoId(disciplina.cursoId)
      setTurmaId(disciplina.id)
    }

    editTurmaId.current = null
  }, [todasDisciplinas])

  const totalPeso = criterios.reduce(
    (sum, item) =>
      sum + (Number(item.peso) || 0),
    0
  )

  const totalComposicao =
    Number(pesoProfessor) + Number(pesoPares)

  function atualizarPesoComposicao(
    tipo: 'professor' | 'pares',
    valor: string
  ) {
    const peso = Math.max(
      0,
      Math.min(100, Number(valor))
    )

    if (tipo === 'professor') {
      setPesoProfessor(
        valor === '' ? '' : String(peso)
      )

      if (
        valor !== '' &&
        Number.isFinite(peso)
      ) {
        setPesoPares(String(100 - peso))
      }
    } else {
      setPesoPares(
        valor === '' ? '' : String(peso)
      )

      if (
        valor !== '' &&
        Number.isFinite(peso)
      ) {
        setPesoProfessor(String(100 - peso))
      }
    }
  }

  function atualizarCriterio(
    key: string,
    patch: Partial<CriterioForm>
  ) {
    setCriterios((items) =>
      items.map((item) =>
        item.key === key
          ? { ...item, ...patch }
          : item
      )
    )
  }

  async function salvar() {
    const datasValidas = datas
      .filter((item) => item.data)
      .filter(
        (item, index, all) =>
          all.findIndex(
            (candidate) =>
              candidate.data === item.data
          ) === index
      )
      .sort((a, b) =>
        a.data.localeCompare(b.data)
      )

    if (
      !titulo.trim() ||
      !turmaId ||
      datasValidas.length < 1 ||
      totalPeso !== 100 ||
      totalComposicao !== 100 ||
      criterios.some(
        (item) => !item.nome.trim()
      )
    ) {
      toast.error(
        'Informe título, curso, disciplina, pelo menos uma data válida e pesos e critérios totalizando 100%.'
      )
      return
    }

    const payload = {
      turmaId: toApiId(turmaId),
      titulo: titulo.trim(),
      descricao: descricao.trim(),
      tempoApresentacaoMin:
        Number(tempo) || 15,
      pesoProfessor:
        Number(pesoProfessor) / 100,

      datas: datasValidas.map((item) => ({
        ...(item.id ? { id: item.id } : {}),
        data: item.data,
        horario: item.horario,
      })),

      criterios: criterios.map((item) => ({
        ...(item.id ? { id: item.id } : {}),
        nome: item.nome.trim(),
        peso: Number(item.peso),
        tipo: item.tipo,
      })),
    }

    try {
      if (editId) {
        await professorService.editarSeminario(
          professorId,
          editId,
          payload
        )

        toast.success(
          'Seminário atualizado com sucesso.'
        )

        router.push(
          `/professor/seminarios/${editId}`
        )
      } else {
        const salvo =
          await professorService.cadastrarSeminario(
            professorId,
            payload
          )

        toast.success(
          'Seminário criado e agendado com sucesso.'
        )

        router.push(
          `/professor/seminarios/${salvo.id}`
        )
      }
    } catch (erro) {
      toast.error(mensagemErro(erro))
    }
  }

  return (
    <div className="flex flex-col gap-6">
      <PageHeader
        title={
          editId
            ? 'Editar seminário'
            : 'Novo seminário'
        }
        description={
          editId
            ? 'Atualize os dados e salve as alterações do seminário.'
            : 'Configure o curso, a disciplina, as datas e os critérios de avaliação.'
        }
      />

      <div className="grid gap-6 xl:grid-cols-[minmax(0,1fr)_360px]">
        <div className="flex flex-col gap-6">
          <Card>
            <CardHeader>
              <CardTitle>
                Informações principais
              </CardTitle>
            </CardHeader>

            <CardContent className="grid gap-4 md:grid-cols-2">
              <div className="flex flex-col gap-2 md:col-span-2">
                <Label htmlFor="titulo">
                  Título
                </Label>

                <Input
                  id="titulo"
                  value={titulo}
                  onChange={(event) =>
                    setTitulo(event.target.value)
                  }
                  placeholder="Ex.: Seminário de Arquitetura"
                />
              </div>

              <div className="flex flex-col gap-2 md:col-span-2">
                <Label htmlFor="descricao">
                  Descrição
                </Label>

                <Textarea
                  id="descricao"
                  value={descricao}
                  onChange={(event) =>
                    setDescricao(event.target.value)
                  }
                  placeholder="Descreva o objetivo da atividade"
                />
              </div>

              {/* CURSO */}
              <div className="flex flex-col gap-2">
                <Label>Curso</Label>

                <Select
                  value={cursoId}
                  onValueChange={(value) => {
                    setCursoId(value ?? '')
                    setTurmaId('')
                  }}
                >
                  <SelectTrigger>
                    <SelectValue
                      placeholder={
                        carregando
                          ? 'Carregando cursos…'
                          : 'Selecione o curso'
                      }
                    >
                      {cursoSelecionado?.nome}
                    </SelectValue>
                  </SelectTrigger>

                  <SelectContent>
                    {cursos.map((curso) => (
                      <SelectItem
                        key={curso.id}
                        value={curso.id}
                      >
                        {curso.nome}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>

              {/* DISCIPLINA */}
              <div className="flex flex-col gap-2">
                <Label>Disciplina</Label>

                <Select
                  value={turmaId}
                  onValueChange={(value) =>
                    setTurmaId(value ?? '')
                  }
                >
                  <SelectTrigger
                    disabled={
                      !cursoId ||
                      disciplinas.length === 0
                    }
                  >
                    <SelectValue
                      placeholder={
                        !cursoId
                          ? 'Selecione o curso primeiro'
                          : 'Selecione a disciplina'
                      }
                    >
                      {disciplinaSelecionada?.nome}
                    </SelectValue>
                  </SelectTrigger>

                  <SelectContent>
                    {disciplinas.map(
                      (disciplina) => (
                        <SelectItem
                          key={disciplina.id}
                          value={disciplina.id}
                        >
                          {disciplina.nome}
                          {disciplina.codigo
                            ? ` (${disciplina.codigo})`
                            : ''}
                        </SelectItem>
                      )
                    )}
                  </SelectContent>
                </Select>
              </div>

              <div className="flex flex-col gap-2">
                <Label htmlFor="tempo">
                  Tempo por apresentação (minutos)
                </Label>

                <Input
                  id="tempo"
                  type="number"
                  min="5"
                  value={tempo}
                  onChange={(event) =>
                    setTempo(event.target.value)
                  }
                />
              </div>
            </CardContent>
          </Card>

          {/* DATAS */}
          <Card>
            <CardHeader>
              <CardTitle>
                Datas de apresentação
              </CardTitle>
            </CardHeader>

            <CardContent className="flex flex-col gap-3">
              <p className="text-sm text-muted-foreground">
                Adicione pelo menos uma data; cada sessão pode ter seu próprio horário.
              </p>

              {datas.map((sessao, index) => (
                <div
                  key={sessao.key}
                  className="grid grid-cols-[1fr_130px_auto] items-end gap-2"
                >
                  <div className="flex flex-col gap-2">
                    <Label htmlFor={`data-${index}`}>
                      Data {index + 1}
                    </Label>

                    <Input
                      id={`data-${index}`}
                      type="date"
                      value={sessao.data}
                      onChange={(event) =>
                        setDatas((items) =>
                          items.map((item, i) =>
                            i === index
                              ? {
                                  ...item,
                                  data: event.target.value,
                                }
                              : item
                          )
                        )
                      }
                    />
                  </div>

                  <div className="flex flex-col gap-2">
                    <Label htmlFor={`horario-${index}`}>
                      Horário
                    </Label>

                    <Input
                      id={`horario-${index}`}
                      type="time"
                      value={sessao.horario}
                      onChange={(event) =>
                        setDatas((items) =>
                          items.map((item, i) =>
                            i === index
                              ? {
                                  ...item,
                                  horario: event.target.value,
                                }
                              : item
                          )
                        )
                      }
                    />
                  </div>

                  {datas.length > 1 && (
                    <Button
                      type="button"
                      variant="ghost"
                      size="icon"
                      aria-label={`Remover data ${index + 1}`}
                      onClick={() =>
                        setDatas((items) =>
                          items.length > 1
                            ? items.filter(
                                (_, i) => i !== index
                              )
                            : items
                        )
                      }
                    >
                      <Trash2 />
                    </Button>
                  )}
                </div>
              ))}

              <Button
                type="button"
                variant="outline"
                className="self-start"
                onClick={() =>
                  setDatas((items) => [
                    ...items,
                    {
                      key: `data-new-${Date.now()}`,
                      data: '',
                      horario: '19:00',
                    },
                  ])
                }
              >
                <Plus data-icon="inline-start" />
                Adicionar mais uma data
              </Button>
            </CardContent>
          </Card>

          {/* COMPOSIÇÃO DAS NOTAS */}
          <Card>
            <CardHeader>
              <CardTitle>
                Composição e Cálculo da Nota Final
              </CardTitle>
            </CardHeader>

            <CardContent className="grid gap-4 sm:grid-cols-2">
              <div className="flex flex-col gap-2">
                <Label htmlFor="peso-professor">
                  Avaliação do Professor (%)
                </Label>

                <Input
                  id="peso-professor"
                  type="number"
                  min="0"
                  max="100"
                  value={pesoProfessor}
                  onChange={(event) =>
                    atualizarPesoComposicao(
                      'professor',
                      event.target.value
                    )
                  }
                  aria-invalid={
                    totalComposicao !== 100
                  }
                />

                <p className="text-xs text-muted-foreground">
                  Peso sugerido para a avaliação do professor.
                </p>
              </div>

              <div className="flex flex-col gap-2">
                <Label htmlFor="peso-pares">
                  Avaliação dos Alunos (Pares) (%)
                </Label>

                <Input
                  id="peso-pares"
                  type="number"
                  min="0"
                  max="100"
                  value={pesoPares}
                  onChange={(event) =>
                    atualizarPesoComposicao(
                      'pares',
                      event.target.value
                    )
                  }
                  aria-invalid={
                    totalComposicao !== 100
                  }
                />

                <p className="text-xs text-muted-foreground">
                  A soma dos pesos é ajustada automaticamente para 100%.
                </p>
              </div>

              <div className="sm:col-span-2">
                <div className="flex justify-between text-sm font-medium">
                  <span>
                    Total da composição
                  </span>
                  <span aria-live="polite">
                    {totalComposicao}% / 100%
                  </span>
                </div>

                <Progress
                  value={Math.min(
                    totalComposicao,
                    100
                  )}
                />
              </div>
            </CardContent>
          </Card>

          {/* CRITÉRIOS */}
          <Card>
            <CardHeader>
              <div className="flex items-center justify-between gap-3">
                <CardTitle>
                  Critérios de avaliação
                </CardTitle>

                <Button
                  type="button"
                  variant="outline"
                  size="sm"
                  onClick={() =>
                    setCriterios((items) => [
                      ...items,
                      {
                        key: `criterio-new-${Date.now()}`,
                        nome: '',
                        peso: 0,
                        tipo: 'grupo',
                      },
                    ])
                  }
                >
                  <Plus data-icon="inline-start" />
                  Adicionar critério
                </Button>
              </div>
            </CardHeader>

            <CardContent className="flex flex-col gap-4">
              <div className="flex flex-col gap-2">
                <div className="flex justify-between text-sm font-medium">
                  <span>
                    Total dos pesos
                  </span>
                  <span>
                    {totalPeso}% / 100%
                  </span>
                </div>

                <Progress
                  value={Math.min(
                    totalPeso,
                    100
                  )}
                />
              </div>

              <Separator />

              {criterios.map(
                (criterio, index) => (
                  <div
                    key={criterio.key}
                    className="grid gap-3 rounded-lg border p-4 md:grid-cols-[1fr_140px_150px_auto]"
                  >
                    <div className="flex flex-col gap-2">
                      <Label
                        htmlFor={`criterio-${criterio.key}`}
                      >
                        Nome do critério
                      </Label>

                      <Input
                        id={`criterio-${criterio.key}`}
                        value={criterio.nome}
                        onChange={(event) =>
                          atualizarCriterio(
                            criterio.key,
                            {
                              nome:
                                event.target.value,
                            }
                          )
                        }
                        placeholder={`Critério ${index + 1}`}
                      />
                    </div>

                    <div className="flex flex-col gap-2">
                      <Label
                        htmlFor={`peso-${criterio.key}`}
                      >
                        Peso (%)
                      </Label>

                      <Input
                        id={`peso-${criterio.key}`}
                        type="number"
                        min="0"
                        max="100"
                        value={criterio.peso}
                        onChange={(event) =>
                          atualizarCriterio(
                            criterio.key,
                            {
                              peso: Math.max(
                                0,
                                Math.min(
                                  100,
                                  Number(
                                    event.target.value
                                  )
                                )
                              ),
                            }
                          )
                        }
                        aria-invalid={
                          totalPeso > 100
                        }
                      />
                    </div>

                    <div className="flex flex-col gap-2">
                      <Label>Tipo</Label>

                      <Select
                        value={criterio.tipo}
                        onValueChange={(value) =>
                          atualizarCriterio(
                            criterio.key,
                            {
                              tipo: (
                                value ?? 'grupo'
                              ) as CriterioTipo,
                            }
                          )
                        }
                      >
                        <SelectTrigger>
                          <SelectValue />
                        </SelectTrigger>

                        <SelectContent>
                          <SelectItem value="grupo">
                            Em grupo
                          </SelectItem>
                          <SelectItem value="individual">
                            Individual
                          </SelectItem>
                        </SelectContent>
                      </Select>
                    </div>

                    <Button
                      type="button"
                      variant="ghost"
                      size="icon"
                      className="self-end"
                      aria-label="Remover critério"
                      onClick={() =>
                        setCriterios((items) =>
                          items.filter(
                            (item) =>
                              item.key !==
                              criterio.key
                          )
                        )
                      }
                    >
                      <Trash2 />
                    </Button>
                  </div>
                )
              )}
            </CardContent>
          </Card>
        </div>

        {/* PRÓXIMOS PASSOS */}
        <Card className="h-fit">
          <CardHeader>
            <CardTitle>
              Próximos passos
            </CardTitle>
          </CardHeader>

          <CardContent className="flex flex-col gap-4">
            <p className="text-sm text-muted-foreground">
              Os alunos matriculados serão carregados
              automaticamente. Depois de criar, você
              poderá organizar os participantes nos
              grupos de cada data e registrar a chamada.
            </p>

            <Button
              onClick={salvar}
              disabled={
                totalPeso > 100 ||
                totalComposicao !== 100
              }
            >
              <CalendarPlus data-icon="inline-start" />

              {editId
                ? 'Salvar alterações'
                : 'Criar e agendar seminário'}
            </Button>
          </CardContent>
        </Card>
      </div>
    </div>
  )
}
