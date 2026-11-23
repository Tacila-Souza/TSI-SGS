# SGS — Guia de Contexto do Projeto

> **Comece por aqui.** Este é o ponto de entrada para qualquer pessoa (ou agente de IA) que vá trabalhar no SGS. Ele resume o projeto, a ordem de leitura, as decisões já tomadas e as regras que não podem ser quebradas.

## 1. O que é o SGS

O SGS é um sistema acadêmico de gestão de seminários. O sistema atende três perfis:

- **Coordenação (admin):** gerencia alunos, professores, cursos e disciplinas.
- **Professor:** cria e configura seminários, organiza grupos, registra presença, conduz a apresentação e avalia.
- **Aluno:** consulta os seminários das disciplinas em que está matriculado, acompanha apresentações, avalia outros grupos e consulta resultados.

A aplicação é dividida em:

- **Frontend:** Next.js/React com TypeScript, na pasta `/front-end`.
- **Backend:** PHP puro, sem framework, com PDO e respostas JSON, na pasta `/back-end`.
- **Banco:** MySQL 8.0.13 ou superior, com scripts oficiais na pasta `/database`.
- **Documentação:** especificações e decisões na pasta `/docs`.

## 2. Estado atual do projeto

| Área | Estado nesta cópia |
| --- | --- |
| Documentação | Consolidada e amarrada: este guia, o documento técnico (seções 6.3.21 e 6.3.22), o contrato JSON (seções 7.11, 7.12 e 9.4), os padrões PHP e as specs formam a base de implementação. |
| Banco de dados | Os 3 scripts oficiais estão em `/database`; o banco local de referência foi validado em MySQL 8.0.44. Cada integrante deve aplicá-los no seu ambiente (ver §4.3). |
| Backend | Infraestrutura em `back-end/src/`, **19 endpoints admin**, **21 endpoints do professor** e **5 endpoints do aluno** implementados em `back-end/public/api/`. Todas as rotas do catálogo 6.3.7 estão disponíveis. |
| Frontend | Projeto Next.js completo em `/front-end`, com `lib/services/` já integrado (`admin.ts`, `professor.ts`, `aluno.ts`, `api.ts`, `mappers.ts`). `mock-data.ts` é apenas legado. |
| Integração | Admin, aluno e professor consomem a API via `lib/services`; `apresentacao/iniciar.php` e `grupos/sincronizar.php` estão disponíveis para uso futuro do service do professor. |

**Consequência prática:** implemente o PHP seguindo **contrato → catálogo 6.3.7 → DDL oficial → spec da funcionalidade**. Os services do frontend são a referência viva do formato que a UI espera; o índice único endpoint → DTO → tela está no contrato, seção **9.4**.

## 3. Estrutura canônica do repositório

| Pasta | Conteúdo |
| --- | --- |
| `/docs` | Documentação técnica, specs, padrões de código e contrato JSON. |
| `/database` | Scripts SQL oficiais: `01_schema_ddl.sql`, `02_trigger_exclusividade.sql` e `03_seeds_dml.sql`. |
| `/back-end` | API PHP. O document root é `back-end/public/`. |
| `/front-end` | Aplicação Next.js/React. |

Nomes antigos como `/backend/`, `/frontend/` e `/dados/` devem ser lidos como `/back-end/`, `/front-end/` e `/database/`.

## 4. Stack e decisões já tomadas

