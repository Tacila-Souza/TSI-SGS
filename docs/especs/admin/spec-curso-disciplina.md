> **Status:** especificação funcional de referência.
> **Leia antes:** `README.md`, `docs/documentacao_de_contextualizacao_tecnica.md` e `docs/contrato_api_frontend.md`.
> **Fonte de verdade:** schema oficial em `/database`; catálogo de endpoints na seção 6.3; contrato JSON no documento de contrato. Os blocos SQL deste arquivo são ilustrativos e não devem ser copiados.

# **ESPECIFICAÇÃO TÉCNICA: GESTÃO DE CURSOS E DISCIPLINAS**

**1\. Identificação**

* **Nome da página / Módulo:** Gestão de Cursos e Disciplinas  

* **Rota (URL):** /admin/cursos  

* **Componentes Principais:** PainelCursos (Master-Detail), ModalCurso, ModalDisciplina.  

* **Objetivo:** Gerenciar a estrutura acadêmica cadastrando cursos, vinculando disciplinas a eles e atribuindo professores e alunos.  

* **Perfil de usuário:** Coordenação (Admin).

**2\. Descrição do funcionamento**

* **Apresentação:** A interface adota o padrão *Master-Detail*. Na lateral esquerda, há uma lista de Cursos. Ao selecionar um curso, a área principal direita exibe os detalhes do curso selecionado e um *grid* (grade) de cards contendo as disciplinas vinculadas àquele curso.  

* **Ações disponíveis:**  

  * Cadastrar, editar e excluir Cursos.  

  * Cadastrar, editar e excluir Disciplinas dentro do curso selecionado.  

  * Nos modais de Disciplina, pesquisar e selecionar o Professor responsável (único) e os Alunos participantes (múltiplos).  

* **Resultado das ações:** A interface reage dinamicamente. Mudar o curso ativo na esquerda recarrega instantaneamente as disciplinas na direita. Modais se fecham após o sucesso e atualizam as listas em tela sem reload.

**3\. Fluxo de navegação**

* **Origem:** Acesso pela área de coordenação (perfil Admin) na Home.  

* **Ações e Destinos (Controlados por Estado):**

Clicar em "Novo curso" \-\> Ativa estado modalCursoAberto \= true.

* Clicar em "Nova disciplina" \-\> Ativa estado modalDisciplinaAberto \= true e dispara consultas simultâneas (GET) para carregar a lista de professores e alunos disponíveis.  

* **Dependências:** Para que a atribuição nas disciplinas funcione, o sistema precisa consumir os dados previamente cadastrados nas entidades de Alunos e Professores.

**4\. Funcionalidades identificadas**

* **F1 \- CRUD de Cursos:** Gerenciamento da entidade Curso.  

* **F2 \- CRUD de Disciplinas:** Gerenciamento da entidade Disciplina (sempre subordinada a um curso).  

* **F3 \- Vínculo de Professor (1:N):** Seleção de 0 ou 1 professor por disciplina.  

* **F4 \- Vínculo de Alunos (N:M):** Seleção de múltiplos alunos para uma disciplina.

**5\. Regras de negócio**

* **RN-001 \- Cadastro Flexível:** O sistema não exige rigidez na ordem. Um curso pode existir sem disciplinas, e uma disciplina pode ser criada sem professor ou alunos vinculados no momento do cadastro.  

* **RN-002 \- Cardinalidade de Professor:** Uma disciplina pode ter, no máximo, 1 professor vinculado. A interface deve impedir múltiplas marcações.  

* **RN-003 \- Integridade de Vínculos:** Ao carregar o modal de edição de uma disciplina, o sistema deve trazer os checkboxes do professor e dos alunos previamente marcados.

**6\. Banco de dados (Bloco A)**

* **Dados que precisam ser armazenados:** Cursos, Disciplinas e os vínculos entre eles e os usuários.  
* **Observação de contrato:** na UI, “Turma” representa uma disciplina com dados do curso; o `id` de `Turma` é o `id` de `disciplinas`. `Turma.nome` vem de `cursos.sigla`, que é persistida. Não existe período em cursos/disciplinas: o período aparece apenas no cadastro de aluno. O schema oficial é `database/01_schema_ddl.sql`.  

* **Entidades e Atributos:**  

  * **Tabela** cursos**:**  

    * id (INT, PK, Auto Increment)  

    * sigla (VARCHAR, NOT NULL, UNIQUE)  

    * nome (VARCHAR, NOT NULL)  

    * descricao (TEXT, NULL)  

  * **Tabela** disciplinas**:**  

    * id (INT, PK, Auto Increment)  

    * curso\_id (INT, FK referenciando cursos(id), NOT NULL)  

    * professor\_id (INT, FK referenciando professores(id), NULL)  

    * nome (VARCHAR, NOT NULL)  

    * codigo (VARCHAR, NOT NULL)  

  * **Tabela Associativa** disciplina\_aluno **(Para a relação N:M):**  

    * disciplina\_id (INT, FK referenciando disciplinas(id), NOT NULL)  

    * aluno\_id (INT, FK referenciando alunos(id), NOT NULL)  

    * *PK Composta:* (disciplina\_id, aluno\_id)

