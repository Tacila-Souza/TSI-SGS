# Contrato JSON — Frontend ↔ API

> **Status:** contrato de referência da integração. Este documento define o formato dos dados trocados entre o Next.js/React e a API PHP.
>
> **Leia antes:** `docs/README.md` e `docs/documentacao_de_contextualizacao_tecnica.md`.
>
> **Regra de manutenção:** qualquer endpoint, campo, enum ou payload novo deve ser refletido aqui antes ou junto da implementação.

## 1. Princípios

1. O banco continua normalizado e usa `snake_case`.
2. A API é a única ponte entre o frontend e o MySQL.
3. A API devolve **DTOs**, nunca linhas cruas do banco.
4. O JSON de entrada e saída usa **camelCase**, alinhado a `front-end/lib/types.ts`.
5. A query string usa **snake_case**, porque faz parte da URL documentada (`?professor_id=`, `?data_id=` etc.).
6. Os IDs na API são **numéricos**. O frontend usa IDs opacos com prefixo (`u-al-1`, `t-1`, `s-1`). A conversão fica em `front-end/lib/services`.
7. O envelope de resposta é sempre o da seção 6.3.10 do documento técnico.
8. O React não recalcula a nota oficial; resultados vêm de `/api/professor/resultados/compilar.php` ou `/api/aluno/resultados/compilar.php`, conforme o perfil.

## 2. Envelope e códigos HTTP

### Sucesso

```json
{
  "sucesso": true,
  "dados": {},
  "mensagem": "Operação realizada com sucesso."
}
```

- `dados` pode ser objeto, array ou `null`.
- `mensagem` é opcional em sucesso.

### Erro

```json
{
  "sucesso": false,
  "mensagem": "Matrícula já cadastrada.",
  "erros": {
    "registro": "Esta matrícula já pertence a outro aluno."
  }
}
```

- `mensagem` é obrigatória em erro.
- `erros` é opcional e serve para validação por campo.

### Códigos

| Código | Uso |
| --- | --- |
| 200 | Consulta, atualização ou exclusão concluída. |
| 201 | Cadastro concluído. |
| 204 | Preflight CORS (`OPTIONS`), sem corpo. |
| 400 | Dados inválidos ou regra de negócio violada. |
| 401 | Reservado para autenticação real; não usado no MVP. |
| 403 | Recurso pertence a outro usuário/perfil. |
| 404 | Registro não encontrado. |
| 409 | Unicidade ou dependência que impede exclusão. |
| 500 | Erro interno não previsto. |

## 3. IDs

### Regra

- **Banco e API:** `INT UNSIGNED`.
- **Frontend:** string opaca com prefixo.
- **Conversão:** `front-end/lib/services/mappers.ts` (ou arquivo equivalente).
- **Componentes:** nunca fazem `parseInt` nem concatenam prefixos manualmente.

### Prefixos do frontend

| Entidade | Banco | Prefixo no frontend | Exemplo |
| --- | --- | --- | --- |
| Aluno | `alunos.id` | `u-al-` | `u-al-1` |
| Professor | `professores.id` | `u-prof-` | `u-prof-1` |
| Admin | não persistido | fixo | `u-coord` |
| Curso | `cursos.id` | `c-` | `c-1` |
| Disciplina/Turma | `disciplinas.id` | `t-` | `t-1` |
| Seminário | `seminarios.id` | `s-` | `s-1` |
| Sessão/Data | `seminario_datas.id` | `sd-` | `sd-1` |
| Grupo | `grupos.id` | `g-` | `g-1` |
| Critério | `seminario_criterios.id` | `cr-` | `cr-1` |
| Avaliação | DTO composto | `av-` | `av-1-2-3` |

### Observações

- O admin do MVP é uma identidade fixa do frontend (`u-coord`). Não existe tabela de administradores.
- `Turma` é o nome usado pela UI para uma disciplina. `Turma.id` e `Disciplina.id` apontam para `disciplinas.id`; o prefixo é `t-` nos dois casos.
- A avaliação não tem uma PK única no banco: é a combinação de várias linhas de `avaliacoes_notas` + `avaliacoes_feedbacks`. O `id` do DTO é sintético (`av-{dataId}-{grupoId}-{avaliadorId}`) e serve apenas como chave de UI.
- O backend não conhece os prefixos de usuário/entidade. Recebe e devolve números; a única exceção é o `id` sintético de avaliação (`av-...`), que não é uma PK. O service do frontend faz o de-para dos demais IDs.

## 4. Nomes de campos

### Regra

| Contexto | Convenção | Exemplo |
| --- | --- | --- |
| Banco | `snake_case` | `nome_completo`, `ano_matricula` |
| Query string | `snake_case` | `?professor_id=1&data_id=2` |
| JSON da API | `camelCase` | `nome`, `anoMatricula` |
| Tipos do frontend | `camelCase` | `nome`, `anoMatricula` |

### De-para principal