- **PHP puro**, sem framework e sem ORM.
- **PDO com prepared statements** em todas as consultas.
- **API JSON** em `/api/{perfil}/{recurso}/{acao}.php`, com um arquivo por operação.
- **URL base de desenvolvimento:** `http://localhost:8000` (sem `/api`); os endpoints documentados já incluem o prefixo `/api/...`.
- **Servidor local:** `php -S localhost:8000 -t back-end/public`.
- **MySQL 8.0.13+**; o ambiente local usa MySQL 8.0.44. MariaDB/XAMPP não é o alvo.
- **Sem autenticação real no MVP:** o professor envia `?professor_id={id}`, o aluno envia `?aluno_id={id}` e o admin não envia identificador. A limitação está registrada na seção 6.3.15 do documento técnico.
- **Exclusão lógica** de alunos e professores por `status` (`ativo`/`inativo`).
- **Cálculo de notas no backend**, por média ponderada normalizada. O React não recalcula a nota oficial.
- **Mock de notas dos alunos** completo, idempotente e dentro da mesma transação da avaliação do professor.
- **Backend híbrido:** CRUD simples pode ter SQL PDO no próprio endpoint; fluxos complexos ficam em `src/dominio/`; consultas compartilhadas podem ir para `src/consultas/`; a conversão para DTO fica em `src/contrato/`.
- **Contrato JSON:** a API devolve DTOs em camelCase, com IDs numéricos; o frontend converte os IDs para strings prefixadas em `lib/services`. O contrato completo está em `docs/contrato_api_frontend.md`.
- **Ambiente PHP:** credenciais ficam em `back-end/.env` (não versionado). O modelo versionado é `back-end/.env.example`; o `.gitignore` da raiz ignora `.env` e `.env.*`, exceto o example.
- **Ambiente frontend:** `front-end/.env.example` documenta `NEXT_PUBLIC_API_BASE_URL` e `NEXT_PUBLIC_PROFESSOR_ID`. O arquivo local `front-end/.env.local` (não versionado) define o professor ativo usado nos testes da área de professor — hoje `3`, porque os professores 1 e 2 foram inativados durante os testes admin.
- **Identidade padrão do MVP:** `PROFESSOR_PADRAO=1` e `ALUNO_PADRAO=1` ficam no `.env` e são expostas como constantes no bootstrap. O frontend usa esses valores, mas a query string `?professor_id=` / `?aluno_id=` continua obrigatória conforme o contrato.
- **Comandos de desenvolvimento (Fase 1):**
  ```bash
  # aplicar banco
  mysql -u root -p < database/01_schema_ddl.sql
  mysql -u root -p < database/02_trigger_exclusividade.sql
  mysql -u root -p < database/03_seeds_dml.sql

  # subir API
  php -S localhost:8000 -t back-end/public

  # validar
  curl "http://localhost:8000/api/admin/alunos/listar.php"
  curl "http://localhost:8000/api/professor/seminarios/listar.php?professor_id=1"
  cd front-end && npx tsc --noEmit && npm run build
  ```
- **Exclusão em lote (admin):** `POST /api/admin/alunos/excluir-lote.php` e `POST /api/admin/professores/excluir-lote.php` recebem `{ "ids": number[] }`, são atômicas e retornam `{ "afetados": n }`.

### 4.1. Estrutura da raiz do projeto

| Caminho | Descrição |
| --- | --- |
| `back-end/` | API PHP, com `public/` (document root, único exposto via HTTP) e `src/` (código interno). |
| `front-end/` | Aplicação Next.js/React + TypeScript; `lib/services/` concentra as chamadas HTTP. |
| `database/` | Scripts SQL oficiais: `01_schema_ddl.sql`, `02_trigger_exclusividade.sql` e `03_seeds_dml.sql`. |
| `docs/` | Guia de entrada, documentação técnica, contrato JSON, padrões de código e specs por perfil. |
| `.gitignore` | Ignora `node_modules`, `.next`, `.env` locais e credenciais do backend (`back-end/.env`, `back-end/.env.*`, exceto `.env.example`). |

Arquivos de referência na raiz das pastas:

| Arquivo | Para que serve |
| --- | --- |
| `README.md` | Este guia: estado, regras, pré-requisitos e comandos. |
| `docs/documentacao_de_contextualizacao_tecnica.md` | Arquitetura, catálogo de endpoints (6.3.7), amarração de respostas (6.3.21) e estado de handoff (6.3.22). |
| `docs/contrato_api_frontend.md` | DTOs, payloads, enums e a tabela única endpoint → DTO → tela (seção 9.4). |
| `docs/padroes_de_codigo_php.md` | Modelo híbrido de código PHP e convenções da API. |
| `docs/AGENTS.md` | Instruções para agentes de IA e prompt inicial atualizado para o handoff. |
| `docs/especs/` | Especificações funcionais por perfil. |
| `database/01_schema_ddl.sql` | Fonte oficial do schema. |
| `back-end/README.md` | Convenções da API e configuração do `.env` do backend. |
| `front-end/package.json` | Dependências e scripts (`dev`, `build`, `start`). |
| `front-end/.env.example` | Modelo de `NEXT_PUBLIC_API_BASE_URL`, `NEXT_PUBLIC_PROFESSOR_ID` e `NEXT_PUBLIC_ALUNO_ID`. |