**7\. Lógica de programação / Backend (Bloco B)**

* **Estrutura de Pastas e Endpoints (API):**  

  * **Cursos (`/api/admin/cursos/`):** `listar.php`, `cadastrar.php`, `editar.php`, `excluir.php`.  

  * **Disciplinas (`/api/admin/disciplinas/`):**

    * `listar.php?curso_id={id}` (retorna as disciplinas, a contagem de alunos e os dados do professor para montar o Card).  

    * `detalhes.php?id={id}` (retorna a disciplina com o professor e os alunos vinculados para preencher o modal de edição).  

    * `cadastrar.php` / `editar.php?id={id}` (recebem JSON com `nome`, `codigo`, `professorId` e `alunoIds: [1, 5, 12]`; o de-para completo está em `docs/contrato_api_frontend.md`).  

    * `excluir.php?id={id}`.

* **Processamento Crítico:**

  * O PHP **deve utilizar transações PDO** (`beginTransaction`, `commit`, `rollBack`) ao cadastrar/editar uma Disciplina. Ele deve primeiro fazer o INSERT/UPDATE na tabela disciplinas e, logo em seguida, gerenciar os registros na tabela disciplina\_aluno (limpando vínculos antigos e inserindo os novos baseados no array recebido).

**8\. Frontend React (Bloco C)**

* **Componentes visuais:** Sidebar de Cursos, Grid de Cards de Disciplina, Modais independentes para Formulários.  
* **Estados da interface:** cursoSelecionado (ID), listagemProfessores (Array), listagemAlunos (Array), alunosSelecionados (Array de IDs vinculados aos checkboxes).  

* **Requisições:**  

  * Ao montar o componente (Mount), faz `GET /api/admin/cursos/listar.php`.  

  * Ao selecionar um curso, faz `GET /api/admin/disciplinas/listar.php?curso_id={id}`.  

  * Ao abrir o Modal de Disciplina, dispara *fetch* para as APIs já existentes de listar professores e alunos (`/api/admin/professores/listar.php` e `/api/admin/alunos/listar.php`).  

* **Filtros (Pesquisa Local):** Os campos de busca dentro do modal de Disciplina ("Nome ou registro" e "Nome ou ano de matrícula") atuarão localmente no React, filtrando os arrays já carregados para facilitar a marcação dos checkboxes.

**9\. Matriz de responsabilidade**

| Necessidade | Banco de Dados | Backend (PHP) | Frontend (React) |
| :---- | :---- | :---- | :---- |
| **Vincular Professor** | Armazena a FK professor\_id na tabela disciplina. | Recebe o ID do professor no JSON e faz UPDATE/INSERT na tabela disciplinas. | Limita a seleção a 1 item (comportamento de rádio) e envia o ID selecionado. |
| **Vincular Alunos** | Tabela pivô disciplina\_aluno armazena os pares (Muitos para Muitos). | Utiliza transação PDO para inserir múltiplos registros na tabela pivô. | Controla estado de *checkboxes* múltiplos e envia um array numérico de IDs. |
| **Visualizar Cursos** | Fornece dados dos cursos. | Executa SELECT simples e devolve JSON. | Renderiza a lista na barra lateral da esquerda. |
| **Visualizar Disciplinas** | Fornece dados \+ joins (professor) \+ count (alunos). | Executa query com JOIN e COUNT baseada no curso\_id. | Renderiza os Cards populando os totalizadores (Qtd Alunos/Prof). |

**10\. Dependências e Integração**

* **Reaproveitamento de Endpoints:** A interface vai precisar consumir `GET /api/admin/professores/listar.php` e `GET /api/admin/alunos/listar.php` (endpoints das funcionalidades anteriores) para popular as listas dentro do modal de disciplina.

**11\. Decisão sobre exclusão**

* **Exclusão de Curso:** é bloqueada quando existirem disciplinas vinculadas. O backend retorna `409 Conflict` com mensagem orientando a remover as disciplinas primeiro. Não há exclusão em cascata de cursos.
* **Exclusão de Disciplina:** é bloqueada quando existirem seminários vinculados. O backend retorna `409 Conflict`. Vínculos apenas em `disciplina_aluno` podem ser removidos em cascata.
* **Justificativa:** evitar a perda acidental de seminários, avaliações, presenças e notas históricas.

**12\. Critérios de conclusão**

* Navegação *Master-Detail* fluida (clicar em um curso atualiza a listagem de disciplinas imediatamente).  

* Inclusão de disciplina armazenando corretamente o professor\_id e inserindo os registros na tabela pivô disciplina\_aluno.  

* Card da disciplina exibindo o nome do professor atualizado e a contagem exata de alunos atrelados a ela.

---

**Manutenção:** qualquer alteração de endpoint, campo, enum ou regra deve ser refletida também em `docs/contrato_api_frontend.md` e na seção 6.3 do documento técnico.

