# Mapa da Arquitetura Front-end

> Documento baseado na estrutura atual do repositório. Atualizado em 23/11/2026.
> Fonte de verdade dos formatos de dados: `docs/contrato_api_frontend.md`.

## 1. Visão geral

A aplicação é um **Next.js 16 com App Router, React e TypeScript**, sem pasta `src/`: `app/`, `components/`, `hooks/` e `lib/` ficam na raiz de `front-end/`.

O frontend consome a **API PHP** em `back-end/public/api/` por meio de `lib/services/`. O `AppProvider` (`lib/app-context.tsx`) continua sendo a fachada de estado: ele busca usuários e seminários na API e expõe operações para as telas. Não há autenticação real — a identidade vem do perfil selecionado na sessão (`useApp().professorId`), com fallback em `NEXT_PUBLIC_PROFESSOR_ID` antes da hidratação. `lib/mock-data.ts` é legado e não deve ser usado em código novo.

## 2. Árvore do frontend

```text
front-end/
├── app/                                  # Rotas Next.js (App Router)
│   ├── admin/                            # alunos, professores, cursos e dashboard
│   ├── aluno/                            # dashboard, seminários, ao vivo e resultados
│   ├── professor/                        # dashboard, seminários, formulário e apresentação
│   ├── perfil/page.tsx
│   ├── globals.css
│   ├── layout.tsx                        # AppProvider + Toaster
│   └── page.tsx                          # entrada nos perfis de teste
├── components/
│   ├── admin/user-manager.tsx
│   ├── seminario/
│   │   ├── live-timer.tsx
│   │   ├── resultado-view.tsx            # ResultadoDTO (atual)
│   │   └── seminario-card.tsx
│   ├── app-shell.tsx
│   └── ui/                               # primitivos shadcn/ui
├── lib/
│   ├── app-context.tsx                   # estado global e fachada da API
│   ├── format.ts
│   ├── mock-data.ts                      # legado, não é fonte de verdade
│   ├── services/
│   │   ├── admin.ts
│   │   ├── aluno.ts
│   │   ├── professor.ts
│   │   ├── api.ts                        # base URL, envelope e erros HTTP
│   │   ├── index.ts
│   │   └── mappers.ts                    # DTO (números) → tipos da UI (IDs prefixados)
│   ├── types.ts
│   ├── types-admin.ts
│   └── utils.ts
└── public/                               # assets estáticos
```

Arquivos gerados pelo Next.js e versionados: `next-env.d.ts`, `AGENTS.md` e `CLAUDE.md` (`@AGENTS.md`). Eles podem ser reescritos por `next dev`/`next build`; se o `git diff` mostrar apenas essa reescrita, restaure com `git checkout -- <arquivo>` em vez de commitar o churn.

## 3. Rotas e componentes de página

| URL | Componente principal | Responsabilidade |
|---|---|---|
| `/` | `app/page.tsx` — `LoginPage` | Entrada rápida nos perfis (admin fixo + alunos/professores da API) |
| `/admin` | `AdminDashboard` | Visão geral administrativa |
| `/admin/alunos` | `AlunosPage` | Gestão de alunos |
| `/admin/professores` | `ProfessoresPage` | Gestão de professores |
| `/admin/cursos` | `CursosPage` | Cursos, disciplinas e vínculos |
| `/professor` | `ProfessorDashboard` | Painel do professor |
| `/professor/seminarios` | `SeminariosPage` | Lista e filtros de seminários |
| `/professor/seminarios/novo` | `NovoSeminarioPage` | Cadastro e edição de seminário |
| `/professor/seminarios/[id]` | `ProfessorSeminarioDetalhePage` | Sessões, grupos, chamada e resultados |
| `/professor/seminarios/[id]/apresentar` | `ApresentarSeminarioPage` | Cronômetro e avaliação ao vivo |
| `/aluno` | `AlunoDashboardPage` | Painel do aluno |
| `/aluno/seminarios` | `AlunoSeminariosPage` | Lista de seminários do aluno |
| `/aluno/seminarios/[id]` | `AlunoSeminarioDetalhePage` | Detalhe, grupos e resultados |
| `/aluno/seminarios/[id]/ao-vivo` | `AlunoAoVivoPage` | Acompanhamento e avaliação por pares |
| `/aluno/resultados` | `AlunoResultadosPage` | Histórico de resultados |
| `/perfil` | `PerfilPage` | Dados do perfil e vínculos a partir dos seminários da API |

## 4. Estado e dados

- `AppProvider` carrega alunos/professores via `adminService` e seminários via `professorService.listarSeminarios()` para cada professor ativo.
- `recarregarSeminarios()` roda após criar/editar/excluir seminário ou sessão.
- Identidade do professor vem de `useApp().professorId`; `NEXT_PUBLIC_PROFESSOR_ID` só vale antes da hidratação.
- `LiveState` (cronômetro e grupo em apresentação) é somente do cliente.
- Notas oficiais vêm de `/api/professor/resultados/compilar.php` ou `/api/aluno/resultados/compilar.php`; o React não recalcula a fórmula.

## 5. Requisições HTTP

- `lib/services/api.ts` usa `NEXT_PUBLIC_API_BASE_URL` (modelo em `.env.example`) e interpreta o envelope `{ sucesso, dados, mensagem, erros }`.
- Services por perfil: `admin.ts`, `professor.ts`, `aluno.ts`. Nenhuma página deve chamar `fetch` diretamente.
- JSON em **camelCase**; query string em **snake_case** (`professor_id`, `aluno_id`, `data_id`, `grupo_id`, `disciplina_id`).
- IDs na API são numéricos; `mappers.ts` converte para strings prefixadas (`u-al-1`, `u-prof-1`, `t-1`, `s-1`, `g-1`).
- Índice endpoint → DTO → tela: `docs/contrato_api_frontend.md`, seção 9.4.

## 6. Verificação

```bash
pnpm typecheck
pnpm build
./scripts/smoke-api.sh   # na raiz do projeto, com API e MySQL no ar
```