### 4.2. Pré-requisitos

| Ferramenta | Versão / observação | Uso |
| --- | --- | --- |
| Node.js | LTS (recomendado 20+) | Executar o frontend Next.js |
| npm | Incluso no Node.js; o projeto também declara `pnpm@12.3.4` | Instalar dependências e rodar scripts do frontend |
| PHP | 8.1+ com extensões `pdo_mysql`, `mbstring` e `json` | Servidor embutido da API (`php -S`) |
| MySQL | **8.0.13 ou superior** (referência local: 8.0.44) | Banco de dados |
| Cliente `mysql` (CLI) | Incluso no MySQL | Aplicar os scripts SQL; necessário para o `DELIMITER` do trigger |

> MariaDB/XAMPP não é o alvo do projeto. Não use o SQL ilustrativo das specs como schema.

### 4.3. Configuração e execução

**1) Banco de dados** — na raiz do projeto, nesta ordem:

```bash
mysql -u root -p < database/01_schema_ddl.sql
mysql -u root -p < database/02_trigger_exclusividade.sql
mysql -u root -p < database/03_seeds_dml.sql
```

`01_schema_ddl.sql` contém `DROP DATABASE` e é destrutivo; use apenas para criar/recriar o ambiente de desenvolvimento.

**2) Backend** — após recriar `back-end/` conforme a seção 6.3.3 do documento técnico:

```bash
# Linux/macOS/Git Bash
cp back-end/.env.example back-end/.env

# Windows (PowerShell)
Copy-Item back-end/.env.example back-end/.env
```

Ajuste `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS` e `APP_ORIGIN` em `back-end/.env`. A porta do MySQL varia por ambiente (o padrão do modelo é `3306`; nesta máquina de referência é `3308`) — confirme com `mysql -u root -p -e "SHOW VARIABLES LIKE 'port';"` antes de subir a API. Um `DB_PORT` errado gera `SQLSTATE[HY000] [2002] Connection refused` em todos os endpoints. Em seguida, **a partir da raiz do projeto**:

```bash
php -S localhost:8000 -t back-end/public
```

URL base da API: `http://localhost:8000` (os endpoints já incluem `/api/...`). Teste rápido:

```bash
curl "http://localhost:8000/api/admin/alunos/listar.php"
```

**3) Frontend:**

```bash
cd front-end
cp .env.example .env.local
corepack enable
pnpm install --frozen-lockfile
pnpm dev
```

O gerenciador canônico é **pnpm** (`packageManager: pnpm@12.3.4`); não versionar `package-lock.json`. Acesse `http://localhost:3000`. Checagens úteis:

```bash
pnpm typecheck
pnpm build
./scripts/smoke-api.sh   # na raiz do projeto, com a API e o MySQL no ar
```

### 4.4. Amarração dos formatos de resposta

Os formatos que antes ficavam implícitos nas specs estão agora definidos e amarrados ao consumidor:

| O que era implícito | Onde foi amarrado |
| --- | --- |
| Resposta de `apresentacao/carregar.php` / `iniciar.php` / `salvar-avaliacao.php` | Contrato 7.11 (`ApresentacaoDTO`) e técnico 6.3.21 |
| Resposta de `aluno/avaliacoes/formulario.php` / `salvar.php` | Contrato 7.12 (`FormularioAvaliacaoAlunoDTO`) e técnico 6.3.21 |
| Resposta de `presencas/listar.php` / `salvar.php` e de `encerrar.php` | Contrato 9.4 e técnico 6.3.21 |
| Índice completo endpoint → DTO → tela | Contrato 9.4 |

**Exceção documentada:** o array `grupos` de `ApresentacaoDTO` mantém chaves `snake_case` (`seminario_data_id`, `ordem_apresentacao`, `integrantes_ids`) porque a tela ao vivo as consome assim. Se for normalizar para `GrupoDTO` camelCase, atualize `front-end/lib/services/professor.ts` e a página de apresentação no mesmo conjunto de mudanças.

## 5. Ordem de leitura obrigatória

Antes de implementar qualquer coisa, leia nesta ordem:

