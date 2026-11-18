# Mapa da Arquitetura Front-end

> Documento baseado na estrutura atual do repositório. Atualizado em 17/11/2026.

## 1. Visão geral

A aplicação atualmente é um frontend **Next.js 16 com App Router, React e TypeScript**, organizado na raiz do projeto. **Não existe uma pasta `src/`**: as pastas `app/`, `components/`, `hooks/` e `lib/` ficam diretamente na raiz.

A estrutura atualmente disponível não corresponde a uma separação Frontend/Backend com uma API PHP: não foram encontrados serviços HTTP, `services/api`, rotas `app/api`, chamadas `fetch`/Axios para um backend ou código PHP. O acesso para testes usa três perfis fixos de demonstração — um administrador, um professor e um aluno — definidos em `lib/mock-data.ts`. Os dados da aplicação são mantidos em memória pelo `AppProvider` (`lib/app-context.tsx`); não há autenticação real nem persistência em servidor.

## 2. Árvore do frontend

```text
/
├── app/                                  # Rotas Next.js (App Router)
│   ├── admin/
│   │   ├── alunos/page.tsx
│   │   ├── cursos/page.tsx
│   │   ├── professores/page.tsx
│   │   ├── layout.tsx
│   │   └── page.tsx
│   ├── aluno/
│   │   ├── resultados/page.tsx
│   │   ├── seminarios/
│   │   │   ├── [id]/page.tsx
│   │   │   ├── [id]/ao-vivo/page.tsx
│   │   │   └── page.tsx
│   │   ├── layout.tsx
│   │   └── page.tsx
│   ├── professor/
│   │   ├── seminarios/
│   │   │   ├── [id]/page.tsx
│   │   │   ├── [id]/apresentar/page.tsx
│   │   │   ├── novo/page.tsx
│   │   │   └── page.tsx
│   │   ├── layout.tsx
│   │   └── page.tsx
│   ├── perfil/page.tsx
│   ├── globals.css
│   ├── layout.tsx
│   └── page.tsx                         # Entrada rápida nos três perfis de teste
├── components/
│   ├── admin/user-manager.tsx            # Gestão compartilhada de alunos/professores
│   ├── seminario/
│   │   ├── live-timer.tsx
│   │   ├── results-view.tsx
│   │   └── seminario-card.tsx
│   ├── app-shell.tsx
│   ├── page-header.tsx
│   ├── stat-card.tsx
│   ├── status-badge.tsx
│   └── ui/                               # Primitivos shadcn/ui utilizados
│       ├── avatar.tsx
│       ├── badge.tsx
│       ├── breadcrumb.tsx
│       ├── button.tsx
│       ├── card.tsx
│       ├── checkbox.tsx
│       ├── dialog.tsx
│       ├── dropdown-menu.tsx
│       ├── empty.tsx
│       ├── field.tsx
│       ├── input.tsx
│       ├── label.tsx
│       ├── progress.tsx
│       ├── select.tsx
│       ├── separator.tsx
│       ├── sheet.tsx
│       ├── sidebar.tsx
│       ├── skeleton.tsx
│       ├── slider.tsx
│       ├── sonner.tsx
│       ├── table.tsx
│       ├── tabs.tsx
│       ├── textarea.tsx
│       ├── toggle-group.tsx
│       ├── toggle.tsx
│       └── tooltip.tsx
├── hooks/
│   └── use-mobile.ts
├── lib/
│   ├── app-context.tsx                   # Estado global e operações da aplicação
│   ├── format.ts                         # Formatação de valores/datas
│   ├── mock-data.ts                      # Fixtures e três perfis fixos de teste
│   ├── types.ts                          # Tipos e helpers de domínio
│   └── utils.ts                          # Utilitários compartilhados
└── public/                               # Ícones, imagens e outros assets estáticos
```

Arquivos de configuração relevantes na raiz: `package.json`, `pnpm-lock.yaml`, `next.config.mjs`, `tsconfig.json`, `components.json`, `postcss.config.mjs` e `app/globals.css`.

