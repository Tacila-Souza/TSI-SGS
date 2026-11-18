# Instruções para Agentes de IA — SGS

> **Uso:** este arquivo é o contexto inicial padronizado para o OpenCode e outros agentes que forem trabalhar no SGS. Peça ao agente para ler este arquivo antes de qualquer alteração. Ele complementa `docs/README.md` e não substitui o documento técnico, o contrato JSON ou o schema oficial.

## 1. Contexto do projeto

O SGS é um sistema acadêmico de gestão de seminários com três perfis:

- **Coordenação (admin):** alunos, professores, cursos e disciplinas.
- **Professor:** seminários, datas, critérios, grupos, presença, apresentação e avaliação.
- **Aluno:** seminários das disciplinas matriculadas, avaliação por pares e resultados.

Arquitetura:

- **Frontend:** Next.js/React + TypeScript em `/front-end`.
- **Backend:** PHP puro, sem framework, PDO e JSON em `/back-end` — **pasta vazia neste pacote**, a reconstruir pela equipe.
- **Banco:** MySQL 8.0.13+ em `/database`.
- **Documentação:** `/docs`.

## 2. Leitura obrigatória antes de codar

Leia nesta ordem:

1. `docs/README.md`
2. `docs/documentacao_de_contextualizacao_tecnica.md`
3. `docs/contrato_api_frontend.md`
4. `docs/padroes_de_codigo_php.md`
5. A spec da funcionalidade em `docs/especs/{admin,professor,aluno}/`
6. `database/01_schema_ddl.sql`, se a tarefa envolver banco

Se a tarefa for de frontend, leia também `front-end/lib/types.ts` e `front-end/AGENTS.md` (regras específicas do Next.js).

## 3. Arquitetura aprovada

### Backend híbrido

- O endpoint é o controlador da operação.
- **CRUD simples** (uma tabela, sem transação e sem cálculo): SQL PDO direto no arquivo do endpoint.
- **Fluxo complexo** (duas ou mais tabelas, transação, cálculo de nota, mock, operação em lote): função em `src/dominio/`.
- **Consulta compartilhada** por mais de um endpoint/perfil: função em `src/consultas/`.
- **Serialização de DTOs**: funções em `src/contrato/`.
- `src/` nunca é exposto pelo servidor; o document root é `back-end/public/`.

### Contrato JSON

- JSON de entrada e saída: **camelCase**, alinhado a `front-end/lib/types.ts`.
- Query string: **snake_case** (`professor_id`, `aluno_id`, `data_id`, `grupo_id`, `disciplina_id`, `incluir_inativos`).
- IDs na API: **numéricos** (exceto o `id` sintético de avaliação, `av-...`). IDs no frontend: strings opacas com prefixo (`u-al-1`, `u-prof-1`, `t-1`, `s-1`, `g-1`). A conversão fica em `front-end/lib/services`.
- Envelope: `{ "sucesso": true, "dados": ... }` ou `{ "sucesso": false, "mensagem": ..., "erros": ... }`.
- A API nunca devolve linhas cruas do banco.
- O contrato completo está em `docs/contrato_api_frontend.md`. A seção **9.4** é o índice único endpoint → DTO → tela; as seções **7.11** e **7.12** formalizam os formatos antes implícitos de apresentação e formulário do aluno.

## 4. Regras invioláveis

### Backend

- Não usar framework, ORM ou biblioteca de backend.
- Não criar endpoint fora do catálogo da seção 6.3.7 sem atualizar a documentação.
- Um arquivo PHP = uma operação.
- Toda consulta usa PDO com prepared statements; nunca concatenar valores na SQL.
- Validar método HTTP, identidade, entrada e propriedade do recurso.
- Operações com múltiplas tabelas usam transação com `beginTransaction`, `commit` e `rollBack`.
- Nunca imprimir HTML, `var_dump`, avisos ou espaços antes do JSON.
- Erros internos retornam `500` genérico; não expor SQL ou stack trace.

### Banco

