> **Status:** especificação funcional de referência.
> **Leia antes:** `README.md`, `docs/documentacao_de_contextualizacao_tecnica.md` e `docs/contrato_api_frontend.md`.
> **Fonte de verdade:** schema oficial em `/database`; catálogo de endpoints na seção 6.3; contrato JSON no documento de contrato. Os blocos SQL deste arquivo são ilustrativos e não devem ser copiados.

# **ESPECIFICAÇÃO TÉCNICA: GERENCIAMENTO DE ALUNOS**

**1\. Identificação**

* **Nome da página:** Gerenciamento de Alunos  
* **Rota:** /admin/alunos  
* **Objetivo:** Permitir que a coordenação cadastre, consulte, atualize e exclua os alunos vinculados à instituição.  
* **Perfil de usuário:** Coordenação (Admin)  
* **Páginas relacionadas:** Home (Seleção de Perfil)  

**2\. Descrição do funcionamento**

* **Apresentação:** A página exibe uma tabela listando todos os alunos cadastrados com as colunas: Nome/E-mail, Matrícula e Entrada (Ano.Semestre).  
* **Ações disponíveis:**  

  * Buscar aluno por texto.  
  * Cadastrar novo aluno (abre modal de inserção).  
  * Editar aluno existente (abre modal preenchido).  
  * Excluir aluno.  
  * **Resultado das ações:** Após o cadastro, edição ou exclusão, a tabela é atualizada para refletir o estado real do banco de dados sem necessidade de recarregar a página inteira.  

**3\. Fluxo de navegação**

* **Origem:** Acesso a partir da seleção do perfil Coordenação na Home.  
* **Ações principais:** O usuário clica em "+ Novo aluno", preenche o formulário e clica em "Cadastrar". Para edição/exclusão, clica no botão de reticências (...) na linha do aluno correspondente.  
* **Destinos:** A navegação ocorre na própria página através da abertura e fechamento de janelas sobrepostas (Modais).  
* **Dependências:** O acesso deve ocorrer pela área de coordenação. No MVP não há autenticação real; o backend apenas separa as rotas pelo prefixo `/api/admin/`, sem impedir tecnicamente chamadas de outros perfis.  

**4\. Funcionalidades identificadas**

* **F1 \- Listagem de Alunos:** Consulta e exibição tabular dos dados.  
* **F2 \- Busca de Alunos:** Filtro simples baseado em string (nome ou matrícula).  
* **F3 \- Cadastro de Aluno:** Formulário para inserção de um novo registro.  
* **F4 \- Edição de Aluno:** Formulário para alteração de dados de um registro existente.  
* **F5 \- Exclusão de Aluno:** Ação para remover o aluno do sistema.  

**5\. Regras de negócio**

* **RN-001 \- Matrícula Única:** Não é permitido cadastrar ou atualizar um aluno com uma matrícula que já exista no sistema. O backend deve barrar a operação.  
* **RN-002 \- E-mail Único:** Não é permitido haver dois alunos com o mesmo e-mail.  
* **RN-003 \- Obrigatoriedade de Dados:** Os campos Nome completo, E-mail, Matrícula, Ano de matrícula e Semestre de entrada são obrigatórios.  

**6\. Banco de dados (Bloco A)**

* **Dados que precisam ser armazenados:** Informações cadastrais básicas dos alunos.  
* **Entidades:** alunos  
* **Atributos:**  

  * id (INT, Auto Increment)  
  * nome\_completo (VARCHAR)  
  * email (VARCHAR)  
  * matricula (VARCHAR)  
  * ano\_matricula (INT ou YEAR)  
  * semestre\_entrada (TINYINT)  
  * status (ENUM: 'ativo', 'inativo', default 'ativo')  
  * **PKs:** id  
* **FKs:** Nenhuma (neste momento inicial, antes do vínculo com turmas/disciplinas).  
* **Relacionamentos / Tabelas associativas:** Não aplicável nesta view isolada.  
* **Restrições:** UNIQUE para matricula e email. NOT NULL em todos os campos. status com default 'ativo' e índice para filtro de listagem.  

**7\. Lógica de programação / Backend (Bloco B)**

* **Operações necessárias:** Receber requisições CRUD, validar regras de negócio, realizar consultas no banco e devolver os dados em JSON.  
* **Regras aplicadas:** Verificação das RN-001 e RN-002 antes de operações de INSERT ou UPDATE.  
* **Endpoints sugeridos (padrão da seção 6.3):**  

  * GET `/api/admin/alunos/listar.php` (lista ativos, aceitando `?busca=` e `?incluir_inativos=1`)  
  * POST `/api/admin/alunos/cadastrar.php` (cadastra novo)  
  * PUT `/api/admin/alunos/editar.php?id={id}` (atualiza existente)  
  * DELETE `/api/admin/alunos/excluir.php?id={id}` (exclusão lógica)  
  * POST `/api/admin/alunos/excluir-lote.php` (corpo `{ "ids": number[] }`; exclusão lógica atômica em lote)  
  * **Parâmetros/Corpo da Requisição (JSON):** `nome`, `email`, `registro`, `anoMatricula`, `semestreEntrada` e `status` (opcional, usado para reativar). No banco, `nome` corresponde a `nome_completo` e `registro` corresponde a `matricula`; o de-para completo está em `docs/contrato_api_frontend.md`.  