| Banco | API/JSON | Frontend |
| --- | --- | --- |
| `alunos.nome_completo` | `nome` | `nome` |
| `alunos.matricula` | `registro` | `registro` |
| `alunos.ano_matricula` | `anoMatricula` | `anoMatricula` |
| `alunos.semestre_entrada` | `semestreEntrada` | `semestreEntrada` |
| `professores.nome_completo` | `nome` | `nome` |
| `professores.registro` | `registro` | `registro` |
| `cursos.sigla` | `sigla` / `nome` (em `Turma`) | `sigla` / `nome` |
| `disciplinas.id` | `id` (em `Disciplina`/`Turma`) | `id` com prefixo `t-` |
| `disciplinas.curso_id` | `cursoId` | `cursoId` |
| `disciplinas.professor_id` | `professorId` | `professorId` |
| `disciplinas.codigo` | `codigo` / `disciplinaSigla` | `disciplinaSigla` |
| `seminarios.disciplina_id` | `turmaId` | `turmaId` |
| `seminarios.tempo_apresentacao` | `tempoApresentacaoMin` | `tempoApresentacaoMin` |
| `seminarios.peso_professor` | `pesoProfessor` (0–1) | `pesoProfessor` (0–1) |
| `seminario_criterios.nome_criterio` | `nome` | `nome` |
| `seminario_criterios.peso_criterio` | `peso` (0–100) | `peso` (0–100) |
| `seminario_criterios.tipo_avaliacao` | `tipo` | `tipo` |
| `grupos.ordem_apresentacao` | `ordem` | `ordem` |
| `avaliacoes_notas.tipo_avaliador` | `tipo` | `tipo` |

## 5. Enums e status

| Contexto | Banco | API/Frontend | Observação |
| --- | --- | --- | --- |
| Seminário | `pendente` | `rascunho` | O backend força `pendente` ao criar. |
| Seminário | `agendado` | `agendado` | |
| Seminário | `em_andamento` | `em_andamento` | |
| Seminário | `concluido` | `finalizado` | |
| Sessão | `agendada` | `agendado` | |
| Sessão | `em_andamento` | `agendado` | A UI não diferencia “em andamento” de “agendado” na sessão; o estado ao vivo fica no cliente. |
| Sessão | `concluida` | `finalizado` | |
| Sessão | `cancelada` | fora do MVP | Não expor na UI atual. |
| Avaliação | `professor` | `professor` | |
| Avaliação | `aluno` | `par` | “Par” = avaliação por outro aluno. |
| Grupo | `pendente`, `em_apresentacao`, `avaliado` | campo extra opcional | A UI atual não usa status do grupo. |
| Usuário | `ativo`, `inativo` | `ativo`, `inativo` | |
| Perfil | `aluno`, `professor` | `aluno`, `professor` | O admin é `coordenador`, sem registro no banco. |

## 6. Datas, horários e números

| Tipo | Formato | Exemplo |
| --- | --- | --- |
| Data | `YYYY-MM-DD` | `2026-11-17` |
| Hora | `HH:mm` | `19:00` |
| Timestamp | ISO 8601 | `2026-11-17T19:00:00-03:00` |
| Nota | número decimal 0–10 | `9.5` |
| Peso de critério | inteiro 0–100 | `30` |
| Peso do professor | decimal 0–1 | `0.6` |
| Ordem | inteiro >= 1 | `1` |

- O backend mantém a precisão e arredonda para duas casas apenas na resposta JSON.
- Campos opcionais ausentes devem ser `null` ou omitidos; não usar a string `"undefined"`.

## 7. DTOs

### 7.1. `UsuarioDTO`

Usado em listagens administrativas, seleção de perfil e dados do usuário logado no MVP.

```json
{
  "id": 1,
  "nome": "Ana Beatriz Souza",
  "email": "ana.beatriz.souza@aluno.instituto.edu.br",
  "role": "aluno",
  "status": "ativo",
  "registro": "20261001",
  "turmaIds": [1],
  "anoMatricula": 2026,
  "semestreEntrada": "1"
}
```

| Campo | Tipo | Obrigatório | Origem |
| --- | --- | --- | --- |
| `id` | number | sim | `alunos.id` ou `professores.id` |
| `nome` | string | sim | `nome_completo` |
| `email` | string | sim | `email` |
| `role` | `"aluno"` \| `"professor"` \| `"coordenador"` | sim | tabela de origem; admin fixo |
| `status` | `"ativo"` \| `"inativo"` | sim | `status` |
| `registro` | string | sim | `matricula` (aluno) ou `registro` (professor) |
| `turmaIds` | number[] | sim | disciplinas do aluno/professor |
| `anoMatricula` | number | não | somente aluno |
| `semestreEntrada` | `"1"` \| `"2"` | não | somente aluno |

**Admin:** o frontend usa o objeto fixo `{ id: "u-coord", nome: "Administrador do Sistema", role: "coordenador", status: "ativo", registro: "SIAPE 100234", turmaIds: [] }`. Não há endpoint de cadastro de admin.

### 7.2. `CursoDTO`

Usado em `/api/admin/cursos/listar.php` e nos selects de curso.

```json
{
  "id": 1,
  "sigla": "TSI",
  "nome": "Tecnólogo em Sistemas para Internet",
  "descricao": "Curso de graduação em TSI",
  "disciplinas": []
}
```

| Campo | Tipo | Origem |
| --- | --- | --- |
| `id` | number | `cursos.id` |
| `sigla` | string | `cursos.sigla` |
| `nome` | string | `cursos.nome` |
| `descricao` | string \| null | `cursos.descricao` |
| `disciplinas` | `DisciplinaDTO[]` | somente quando o endpoint pedir detalhes |

### 7.3. `DisciplinaDTO`

Usado na gestão administrativa de cursos e disciplinas.

```json
{
  "id": 1,
  "cursoId": 1,
  "nome": "Programação Imperativa",
  "codigo": "PI",
  "professorId": 1,
  "professorNome": "Liliane Nascimento",
  "alunoIds": [1, 2],
  "alunosCount": 2
}
```