- O DDL oficial é `database/01_schema_ddl.sql`. Blocos SQL das specs são ilustrativos; não copiar.
- Não alterar o schema sem atualizar a documentação e justificar.
- Alunos e professores têm exclusão lógica (`status` `ativo`/`inativo`), nunca `DELETE` físico.
- Um aluno não pode pertencer a dois grupos da mesma sessão; há trigger para `INSERT` e `UPDATE` em `grupo_alunos`.
- Validações de sessão×grupo e de matrícula×presença ficam no backend; o banco não faz essas checagens complexas.
- `cursos.sigla` é persistida; `Turma.nome` deriva dela. Período existe apenas no cadastro de aluno (`anoMatricula` + `semestreEntrada`).
- Os selects de curso/disciplina do formulário de seminário vêm da API; nunca de `mock-data`. O professor ativo nos testes vem de `NEXT_PUBLIC_PROFESSOR_ID` em `front-end/.env.local`.
- `seminario_criterios` não tem descrição; critério é nome, peso e tipo.
- Cursos com disciplinas e disciplinas com seminários não podem ser excluídos; retornar `409 Conflict`.
- Listagens filtram ativos por padrão; `?incluir_inativos=1` inclui inativos.
- Aplicar os scripts na ordem `01` → `02` → `03` com o cliente `mysql`; `DELIMITER $$` não funciona em PDO. Ver `docs/README.md`, seção 8.1.

### Backend

- Credenciais locais em `back-end/.env`; modelo versionado em `back-end/.env.example`. Nunca versionar o `.env`.
- `PROFESSOR_PADRAO=1` e `ALUNO_PADRAO=1` são constantes do bootstrap, mas `?professor_id=`/`?aluno_id=` continuam obrigatórios.
- Subir a API com `php -S localhost:8000 -t back-end/public` na raiz do projeto.
- Exclusão em lote de alunos/professores: `POST .../excluir-lote.php` com `{ "ids": number[] }`, atômica.
- Escopo de implementação: infraestrutura + 19 endpoints admin (Fase 1).
- Escopo de implementação: 16 endpoints de professor — seminários, grupos, presença, sincronização em lote (Fase 2).
- Edição de seminário deve usar diff por ID e proteger histórico com `409`.
- `SQLSTATE 45000` dos triggers deve virar `400` com mensagem legível.
- Escopo de implementação: avaliação, mock e resultados compilados via API (Fase 3 professor).
- Escopo de implementação: listagem, detalhes, avaliação por pares e resultados com privacidade no backend (Fase 3 aluno).
- Todas as telas consomem a API via `lib/services`; `mock-data.ts` é apenas legado.

### Frontend

- Chamadas HTTP novas ficam em `front-end/lib/services`; não espalhar `fetch` pelos componentes.
- O `AppProvider` é a fachada de estado; pode mudar por dentro, mas os componentes devem mudar o mínimo possível.
- Código novo não importa `mock-data.ts`; os mocks são legado de demonstração.
- `Criterio` não possui `descricao`; `Turma` não possui `periodo`; `Curso` possui `sigla`.
- `LiveState` (cronômetro e grupo em apresentação) é somente do cliente; não tentar persisti-lo nem derivá-lo do banco.
- O cálculo oficial de notas vem de `/api/professor/resultados/compilar.php` ou `/api/aluno/resultados/compilar.php`, conforme o perfil; não duplicar a fórmula no React.
- Se um tipo do frontend mudar, atualizar o contrato JSON e os mapeamentos.

### Documentação

- Não duplicar SQL, DTOs ou listas de endpoints. Referenciar a fonte de verdade.
- Toda mudança de endpoint, campo, enum ou regra deve atualizar o contrato JSON (inclusive a amarração da seção 9.4) e a seção 6.3.
- Toda decisão nova deve virar um DP na seção 13 do documento técnico.

## 5. Estado atual (pacote de handoff)

- `/back-end` está **vazio** nesta cópia. Infraestrutura, endpoints, domínio e serializadores devem ser reconstruídos pela equipe.
- `/front-end` está completo, com `lib/services/` já integrado (`admin.ts`, `professor.ts`, `aluno.ts`, `api.ts`, `mappers.ts`); `mock-data.ts` é apenas legado.
- `/database` contém os 3 scripts oficiais; o banco local de referência foi validado em MySQL 8.0.44. Cada pessoa aplica os scripts no seu ambiente conforme `docs/README.md` §4.3.
- `/docs` está consolidado e amarrado: catálogo em 6.3.7, formatos em 6.3.21, estado de handoff em 6.3.22 e índice endpoint → DTO → tela no contrato, seção 9.4.
- Enquanto o backend não existir, as telas que chamam a API falharão ao carregar — comportamento esperado.

## 6. Como trabalhar nesta base

### Antes de alterar