1. **`README.md`** — este guia.
2. **`docs/documentacao_de_contextualizacao_tecnica.md`** — arquitetura, endpoints, segurança, transações e decisões.
3. **`docs/contrato_api_frontend.md`** — formato dos DTOs, IDs, enums, datas e integração com o frontend; **comece pela seção 9.4** (endpoint → DTO → tela) e depois leia as seções 7.11 e 7.12.
4. **`docs/padroes_de_codigo_php.md`** — como escrever o PHP no modelo híbrido.
5. **A spec da funcionalidade** em `docs/especs/{admin,professor,aluno}/`.
6. **`database/01_schema_ddl.sql`** — se a tarefa envolver banco de dados.

Para agentes de IA, use também **`docs/AGENTS.md`**, que contém as instruções padronizadas e o prompt inicial para o OpenCode.

## 6. Regras que não podem ser quebradas

### Estrutura e backend

- Não criar frameworks, ORMs ou bibliotecas de backend.
- Não criar endpoints fora do catálogo da seção 6.3.7 sem antes atualizar a documentação.
- Um arquivo PHP representa uma única operação.
- O backend nunca imprime HTML, `var_dump`, avisos ou espaços antes do JSON.
- Toda consulta usa PDO com prepared statements. Não concatenar valores na SQL.
- Toda operação complexa que altere mais de uma tabela usa transação (`beginTransaction`, `commit`, `rollBack`).
- O endpoint valida método HTTP, identidade e entrada antes de executar qualquer operação.
- O backend valida a propriedade do recurso: professor só altera o que é dele; aluno só consulta seus dados e os do seu grupo.

### Banco de dados

- O DDL oficial é `database/01_schema_ddl.sql`. Os blocos SQL das specs são ilustrativos e não devem ser copiados.
- Não alterar o schema sem atualizar a documentação e justificar a necessidade.
- Alunos e professores são excluídos logicamente, nunca com `DELETE` físico.
- Um aluno não pode pertencer a dois grupos da mesma sessão; o banco protege `INSERT` e `UPDATE` em `grupo_alunos` com triggers.
- Cursos com disciplinas e disciplinas com seminários não podem ser excluídos; o backend responde `409 Conflict`.
- As listagens filtram registros ativos por padrão; `?incluir_inativos=1` inclui inativos.

### Contrato JSON

- JSON de entrada e saída: **camelCase**, alinhado a `front-end/lib/types.ts`.
- Query string: **snake_case** (`professor_id`, `aluno_id`, `data_id`, `grupo_id`, `disciplina_id`, `incluir_inativos`).
- IDs na API: **numéricos** (exceto o `id` sintético de avaliação, `av-...`). IDs no frontend: strings opacas com prefixo (`u-al-1`, `u-prof-1`, `t-1`, `s-1`, `g-1`). A conversão é feita em `front-end/lib/services`.
- A API nunca devolve linhas cruas do banco.
- Envelopes de resposta seguem a seção 6.3.10: `{ "sucesso": true, "dados": ... }` ou `{ "sucesso": false, "mensagem": ..., "erros": ... }`.
- Enums, pesos e datas são traduzidos pela API/serializadores; o frontend não recebe nomes de colunas.

### Frontend

- Novas chamadas HTTP ficam em `front-end/lib/services`, nunca espalhadas pelos componentes.
- O `AppProvider` continua sendo a fachada de estado da aplicação. Ele pode mudar por dentro para buscar dados e enviar mutações, mas os componentes devem mudar o mínimo possível.
- Novos componentes não devem importar `mock-data.ts`. Os dados mockados são apenas legado de demonstração.
- O painel de resultados deve consumir `/api/professor/resultados/compilar.php` ou `/api/aluno/resultados/compilar.php`, conforme o perfil; o cálculo oficial não é duplicado no React.
- Se um tipo do frontend precisar mudar, atualize o contrato JSON e os mapeamentos junto.

## 7. Prompt inicial para o OpenCode

Copie o bloco abaixo no início de uma sessão do OpenCode ou peça: **“Leia `docs/AGENTS.md` e siga as instruções.”**