| Campo | Tipo | Origem |
| --- | --- | --- |
| `id` | number | `disciplinas.id` |
| `cursoId` | number | `disciplinas.curso_id` |
| `nome` | string | `disciplinas.nome` |
| `codigo` | string | `disciplinas.codigo` |
| `professorId` | number \| null | `disciplinas.professor_id` |
| `professorNome` | string \| null | join com `professores` |
| `alunoIds` | number[] | `disciplina_aluno` |
| `alunosCount` | number | contagem de `disciplina_aluno` |

O frontend administrativo atual usa `professores: string[]` e `alunos: string[]` em cada disciplina. O service adapta `professorId`/`alunoIds` para esses arrays; a API não precisa expor os dois formatos.

### 7.4. `TurmaDTO` (compatibilidade com a UI)

O frontend chama de “Turma” o que o banco chama de “Disciplina”. Este DTO existe para manter os componentes atuais.

```json
{
  "id": 1,
  "nome": "TSI",
  "cursoId": 1,
  "cursoDescricao": "Tecnólogo em Sistemas para Internet",
  "disciplina": "Programação Imperativa",
  "disciplinaSigla": "PI",
  "professorId": 1,
  "alunoIds": [1, 2]
}
```

| Campo | Tipo | Origem | Observação |
| --- | --- | --- | --- |
| `id` | number | `disciplinas.id` | Prefixo `t-` no frontend. |
| `nome` | string | `cursos.sigla` | Sigla do curso, exibida como agrupador na UI. |
| `cursoId` | number | `disciplinas.curso_id` | |
| `cursoDescricao` | string | `cursos.descricao` ou `cursos.nome` | |
| `disciplina` | string | `disciplinas.nome` | |
| `disciplinaSigla` | string | `disciplinas.codigo` | |
| `professorId` | number \| null | `disciplinas.professor_id` | |
| `alunoIds` | number[] | `disciplina_aluno` | |

### 7.5. `CriterioDTO`

```json
{
  "id": 1,
  "nome": "Domínio do conteúdo",
  "peso": 30,
  "tipo": "grupo"
}
```

| Campo | Tipo | Origem |
| --- | --- | --- |
| `id` | number | `seminario_criterios.id` |
| `nome` | string | `nome_criterio` |
| `peso` | number (0–100) | `peso_criterio` |
| `tipo` | `"individual"` \| `"grupo"` | `tipo_avaliacao` |

### 7.6. `GrupoDTO`

```json
{
  "id": 1,
  "nome": "Grupo 1",
  "tema": "Arquitetura em camadas",
  "integrantesIds": [1],
  "ordem": 1,
  "status": "pendente"
}
```

| Campo | Tipo | Origem | Observação |
| --- | --- | --- | --- |
| `id` | number | `grupos.id` | |
| `nome` | string | derivado | O schema não tem nome de grupo. Derivar como `"Grupo " + ordem` ou `"Grupo " + id`. |
| `tema` | string | `grupos.tema` | |
| `integrantesIds` | number[] | `grupo_alunos` | |
| `ordem` | number | `ordem_apresentacao` | Se nulo, usar a posição na lista. |
| `status` | string | `grupos.status` | Campo extra; a UI atual não usa. |

### 7.7. `SeminarioSessaoDTO`

```json
{
  "id": 1,
  "data": "2026-11-17",
  "horario": "19:00",
  "grupos": [],
  "presenca": [
    { "alunoId": 1, "presente": true },
    { "alunoId": 2, "presente": false }
  ],
  "chamadaRealizada": true,
  "status": "agendado"
}
```

| Campo | Tipo | Origem | Observação |
| --- | --- | --- | --- |
| `id` | number | `seminario_datas.id` | O frontend atual não usa; o service pode manter oculto. |
| `data` | string | `data_apresentacao` | |
| `horario` | string | `horario` | |
| `grupos` | `GrupoDTO[]` | `grupos` da sessão | |
| `presenca` | array | `presencas` | O service converte para `Record<string, boolean>`. |
| `chamadaRealizada` | boolean | derivado | `true` se existir pelo menos uma linha em `presencas` para a sessão. |
| `status` | `"agendado"` \| `"finalizado"` | `seminario_datas.status` | Ver tabela de enums. |

### 7.8. `SeminarioDTO`

Usado em listagens, detalhes e edição.

```json
{
  "id": 1,
  "titulo": "Seminário de Arquitetura de Software",
  "descricao": "Seminário ao vivo para testar chamada, grupos e avaliações.",
  "disciplina": "Programação Imperativa",
  "turmaId": 1,
  "professorId": 1,
  "data": "2026-11-17",
  "horario": "19:00",
  "tempoApresentacaoMin": 15,
  "pesoProfessor": 0.6,
  "status": "em_andamento",
  "criterios": [],
  "grupos": [],
  "presenca": [],
  "sessoes": []
}
```