1. Identifique o perfil e a spec da funcionalidade.
2. Verifique os endpoints existentes na seção 6.3.7.
3. Verifique o DTO correspondente no contrato JSON.
4. Verifique se a operação é simples ou complexa.
5. Liste os arquivos que pretende alterar.

### Durante a alteração

- Siga o modelo híbrido de `docs/padroes_de_codigo_php.md`.
- Use os helpers de `src/http/`, `src/auth/`, `src/validacao/` e `src/contrato/`.
- Mantenha as respostas no envelope padrão.
- Não invente campos, enums ou IDs fora do contrato.
- Se precisar de um campo novo, atualize o contrato antes de implementar.

### Depois de alterar

- Teste o fluxo com MySQL 8.0.13+.
- Confira o JSON de sucesso e de erro.
- Confira se o frontend não voltou a importar mock-data.
- Atualize a documentação afetada no mesmo conjunto de mudanças.

## 7. Prompt inicial para o OpenCode

```text
Você está no projeto SGS (sistema acadêmico de gestão de seminários).
Leia, nesta ordem:
1. docs/README.md
2. docs/documentacao_de_contextualizacao_tecnica.md
3. docs/contrato_api_frontend.md
4. docs/padroes_de_codigo_php.md
5. docs/especs/<perfil>/<spec-da-funcionalidade>.md
6. database/01_schema_ddl.sql (se a tarefa envolver banco)

Siga a arquitetura híbrida: CRUD simples com SQL PDO no endpoint; fluxos complexos em src/dominio/; consultas compartilhadas em src/consultas/; serialização em src/contrato/.

Regras:
- PHP puro, PDO e prepared statements; sem framework/ORM.
- JSON camelCase; query string snake_case; IDs numéricos na API e prefixados no frontend via lib/services.
- Sem autenticação real: ?professor_id= e ?aluno_id=; admin sem ID.
- Exclusão lógica de alunos/professores.
- Cálculo de notas e mock no backend.
- O schema oficial é database/01_schema_ddl.sql; não copie SQL das specs.
- Chamadas HTTP novas do frontend ficam em lib/services.

Estado atual (pacote de handoff):
- /back-end vazio; implemente infraestrutura, endpoints, domínio e serializadores.
- /front-end completo, com lib/services integrado; mock-data é apenas legado.
- /database e /docs prontos; contrato 9.4 amarra endpoint → DTO → tela.
- Ordem de trabalho: docs/README.md §4.3 e documento técnico 6.3.22.

Tarefa desta sessão:
<descreva a tarefa, o perfil e a spec relacionada>

Antes de codar, responda:
1. quais documentos leu;
2. quais arquivos pretende alterar;
3. quais decisões do contrato e da seção 6.3 se aplicam;
4. o que não fará para não sair do escopo.
```

## 8. Checklist rápido

- [ ] Li README, documento técnico, contrato, padrões PHP e a spec.
- [ ] Sei se a operação é simples ou complexa.
- [ ] O endpoint existe no catálogo ou a documentação foi atualizada.
- [ ] O payload e a resposta seguem o contrato JSON.
- [ ] O backend usa PDO/prepared statements e transação quando necessário.
- [ ] O frontend usa `lib/services` e não importa mock-data.
- [ ] O envelope e os códigos HTTP estão corretos.
- [ ] A documentação afetada foi atualizada.

## 9. O que não fazer

- Não implementar autenticação real sem uma decisão explícita.
- Não trocar PHP puro por framework.
- Não criar `/api/comum`, `index.php`, `processa.php` ou `crud.php`.
- Não usar `PATCH`; o padrão é `PUT`.
- Não apagar fisicamente alunos/professores.
- Não recalcular notas no React.
- Não usar SQL das specs como schema.
- Não alterar `front-end/lib/types.ts` sem atualizar o contrato.
- Não commitar credenciais do MySQL.

## 10. Fonte de verdade

| Assunto | Documento |
| --- | --- |
| Visão geral e estado atual | `docs/README.md` |
| Arquitetura, endpoints e decisões | `docs/documentacao_de_contextualizacao_tecnica.md` |
| Formato dos dados da API e amarração endpoint → DTO → tela | `docs/contrato_api_frontend.md` (7.11, 7.12, 9.4) |
| Modelo de código PHP | `docs/padroes_de_codigo_php.md` |
| Funcionalidade por perfil | `docs/especs/` |
| Tabelas e restrições | `database/01_schema_ddl.sql` |
| Tipos da UI | `front-end/lib/types.ts` |