```text
Você está no projeto SGS (sistema acadêmico de gestão de seminários).
Antes de qualquer alteração, leia nesta ordem:
1. README.md
2. docs/documentacao_de_contextualizacao_tecnica.md
3. docs/contrato_api_frontend.md
4. docs/padroes_de_codigo_php.md
5. docs/especs/<perfil>/<spec-da-funcionalidade>.md
6. database/01_schema_ddl.sql (somente se a tarefa envolver banco)

Regras obrigatórias:
- PHP puro, PDO e prepared statements; não usar framework nem ORM.
- Endpoints em back-end/public/api/{perfil}/{recurso}/{acao}.php, um arquivo por operação.
- JSON em camelCase e query string em snake_case.
- A API devolve DTOs; nunca linhas cruas do banco.
- Sem autenticação real: use ?professor_id= e ?aluno_id=; admin não envia ID.
- Exclusão de alunos/professores é lógica (status ativo/inativo).
- Cálculo de notas e mock ficam no backend; o React não recalcula a nota oficial.
- O schema oficial é database/01_schema_ddl.sql; não copie SQL das specs.
- Novas chamadas HTTP do frontend ficam em lib/services.
- Antes de criar ou alterar endpoint, atualize o contrato JSON e a seção 6.3.

Estado atual:
- /back-end com infraestrutura, 19 endpoints admin, 21 endpoints do professor e 5 endpoints do aluno; catálogo 6.3.7 completo.
- /front-end está completo, com `lib/services/` integrado; `mock-data.ts` é apenas legado.
- /database e /docs estão prontos; o contrato (seções 7.11, 7.12 e 9.4) amarra os formatos de resposta.
- A arquitetura aprovada é híbrida + contrato JSON; a ordem de trabalho está em README.md §4.3 e na seção 6.3.22 do documento técnico.

Tarefa desta sessão:
<descreva aqui a tarefa, o perfil e a spec relacionada>

Antes de codar, responda com:
1. quais documentos leu;
2. quais arquivos pretende alterar;
3. quais decisões do contrato ou da seção 6.3 se aplicam;
4. o que não fará para não sair do escopo.
```

## 8. Mapa dos documentos

| Documento | Para que serve |
| --- | --- |
| `README.md` | Visão geral, estado atual, regras e prompt inicial. |
| `docs/AGENTS.md` | Instruções padronizadas para OpenCode e outros agentes. |
| `docs/documentacao_de_contextualizacao_tecnica.md` | Arquitetura, endpoints, segurança, transações e decisões DP-001 a DP-036. |
| `docs/contrato_api_frontend.md` | DTOs, IDs, enums, datas, payloads, formatos de resposta (7.11/7.12) e amarração endpoint → DTO → tela (9.4). |
| `docs/padroes_de_codigo_php.md` | Modelo híbrido de código PHP, exemplos e o que evitar. |
| `docs/integracao_de_branches.md` | Branches divergentes, riscos de merge e estratégia de integração. |
| `docs/especs/admin/` | Especificações de alunos, professores, cursos e disciplinas. |
| `docs/especs/professor/` | Especificações das etapas 1, 2 e 3 do seminário. |
| `docs/especs/aluno/` | Especificação do portal do aluno. |
| `back-end/README.md` | Configuração do `.env`, comandos do servidor e convenções da API. |
| `database/01_schema_ddl.sql` | Fonte oficial das tabelas, colunas, FKs e índices. |
| `database/02_trigger_exclusividade.sql` | Regra de exclusividade de grupo por sessão (INSERT e UPDATE). |
| `database/03_seeds_dml.sql` | Dados iniciais para desenvolvimento. |
| `front-end/lib/services/` | Services por perfil (`admin.ts`, `professor.ts`, `aluno.ts`) e helpers (`api.ts`, `mappers.ts`). |
| `front-end/lib/types.ts` | Tipos de domínio usados pela UI. |
| `front-end/lib/mock-data.ts` | Dados de demonstração; não é fonte de verdade. |

## 8.1. Como aplicar os scripts SQL

Use o cliente `mysql` do MySQL, na ordem abaixo:

```bash
mysql -u root -p < database/01_schema_ddl.sql
mysql -u root -p < database/02_trigger_exclusividade.sql
mysql -u root -p < database/03_seeds_dml.sql
```

O `DELIMITER $$` de `02_trigger_exclusividade.sql` é uma diretiva do cliente `mysql`, não um comando SQL do servidor. Por isso o trigger deve ser aplicado via CLI; se for necessário fazê-lo por PHP, remova as linhas `DELIMITER`, o sufixo `$$` e envie o `CREATE TRIGGER` como uma única instrução.

`01_schema_ddl.sql` contém `DROP DATABASE` e é destrutivo: execute apenas na inicialização do ambiente de desenvolvimento.