| Campo | Tipo | Origem | Observação |
| --- | --- | --- | --- |
| `id` | number | `seminarios.id` | |
| `titulo` | string | `titulo` | |
| `descricao` | string | `descricao` | |
| `disciplina` | string | `disciplinas.nome` | Nome exibido na UI. |
| `turmaId` | number | `seminarios.disciplina_id` | Na UI, `Turma` = disciplina. |
| `professorId` | number | `seminarios.professor_id` | |
| `data` | string | primeira data | Compatibilidade com a UI de sessão única. |
| `horario` | string | primeira data | Compatibilidade. |
| `tempoApresentacaoMin` | number | `tempo_apresentacao` | |
| `pesoProfessor` | number (0–1) | `peso_professor / 100` | O banco guarda 0–100. |
| `status` | enum | `seminarios.status` | Ver tabela de enums. |
| `criterios` | `CriterioDTO[]` | `seminario_criterios` | |
| `grupos` | `GrupoDTO[]` | primeira sessão | Para seminários com várias datas, `sessoes` é a fonte principal. |
| `presenca` | array | primeira sessão | O service converte para record. |
| `sessoes` | `SeminarioSessaoDTO[]` | `seminario_datas` | Opcional quando o endpoint não pedir detalhes. |

**Compatibilidade multi-sessão:** `data`, `horario`, `grupos` e `presenca` no nível raiz espelham a primeira sessão para os componentes que ainda não usam `sessoes`. Telas novas devem usar `sessoes`.

### 7.9. `AvaliacaoDTO`

```json
{
  "id": "av-1-1-1",
  "seminarioId": 1,
  "dataId": 1,
  "dataSessao": "2026-11-17",
  "grupoId": 1,
  "avaliadorId": 1,
  "tipo": "professor",
  "notas": [
    { "criterioId": 1, "alunoId": null, "nota": 9.0 },
    { "criterioId": 2, "alunoId": 1, "nota": 8.5 }
  ],
  "feedback": "Excelente domínio do conteúdo.",
  "criadoEm": "2026-11-17T21:30:00-03:00"
}
```

| Campo | Tipo | Origem | Observação |
| --- | --- | --- | --- |
| `id` | string | sintético | `av-{dataId}-{grupoId}-{avaliadorId}`. Não é PK do banco. |
| `seminarioId` | number | `seminarios.id` | |
| `dataId` | number | `seminario_datas.id` | Usado nas chamadas de API. |
| `dataSessao` | string | `data_apresentacao` | Usado pela UI. |
| `grupoId` | number | `grupos.id` | |
| `avaliadorId` | number | `professores.id` ou `alunos.id` | |
| `tipo` | `"professor"` \| `"par"` | `tipo_avaliador` | `aluno` vira `par`. |
| `notas` | array | `avaliacoes_notas` | O service converte para `Record<string, number>`. |
| `feedback` | string | `avaliacoes_feedbacks` | |
| `criadoEm` | string | `criado_em` | ISO 8601. |

**Chave das notas no frontend:**

- Critério de grupo: `notas[criterioId]`.
- Critério individual: `notas[`${criterioId}:${alunoId}`]`.
- O service converte o array da API para esse mapa e vice-versa.

### 7.10. `ResultadoDTO`

Usado por `/api/professor/resultados/compilar.php` e `/api/aluno/resultados/compilar.php`.

```json
{
  "seminarioId": 1,
  "dataId": 1,
  "dataSessao": "2026-11-17",
  "semAvaliadores": false,
  "pesoProfessor": 0.6,
  "pesoAlunos": 0.4,
  "criterios": [
    { "id": 1, "nome": "Domínio do conteúdo", "peso": 30, "tipo": "grupo" }
  ],
  "ranking": [
    {
      "posicao": 1,
      "grupoId": 1,
      "grupoNome": "Grupo 1",
      "tema": "Arquitetura em camadas",
      "mediaGeral": 9.1,
      "notaProfessor": 9.0,
      "notaAlunos": 9.2,
      "avaliadoresConsiderados": 2,
      "integrantes": [
        {
          "alunoId": 1,
          "nome": "Ana Beatriz Souza",
          "notaProfessor": 9.0,
          "notaAlunos": 9.2,
          "notaFinal": 9.1
        }
      ],
      "notasProfessorPorCriterio": [
        { "criterioId": 1, "nota": 9.0 }
      ]
    }
  ],
  "feedbacks": [
    {
      "grupoId": 1,
      "avaliadorId": 2,
      "tipo": "par",
      "texto": "Apresentação clara e bem estruturada."
    }
  ]
}
```

| Campo | Tipo | Observação |
| --- | --- | --- |
| `semAvaliadores` | boolean | `true` quando `n = 0`; o backend aplica `P_s = T_s`. |
| `pesoProfessor`, `pesoAlunos` | number (opcional) | Pesos configurados no seminário, de `0` a `1`, para os rótulos da tela de resultados. Podem não existir em versões antigas do compilador; a UI nesse caso mostra apenas "Professor". |
| `criterios` | `CriterioDTO[]` | Rubrica criada pelo professor: nome, peso e tipo de cada critério. |
| `ranking` | array | Ordenado por `mediaGeral` decrescente. |
| `mediaGeral` | number | Média aritmética das notas finais dos integrantes. |
| `notaProfessor` | number \| null | `T_s` normalizado; `null` se não houver avaliação do professor. |
| `notaAlunos` | number | `P_s`; quando `n = 0`, o backend aplica `P_s = T_s` e marca `semAvaliadores`. |
| `avaliadoresConsiderados` | number | `n` usado no cálculo. |
| `integrantes` | array | Notas individuais. |
| `notasProfessorPorCriterio` | array (opcional) | Par `criterioId`/`nota` na ordem de `criterios`; critério individual usa a média entre os integrantes. `nota` é `null` quando não há nota do professor. No perfil aluno, o array é vazio nos grupos dos quais ele não participa (DP-036). Quando ausente, a UI mostra "—" para cada critério. |
| `feedbacks` | array | Feedbacks coletados, de `professor` e de `par`, com `tipo`, `avaliadorId` e `avaliadorNome`; a UI separa os dois tipos. Respeita a privacidade do aluno (DP-036). |