## 3. Rotas e componentes de página

| URL | Componente principal | Responsabilidade |
|---|---|---|
| `/` | `app/page.tsx` — `LoginPage` | Acesso rápido como administrador, professor ou aluno de teste |
| `/admin` | `app/admin/page.tsx` — `AdminDashboard` | Visão geral administrativa |
| `/admin/alunos` | `app/admin/alunos/page.tsx` — `AlunosPage` | Gestão de alunos |
| `/admin/professores` | `app/admin/professores/page.tsx` — `ProfessoresPage` | Gestão de professores |
| `/admin/cursos` | `app/admin/cursos/page.tsx` — `CursosPage` | Gestão de cursos, disciplinas e vínculos |
| `/professor` | `app/professor/page.tsx` — `ProfessorDashboard` | Painel do professor |
| `/professor/seminarios` | `app/professor/seminarios/page.tsx` — `SeminariosPage` | Lista e filtros de seminários |
| `/professor/seminarios/novo` | `app/professor/seminarios/novo/page.tsx` — `NovoSeminarioPage` | Cadastro e edição de seminário |
| `/professor/seminarios/[id]` | `app/professor/seminarios/[id]/page.tsx` — `ProfessorSeminarioDetalhePage` | Sessões, grupos, chamada e critérios |
| `/professor/seminarios/[id]/apresentar` | `app/professor/seminarios/[id]/apresentar/page.tsx` — `ApresentarSeminarioPage` | Cronômetro e avaliação ao vivo |
| `/aluno` | `app/aluno/page.tsx` — `AlunoDashboardPage` | Painel do aluno |
| `/aluno/seminarios` | `app/aluno/seminarios/page.tsx` — `AlunoSeminariosPage` | Lista de seminários do aluno |
| `/aluno/seminarios/[id]` | `app/aluno/seminarios/[id]/page.tsx` — `AlunoSeminarioDetalhePage` | Detalhe, grupos e status ao vivo |
| `/aluno/seminarios/[id]/ao-vivo` | `app/aluno/seminarios/[id]/ao-vivo/page.tsx` — `AlunoAoVivoPage` | Acompanhamento de apresentação ao vivo |
| `/aluno/resultados` | `app/aluno/resultados/page.tsx` — `AlunoResultadosPage` | Resultados e avaliações |
| `/perfil` | `app/perfil/page.tsx` — `PerfilPage` | Dados do perfil atual |

Os layouts `app/admin/layout.tsx`, `app/professor/layout.tsx` e `app/aluno/layout.tsx` agrupam a navegação/estrutura visual de cada área. `app/layout.tsx` aplica o `AppProvider` e o `Toaster` global.

## 4. Componentes internos, entidades e estados de modais

Os diálogos de domínio estão atualmente declarados dentro das páginas/componentes consumidores; não há arquivos separados chamados, por exemplo, `ModalNovoAluno.tsx` ou `ModalEditarGrupo.tsx`.