## 9. Pendências e armadilhas conhecidas

- **Rotas de professor concluídas:** `/api/professor/apresentacao/iniciar.php` (POST, devolve `ApresentacaoDTO`) e `/api/professor/grupos/sincronizar.php` (PUT, devolve `GrupoDTO[]`) foram expostas a partir das funções de domínio existentes. A base tem 19 endpoints admin, 21 do professor e 5 do aluno. O `lib/services/professor.ts` ainda não chama essas duas rotas; ao fazê-lo, siga o catálogo 6.3.7 e o índice do contrato, seção 9.4.
- **Frontend integrado, mas dependente da API:** as telas consomem `lib/services`; sempre que um endpoint ainda não existir, a tela correspondente falhará ao carregar.
- **IDs diferentes:** o banco usa inteiros; o frontend usa strings prefixadas. A conversão é responsabilidade de `lib/services`.
- **Nomes diferentes:** o banco usa `nome_completo`, `matricula`, `ano_matricula`, `cursos.sigla`; o frontend usa `nome`, `registro`, `anoMatricula`, `sigla`. O contrato define o de-para.
- **Enums diferentes:** `pendente`/`concluido` no banco vs `rascunho`/`finalizado` no frontend; `aluno` vs `par`. O contrato define o de-para.
- **`Turma` não é uma tabela:** no frontend, `Turma` representa uma disciplina com dados do curso. O `id` de `Turma` é o `id` de `disciplinas`.
- **Campos derivados:** `Turma.nome` vem de `cursos.sigla` (agora persistida); `Grupo.nome` e `chamadaRealizada` são derivados. `Turma.periodo` e `Criterio.descricao` foram removidos do protótipo; período existe apenas no cadastro de aluno.
- **`chamadaRealizada`:** não existe no schema; deve ser derivada da existência de registros em `presencas` para a sessão.
- **`ResultsView`:** legado; substituído por `ResultadoView`, que consome `ResultadoDTO` diretamente. Não recalcule notas no React.
- **Admin:** não existe tabela de administradores. O admin do MVP é a identidade fixa `u-coord`, sem registro no banco.
- **Sessão cancelada:** o schema prevê `cancelada` em `seminario_datas`, mas a UI do MVP trabalha apenas com “agendado” e “finalizado”. Cancelamento está fora do escopo atual.
- **Exclusão em lote:** o contrato prevê `POST .../excluir-lote.php` para alunos e professores, com validação prévia e transação única (DP-021).
- **Atualização de grupos:** a UI substitui o array inteiro; `PUT /api/professor/grupos/sincronizar.php` com `{ "grupos": [{ "id": number|null, "tema": string, "integrantesIds": number[] }] }` está implementado (DP-023/DP-025).
- **Rotas do aluno:** as rotas canônicas são `/aluno/seminarios/{id}/ao-vivo` e `/aluno/resultados`, alinhadas ao protótipo. As rotas `/avaliar` e `/resultados` por seminário são legado da spec e não existem na UI atual.

## 10. Critérios de conclusão de uma tarefa

Uma tarefa só é considerada concluída quando:

- a spec da funcionalidade foi lida e está consistente com o contrato;
- o endpoint segue o catálogo, o envelope e os códigos HTTP;
- o contrato JSON foi atualizado se o payload mudou;
- o backend usa PDO/prepared statements e transação quando necessário;
- o frontend consome a API via `lib/services` e não importa mock-data em código novo;
- o fluxo foi testado com MySQL 8.0.13+;
- a documentação afetada foi atualizada no mesmo conjunto de mudanças.

## 11. Manutenção da documentação

- A documentação técnica é a referência de arquitetura.
- O contrato JSON é a referência do formato dos dados.
- As specs são a referência funcional de cada tela.
- O schema oficial é a referência do banco.
- Não duplicar SQL, DTOs ou listas de endpoints em vários documentos: referencie a fonte de verdade.
- Ao mudar uma decisão, atualize o DP correspondente na seção 13 do documento técnico e, se afetar o frontend, o contrato JSON.

---

**Resumo em uma frase:** o SGS usa Next.js no frontend, PHP puro/PDO + MySQL no backend, arquitetura híbrida, contrato JSON em camelCase e cálculo de notas no servidor. Leia o documento técnico e o contrato antes de codar.