### 7.11. `ApresentacaoDTO`

Resposta de `GET /api/professor/apresentacao/carregar.php`, de `POST .../iniciar.php` e de `POST .../salvar-avaliacao.php`. É o payload que monta a tela ao vivo do professor.

```json
{
  "seminarioId": 1,
  "dataId": 7,
  "dataSessao": "2026-11-17",
  "status": "em_andamento",
  "grupos": [
    {
      "id": 3,
      "seminario_data_id": 7,
      "tema": "Arquitetura em camadas",
      "ordem_apresentacao": 1,
      "status": "avaliado",
      "integrantes_ids": [1, 2],
      "avaliado": true,
      "notas": [
        { "criterioId": 1, "alunoId": null, "nota": 9.0 },
        { "criterioId": 2, "alunoId": 1, "nota": 8.5 }
      ],
      "feedback": "Apresentação clara."
    }
  ],
  "gruposAvaliados": 1,
  "totalGrupos": 1
}
```

| Campo | Tipo | Origem / observação |
| --- | --- | --- |
| `seminarioId`, `dataId` | number | IDs numéricos da API. |
| `dataSessao` | string | `seminario_datas.data_apresentacao` (`YYYY-MM-DD`). |
| `status` | string | Status **bruto** do banco (`agendada`, `em_andamento`, `concluida`); não passa pelo de-para da seção 5. |
| `grupos` | array | Linhas de `grupos` da sessão, ordenadas por `ordem_apresentacao, id`. |
| `grupos[].integrantes_ids` | number[] | Vínculos de `grupo_alunos`. |
| `grupos[].avaliado` | boolean | `true` quando existir pelo menos uma nota **do professor** para aquele grupo. |
| `grupos[].notas` | array | Notas já salvas do professor na sessão/grupo; `alunoId` é `null` para critério de grupo. |
| `grupos[].feedback` | string | Texto salvo do professor; `""` quando ainda não houver feedback. |
| `gruposAvaliados`, `totalGrupos` | number | Contadores para a barra de progresso da tela ao vivo. |

> **Exceção legada amarrada:** os campos dentro de `grupos` (`seminario_data_id`, `ordem_apresentacao`, `integrantes_ids`) permanecem em `snake_case` neste DTO porque `carregarApresentacao` em `front-end/lib/services/professor.ts` e a página `/professor/seminarios/[id]/apresentar` os consomem exatamente assim. Não confundir com `GrupoDTO` (seção 7.6), que é camelCase e usado por `grupos/listar`, `grupos/cadastrar`, `grupos/editar`, `grupos/sortear` e pelo campo `grupos` de `SeminarioDTO`. Se essa exceção for eliminada, o service e a página devem ser atualizados no mesmo conjunto de mudanças.

**Nota:** `POST .../iniciar.php` devolve este mesmo DTO; `POST .../encerrar.php` devolve `{ "dataId": number, "status": "finalizado", "gruposPendentes": number }`.

### 7.12. `FormularioAvaliacaoAlunoDTO`

Resposta de `GET /api/aluno/avaliacoes/formulario.php` e de `POST /api/aluno/avaliacoes/salvar.php`. É o payload da tela `/aluno/seminarios/{id}/ao-vivo`.

```json
{
  "seminarioId": 1,
  "dataId": 7,
  "dataSessao": "2026-11-17",
  "criterios": [
    { "id": 1, "nome": "Domínio do conteúdo", "peso": 50, "tipo": "grupo" },
    { "id": 2, "nome": "Clareza e didática", "peso": 50, "tipo": "individual" }
  ],
  "grupos": [
    {
      "id": 3,
      "nome": "Grupo 1",
      "tema": "Arquitetura em camadas",
      "integrantesIds": [1, 2],
      "integrantes": [
        { "alunoId": 1, "nome": "Ana Beatriz Souza" },
        { "alunoId": 2, "nome": "Lucas Henrique Lima" }
      ],
      "ordem": 1,
      "status": "pendente"
    }
  ],
  "minhasAvaliacoes": [
    {
      "grupoId": 3,
      "notas": [
        { "criterioId": 1, "alunoId": null, "nota": 9.0 },
        { "criterioId": 2, "alunoId": 1, "nota": 8.5 }
      ],
      "feedback": "Apresentação clara."
    }
  ]
}
```

| Campo | Tipo | Origem / observação |
| --- | --- | --- |
| `criterios` | `CriterioDTO[]` | Critérios do seminário, em camelCase (seção 7.5). |
| `grupos` | `GrupoDTO[]` | Somente grupos **elegíveis**: o aluno não é integrante, o grupo não está `avaliado` e possui ao menos um integrante. |
| `grupos[].integrantes` | array (opcional) | Nomes dos integrantes (`{ alunoId, nome }`) para os critérios individuais do formulário; acompanha `integrantesIds`. Quando ausente, a UI usa `Aluno {id}` como rótulo. |
| `minhasAvaliacoes` | array | Avaliações já salvas **por este aluno** na sessão, agrupadas por `grupoId`; serve para reidratar o formulário ao trocar de grupo. |
| `minhasAvaliacoes[].notas` | array | Mesmo formato das notas de `AvaliacaoDTO` (seção 7.9). |
| `minhasAvaliacoes[].feedback` | string | Feedback do aluno para o grupo; `""` quando ainda não houver texto. |