* **Respostas JSON:**  

  * *Sucesso:* `{"sucesso": true, "dados": [...]}`  
  * *Erro:* `{"sucesso": false, "mensagem": "Matrícula já cadastrada no sistema."}`  
  * **Tratamento de erros:** Retornar os códigos HTTP corretos (ex: 200 OK, 201 Created, 400 Bad Request) junto com mensagens de erro inteligíveis.  

**8\. Frontend React (Bloco C)**

* **Informações exibidas:** Tabela de alunos renderizando a concatenação de {anoMatricula}.{semestreEntrada} na coluna "Entrada" (ex: 2026.1).  
* **Campos e formulários:** Inputs de texto (Nome, E-mail, Matrícula), Input Number (Ano), e Select (Semestre: 1 ou 2).  
* **Estados da interface (State):**  

  * Lista de alunos (para preencher a tabela).  
  * Visibilidade dos modais (aberto/fechado).  
  * Dados do formulário ativo.  
  * Termo de busca ativo.  
  * **Requisições:** Utilizar fetch() para conectar com os endpoints criados no PHP.  
* **Atualizações visuais:**  

  * Fazer um novo GET (ou atualizar o estado local) após um cadastro/edição bem-sucedido para que a tabela exiba o novo aluno sem reload.  
  * Exibir notificações ("toast") em caso de sucesso ou erro (ex: "Aluno cadastrado com sucesso" ou "Erro: Matrícula duplicada").  

**9\. Matriz de responsabilidade**

| Necessidade | Banco de Dados | Backend (PHP) | Frontend (React) |
| :---- | :---- | :---- | :---- |
| **Armazenar aluno** | Sim (Insere registro na tabela alunos) | Processa requisição POST, valida dados e executa INSERT PDO. | Coleta dados no formulário e envia via fetch(). |
| **Garantir Matrícula Única** | Define coluna como UNIQUE. | Verifica duplicação no banco antes de cadastrar e retorna erro (RN-001). | Exibe alerta/toast de erro caso o backend recuse o cadastro. |
| **Listar alunos na tabela** | Fornece os registros salvos. | Executa SELECT e retorna JSON com array de alunos. | Faz o GET, recebe o JSON e renderiza a listagem. |
| **Edição e Exclusão** | Atualiza o registro ou altera o status para 'inativo' (exclusão lógica). | Valida a PK e executa UPDATE; nunca apaga o histórico. | Identifica o ID selecionado (pelo menu Ações) e envia requisição. |

**10\. Operações CRUD**

* **Create:** Ação do botão "Novo aluno".  
* **Read:** Carregamento inicial da página e uso da barra de busca.  
* **Update:** Ação "Editar" na listagem (altera um registro existente).  
* **Delete:** Ação "Excluir aluno" na listagem (exclusão lógica: `UPDATE alunos SET status = 'inativo'`).  

**11\. Dependências**

* **Outras páginas:** Nenhuma dependência direta, mas no futuro, a entidade alunos será dependente de tabelas associativas (ex: matrículas em turmas/disciplinas).  
* **Dependência de filtros:** O campo de busca atua como um filtro simples (apenas texto).  

**12\. Decisão sobre exclusão**

* A exclusão de aluno é **lógica**: o endpoint `DELETE /api/admin/alunos/excluir.php?id={id}` executa `UPDATE alunos SET status = 'inativo'`. O registro permanece para preservar presenças, grupos e notas históricas.
* As listagens exibem apenas alunos com `status = 'ativo'` por padrão; o parâmetro opcional `?incluir_inativos=1` permite consultar os inativos.
* Matrícula e e-mail continuam únicos mesmo para registros inativos; a reativação deve ser feita por edição.
* Alunos inativos não aparecem nas listas de seleção para novos grupos ou disciplinas, mas permanecem nos registros históricos de grupos, presenças e notas.

**13\. Critérios de conclusão**

* Tabela renderizando dados vindos dinamicamente do banco de dados (PHP/PDO).  
* Inserção de novo aluno refletindo automaticamente na listagem sem reload.  
* Validação de e-mail e matrícula duplicados funcionando e retornando mensagem clara ao usuário.  
* Funcionalidades de edição preenchendo o formulário com dados existentes e salvando as alterações com sucesso.  
* Exclusão lógica funcionando: o aluno deixa de aparecer na listagem padrão, mas o histórico permanece no banco.  

---

**Manutenção:** qualquer alteração de endpoint, campo, enum ou regra deve ser refletida também em `docs/contrato_api_frontend.md` e na seção 6.3 do documento técnico.