| Entidade / tela | Componentes internos e diálogos | Estado controlador |
|---|---|---|
| Alunos (`/admin/alunos`) | `AlunosPage` usa `UserManager` com `role="aluno"`; tabela, formulário compartilhado de novo/editar, confirmação de exclusão individual e confirmação de exclusão em lote. | `dialog: 'novo' \| 'editar' \| null`; `editing`; `form`; `pendingDelete`; `confirmDelete`; `selected`. |
| Professores (`/admin/professores`) | `ProfessoresPage` usa o mesmo `UserManager` com `role="professor"`; tabela, formulário compartilhado de novo/editar, confirmação de exclusão individual e em lote. | Mesmos estados do `UserManager`: `dialog`, `editing`, `form`, `pendingDelete`, `confirmDelete` e `selected`. |
| Cursos e disciplinas (`/admin/cursos`) | `CursosPage`; diálogo alterna entre formulário de curso e formulário de disciplina. O formulário de disciplina permite selecionar professores e alunos. | `dialog: 'curso' \| 'disciplina' \| null`; `editCurso`; `editDisc`; campos `nome`, `descricao`, `codigo`; `profSelecionados`, `alunosSelecionados`, `buscaProfessor`, `buscaAluno`. |
| Seminário — detalhe e chamada (`/professor/seminarios/[id]`) | `ProfessorSeminarioDetalhePage`; diálogo de criar/editar grupo; alerta em diálogo quando a apresentação não pode começar; confirmação após salvar a chamada por toast global. A exclusão de grupo usa `window.confirm`. | `dialogGrupoAberto`; `grupoEmEdicao`; `showStartAlert`; `temaGrupo`; `buscaAluno`; `alunosSelecionados`. Dados da sessão e presença vêm de `useApp()`. |
| Seminário — cadastro (`/professor/seminarios/novo`) | `NovoSeminarioPage`; sem modal. Formulário em tela para criar/editar e adicionar/remover datas e critérios. | `curso`, `turmaId`, `titulo`, `descricao`, `datas`, `tempo`, `pesoProfessor`, `pesoPares`, `criterios`; `editInitialized` evita inicialização duplicada no modo edição. |
| Apresentação ao vivo (`/professor/seminarios/[id]/apresentar`) | `ApresentarSeminarioPage`; alerta de chamada obrigatória e diálogo de encerramento/conclusão da sessão. `LiveTimer` (`components/seminario/live-timer.tsx`) controla a interação do cronômetro. | `showAttendanceAlert`; `showFinish`; `dataSessao`; `notas`; `feedback`; `notaErro`; `avaliado`. |
| Aluno — listas/detalhes/ao vivo/resultados | Componentes de página e `SeminarioCard`/`ResultsView` conforme a tela; não há diálogos de domínio identificados nestas páginas. | Estado de seminário ao vivo e dados compartilhados vêm de `useApp()`; filtros locais ficam nas respectivas páginas. |

### Fluxo de confirmação da chamada

Na página `/professor/seminarios/[id]`, o botão **Salvar chamada** chama `salvarChamada(id, sessao.data)` do contexto e mostra uma notificação de sucesso via `toast.success`. O componente visual do toast é `components/ui/sonner.tsx`, montado globalmente em `app/layout.tsx`. O estado `chamadaRealizada` determina o rótulo “Chamada salva” e a liberação do início da apresentação.

## 5. Estado e dados da aplicação

- `lib/app-context.tsx` declara o contrato `AppContextValue`, o `AppProvider`, as operações de atualização e o hook `useApp()`.
- `lib/mock-data.ts` fornece fixtures acadêmicas e exatamente três perfis iniciais: um coordenador (Admin), um professor e um aluno. A tela de entrada apresenta um botão direto para cada perfil, sem lista de usuários ou alternância de identidade dentro do nível.
- `lib/types.ts` define os tipos de domínio; `lib/format.ts` reúne formatações.
- O estado é mantido em React (`useState` no contexto e nas páginas). No estado atual não há persistência em servidor ou integração HTTP documentada.

## 6. Requisições HTTP e backend PHP

**Localização atual:** não existe `services/api`, `services/`, `app/api/` ou cliente HTTP configurado identificado neste repositório. As páginas acessam os dados através de `useApp()`; as operações como `salvarChamada`, `setPresenca`, `addSeminario` e `addAvaliacao` atualizam o estado React local em `lib/app-context.tsx`.

Consequentemente, não há endpoint, base URL, método HTTP, payload ou resposta de backend PHP a mapear neste momento. O login é exclusivamente uma conveniência de demonstração para alternar entre os três perfis fixos; não protege rotas em produção e não valida credenciais. Quando a integração PHP for adicionada, recomenda-se concentrar o cliente e funções de domínio numa pasta explícita como `lib/services/` (ou `services/api/`), e fazer o contexto consumir esses serviços em vez de atualizar somente o estado local. Essa recomendação é futura, não descreve código já existente.