**Regras já aplicadas no backend:** matrícula na disciplina do seminário (DP-034), presença do aluno na chamada e bloqueio de autoavaliação (DP-035). Um grupo pode sair de `grupos` após ser avaliado, mas continua em `minhasAvaliacoes` para reedição enquanto a sessão não estiver finalizada.

## 8. Payloads de operações

### Admin

**Aluno — criar/editar**

```json
{
  "nome": "Ana Beatriz Souza",
  "email": "ana.beatriz.souza@aluno.instituto.edu.br",
  "registro": "20261001",
  "anoMatricula": 2026,
  "semestreEntrada": "1",
  "status": "ativo"
}
```

**Professor — criar/editar**

```json
{
  "nome": "Liliane Nascimento",
  "email": "liliane.nascimento@instituto.edu.br",
  "registro": "204518",
  "status": "ativo"
}
```

**Exclusão em lote — alunos/professores**

```json
{
  "ids": [1, 2, 3]
}
```

- Endpoints: `POST /api/admin/alunos/excluir-lote.php` e `POST /api/admin/professores/excluir-lote.php`.
- Todos os IDs são validados antes da atualização; se qualquer um falhar, nada é alterado.
- Resposta: `{ "sucesso": true, "dados": { "afetados": 3 }, "mensagem": "..." }`.

**Curso — criar/editar**

```json
{
  "sigla": "TSI",
  "nome": "Tecnólogo em Sistemas para Internet",
  "descricao": "Curso de graduação em TSI"
}
```

**Disciplina — criar/editar**

```json
{
  "cursoId": 1,
  "professorId": 1,
  "nome": "Programação Imperativa",
  "codigo": "PI",
  "alunoIds": [1, 2]
}
```

### Professor

**Seminário — criar/editar**

```json
{
  "turmaId": 1,
  "titulo": "Seminário de Arquitetura de Software",
  "descricao": "Seminário ao vivo para testar chamada, grupos e avaliações.",
  "tempoApresentacaoMin": 15,
  "pesoProfessor": 0.6,
  "datas": [
    { "data": "2026-11-17", "horario": "19:00" }
  ],
  "criterios": [
    { "nome": "Domínio do conteúdo", "peso": 30, "tipo": "grupo" },
    { "nome": "Clareza e didática", "peso": 25, "tipo": "individual" }
  ]
}
```

- O backend força o status inicial `pendente`; o DTO devolve `rascunho`.
- A soma dos pesos dos critérios deve ser 100.
- `pesoProfessor` é 0–1; o backend converte para 0–100.
- Edição completa: datas e critérios são identificados por `id`. Itens com `id` são atualizados; itens sem `id` são criados; itens omitidos são removidos somente se não houver histórico; caso contrário, `409 Conflict`.

**Grupo — criar/editar**

```json
{
  "tema": "Arquitetura em camadas",
  "integrantesIds": [1],
  "ordem": 1
}
```

**Presença — salvar em lote**

```json
{
  "presencas": [
    { "alunoId": 1, "presente": true },
    { "alunoId": 2, "presente": false }
  ]
}
```

**Avaliação do professor**

```json
{
  "notas": [
    { "criterioId": 1, "alunoId": null, "nota": 9.0 },
    { "criterioId": 2, "alunoId": 1, "nota": 8.5 }
  ],
  "feedback": "Excelente domínio do conteúdo."
}
```

- Resposta de `POST /api/professor/apresentacao/salvar-avaliacao.php?professor_id={id}&data_id={id}&grupo_id={id}`: `ApresentacaoDTO` (seção 7.11), já com notas/feedback persistidos e contadores atualizados. O mesmo DTO é devolvido por `iniciar.php`; `encerrar.php` devolve `{ "dataId", "status": "finalizado", "gruposPendentes" }`.

**Sorteio de ordem**

```json
{
  "gruposIds": [1, 2, 3]
}
```

**Sincronização de grupos em lote**

`PUT /api/professor/grupos/sincronizar.php?professor_id={id}&data_id={id}`

```json
{
  "grupos": [
    { "id": 1, "tema": "Grupo existente", "integrantesIds": [1, 2] },
    { "id": null, "tema": "Novo grupo", "integrantesIds": [3] }
  ]
}
```

- `id: null` cria um novo grupo; `id` existente atualiza o grupo daquela sessão.
- Grupos omitidos são excluídos, desde que não tenham avaliações; caso contrário, `409`.
- A operação é atômica e respeita a exclusividade de aluno por data.

### Aluno

**Avaliação por pares**

```json
{
  "notas": [
    { "criterioId": 1, "alunoId": null, "nota": 9.0 },
    { "criterioId": 2, "alunoId": 1, "nota": 8.5 }
  ],
  "feedback": "Apresentação clara e bem estruturada."
}
```

- Resposta de `GET /api/aluno/avaliacoes/formulario.php?aluno_id={id}&data_id={id}` e de `POST /api/aluno/avaliacoes/salvar.php?aluno_id={id}&data_id={id}&grupo_id={id}`: `FormularioAvaliacaoAlunoDTO` (seção 7.12).
- Resposta de `GET /api/aluno/resultados/compilar.php?aluno_id={id}&data_id={id}`: `ResultadoDTO` (seção 7.10) com privacidade — `integrantes` e `notasProfessorPorCriterio` (quando presentes) vazios e sem feedbacks de pares nos grupos que não são do aluno (DP-036).

## 9. Integração no frontend

### 9.1. Camada de serviços

Estrutura atual de `front-end/lib/services/`:

```text
front-end/lib/services/
├── api.ts         # base URL, fetch, envelope, erros
├── mappers.ts     # IDs, campos, enums e DTOs
├── admin.ts       # coordenação
├── professor.ts   # configuração, apresentação e resultados do professor
├── aluno.ts       # listagem, avaliação e resultados do aluno
└── index.ts       # reexporta os serviços
```

Os serviços são organizados por **perfil**, não por entidade isolada: um mesmo recurso (por exemplo, seminários) tem funções em `professor.ts` e em `aluno.ts`, cada uma apontando para o endpoint do respectivo perfil. A lista anterior com `cursos.ts`, `disciplinas.ts`, `grupos.ts` etc. era uma sugestão de decomposição e **não** é a estrutura adotada.

Regras:

- `NEXT_PUBLIC_API_BASE_URL` define a URL base do servidor (`http://localhost:8000`, sem `/api`). O service concatena o caminho completo do endpoint, que já inclui `/api/...`; não fixar URLs nos componentes.
- `api.ts` desembrulha o envelope e lança um erro tipado quando `sucesso === false`.
- `mappers.ts` converte IDs numéricos em prefixados e vice-versa.
- Os selects de curso/disciplina do formulário de seminário usam `professorService.listarCursos` e `listarDisciplinas`, que consomem diretamente o banco; a propriedade do professor é validada no cadastro/edição do seminário (DP-027).
- Componentes chamam o `AppProvider` ou funções de serviço; não chamam `fetch` diretamente.

### 9.2. `AppProvider`

- Continua sendo a fachada de estado.
- Na montagem, hidrata usuários, seminários, turmas e avaliações a partir da API.
- As mutações podem ser otimistas para manter a UI responsiva, com rollback/toast em erro.
- O estado `live` (cronômetro) continua local; o backend persiste apenas o status da sessão.
- `ResultadoView` consome diretamente o `ResultadoDTO` da API; nenhum cálculo é feito no React. Ele exibe os critérios da rubrica e, quando disponível, as notas do professor por critério a partir de `criterios` e `notasProfessorPorCriterio`. O componente antigo `ResultsView` (que esperava `Seminario` + `Avaliacao[]` e lia mocks) foi removido.

### 9.3. O que não fazer no frontend

- Não importar `mock-data.ts` em código novo.
- Não usar `nome_completo`, `ano_matricula` ou IDs numéricos diretamente nos componentes.
- Não duplicar a fórmula de nota.
- Não inventar endpoints ou campos fora deste contrato.

### 9.4. Amarração explícita dos formatos de resposta

Esta tabela é o índice único entre endpoint, DTO e consumidor no frontend. Em caso de dúvida sobre o formato de uma resposta, ela prevalece sobre exemplos esparsos das specs.

| Endpoint | Método | DTO de resposta | Consumidor no frontend |
| --- | --- | --- | --- |
| `/api/admin/{alunos,professores,cursos,disciplinas}/listar.php` | GET | `UsuarioDTO[]`, `CursoDTO[]`, `DisciplinaDTO[]` | `admin.ts` → telas `/admin/*` |
| `/api/professor/seminarios/listar.php`, `detalhes.php`, `cadastrar.php`, `editar.php` | GET/POST/PUT | `SeminarioDTO` / `SeminarioDTO[]` | `professor.ts` → dashboard, listagem, formulário e detalhes |
| `/api/professor/{cursos,disciplinas,alunos}/listar.php` | GET | `CursoDTO[]`, `DisciplinaDTO[]`, `UsuarioDTO[]` | `professor.ts` → selects do formulário e aba de presença |
| `/api/professor/grupos/{listar,cadastrar,editar,sortear,sincronizar}.php` | GET/POST/PUT | `GrupoDTO` / `GrupoDTO[]` | `professor.ts` → aba de grupos |
| `/api/professor/grupos/excluir.php` | DELETE | `{ "id": number }` | `professor.ts` → aba de grupos |
| `/api/professor/presencas/{listar,salvar}.php` | GET/POST | `[{ alunoId: number, nome: string, presente: boolean }]` | `professor.ts` → aba de chamada |
| `/api/professor/apresentacao/carregar.php`, `iniciar.php`, `salvar-avaliacao.php` | GET/POST | `ApresentacaoDTO` (7.11) | `professor.ts` → `/professor/seminarios/[id]/apresentar` |
| `/api/professor/apresentacao/encerrar.php` | POST | `{ dataId, status: "finalizado", gruposPendentes }` | `professor.ts` → tela ao vivo |
| `/api/professor/resultados/compilar.php` | GET | `ResultadoDTO` (7.10) | `professor.ts` → `ResultadoView` |
| `/api/aluno/seminarios/{listar,detalhes}.php` | GET | `SeminarioDTO` / `SeminarioDTO[]` | `aluno.ts` → dashboard, listagem e detalhes |
| `/api/aluno/avaliacoes/{formulario,salvar}.php` | GET/POST | `FormularioAvaliacaoAlunoDTO` (7.12), com `grupos[].integrantes` opcional para os critérios individuais | `aluno.ts` → `/aluno/seminarios/[id]/ao-vivo` |
| `/api/aluno/resultados/compilar.php` | GET | `ResultadoDTO` (7.10) com critérios, notas do professor por critério e privacidade (DP-036) | `aluno.ts` → detalhes e `/aluno/resultados` |

Regras de manutenção:

- Todo `dados` de sucesso segue um DTO desta tabela; se um endpoint novo não tiver DTO, adicione-o à seção 7 **antes** de implementar.
- A única exceção de `snake_case` em resposta é o array `grupos` de `ApresentacaoDTO`, documentado na seção 7.11.
- Mutação que retorna estado recém-salvo (`salvar-avaliacao`, `salvar` do aluno, `iniciar`, `salvar` de chamada) devolve o DTO da tela correspondente, não apenas `{ "afetados": n }` ou `201` vazio.

## 10. Integração no backend

- `src/contrato/` contém funções de serialização (`serializar_aluno`, `serializar_seminario`, etc.).
- O endpoint simples pode montar o array diretamente, mas deve usar o serializador para manter o contrato.
- O endpoint complexo chama `src/dominio/` e serializa o resultado.
- SQL usa `snake_case`; o serializador entrega `camelCase`.
- O backend converte `peso_professor` (0–100) para `pesoProfessor` (0–1).
- O backend converte `tipo_avaliador = 'aluno'` para `tipo: 'par'`.
- O backend converte status conforme a tabela de enums.
- O backend nunca devolve `nome_completo`, `ano_matricula`, `id` de coluna crua ou linhas de junção.

## 11. Lacunas conhecidas do modelo atual

Decisões já aplicadas: `cursos.sigla` passou a existir no schema e no frontend; `Criterio.descricao` e `Turma.periodo` foram removidos do protótipo. O período continua representado apenas no cadastro de aluno (`anoMatricula` + `semestreEntrada`).

Lacunas e observações consolidadas (itens marcados como resolvidos não são pendências):

1. **`Grupo.nome`** não existe no schema. Derivar de `ordem_apresentacao` ou do `id`.
2. **`chamadaRealizada`** não existe no schema. Derivar da existência de `presencas` para a sessão.
3. **Admin** não tem tabela. Usar a identidade fixa `u-coord`.
4. **Sessão cancelada** existe no schema, mas não na UI do MVP. Manter fora do contrato até ser especificada.
5. **Exclusão em lote de usuários:** decidida e implementada via `POST .../excluir-lote.php` com `{ "ids": number[] }` (DP-021); não é mais uma lacuna.
6. **Atualização de grupos:** resolvida por `PUT /api/professor/grupos/sincronizar.php?professor_id={id}&data_id={id}` com `{ "grupos": [{ "id": number|null, "tema": string, "integrantesIds": number[] }] }` (DP-023/DP-025); implementada, não pendente.
7. **Rotas do aluno:** as canônicas são `/aluno/seminarios/{id}/ao-vivo` e `/aluno/resultados`. As rotas `/avaliar` e `/resultados` por seminário são legado da spec; não são endpoints da API.
8. **`ResultsView`:** removido; substituído por `ResultadoView`, que consome `ResultadoDTO` diretamente e mostra os critérios do seminário. Não recalcular nota no React.
9. **`ApresentacaoDTO`:** o array `grupos` mantém chaves `snake_case` por compatibilidade com a tela ao vivo; está formalizado na seção 7.11 e na amarração da seção 9.4.

## 12. Exemplos de resposta

### Listar alunos

```json
{
  "sucesso": true,
  "dados": [
    {
      "id": 1,
      "nome": "Ana Beatriz Souza",
      "email": "ana.beatriz.souza@aluno.instituto.edu.br",
      "role": "aluno",
      "status": "ativo",
      "registro": "20261001",
      "turmaIds": [1],
      "anoMatricula": 2026,
      "semestreEntrada": "1"
    }
  ]
}
```

### Detalhes de seminário

```json
{
  "sucesso": true,
  "dados": {
    "id": 1,
    "titulo": "Seminário de Arquitetura de Software",
    "descricao": "Seminário ao vivo.",
    "disciplina": "Programação Imperativa",
    "turmaId": 1,
    "professorId": 1,
    "data": "2026-11-17",
    "horario": "19:00",
    "tempoApresentacaoMin": 15,
    "pesoProfessor": 0.6,
    "status": "em_andamento",
    "criterios": [],
    "grupos": [],
    "presenca": [],
    "sessoes": []
  }
}
```

### Erro de validação

```json
{
  "sucesso": false,
  "mensagem": "Dados inválidos.",
  "erros": {
    "registro": "A matrícula é obrigatória.",
    "email": "Informe um e-mail válido."
  }
}
```

## 13. Checklist de contrato para novos endpoints

- [ ] O endpoint está no catálogo da seção 6.3.7.
- [ ] O DTO foi definido ou atualizado neste documento.
- [ ] O JSON usa camelCase.
- [ ] A query string usa snake_case.
- [ ] IDs de entrada são numéricos na API.
- [ ] IDs de saída são numéricos na API (exceto o `id` sintético de avaliação); o service do frontend prefixa os demais.
- [ ] Enums, pesos e datas foram convertidos.
- [ ] O envelope de sucesso/erro está correto.
- [ ] O backend não devolve linha crua.
- [ ] O frontend não importa mock-data.
- [ ] O cálculo de nota continua no backend.

---

**Regra final:** se houver dúvida entre “usar o nome do banco” e “usar o nome da tela”, a resposta é: **o banco usa o nome do banco; a API entrega o nome da tela; o service faz a ponte.**
