> **Status:** especificação funcional de referência.
> **Leia antes:** `docs/README.md`, `docs/documentacao_de_contextualizacao_tecnica.md` e `docs/contrato_api_frontend.md`.
> **Fonte de verdade:** schema oficial em `/database`; catálogo de endpoints na seção 6.3; contrato JSON no documento de contrato. Os blocos SQL deste arquivo são ilustrativos e não devem ser copiados.

# **ESPECIFICAÇÃO TÉCNICA: GERENCIAMENTO DE PROFESSORES**

**1\. Identificação**

* **Nome da página:** Gerenciamento de Professores  
* **Rota:** /admin/professores  
* **Objetivo:** Permitir que a coordenação cadastre, consulte, edite e remova os professores da instituição.  
* **Perfil de usuário:** Coordenação (Admin)  
* **Páginas relacionadas:** Home (Seleção de Perfil)  

**2\. Descrição do funcionamento**

* **Apresentação:** A página principal exibe uma listagem em formato de tabela contendo os professores cadastrados. As colunas visíveis são: Checkbox (seleção), Nome (com avatar de iniciais e e-mail abaixo), Registro e Ações.  
* **Ações disponíveis:**  

  * Buscar professor pelo campo de texto.  
  * Cadastrar novo professor através do botão "+ Novo professor" (abre modal).  
  * Editar dados de um professor específico (abre modal com os dados preenchidos).  
  * Excluir um professor do sistema.  
  * **Resultado das ações:** Ao confirmar um cadastro, edição ou exclusão, a listagem de professores é atualizada dinamicamente na tela, refletindo o novo estado do banco de dados sem a necessidade de recarregar a página.  

**3\. Fluxo de navegação**

* **Origem:** Acesso pela área de coordenação (perfil Admin).  
* **Ações principais:** Clicar em "+ Novo professor", preencher Nome completo, E-mail e Registro, e submeter em "Cadastrar". A edição e exclusão ocorrem acionando o menu de reticências (...) na respectiva linha da tabela.  
* **Destinos:** A interação ocorre na mesma rota (/admin/professores), manipulando a abertura e o fechamento de modais sobrepostos à interface principal.  
* **Dependências:** No MVP não há autenticação real; o acesso é separado pelo prefixo `/api/admin` e não exige identificador. A limitação está na seção 6.3.15 do documento técnico.  

**4\. Funcionalidades identificadas**

* **F1 \- Listagem de Professores:** Exibição dos registros em tabela.  
* **F2 \- Busca de Professores:** Filtro textual simples (por nome ou registro).  
* **F3 \- Cadastro de Professor:** Modal contendo formulário de inserção.  
* **F4 \- Edição de Professor:** Modal preenchido para atualização de dados.  
* **F5 \- Exclusão de Professor:** Remoção do registro selecionado.  

**5\. Regras de negócio**

* **RN-001 \- Registro Único:** Não é permitido cadastrar ou alterar um professor informando um "Registro" (identificador funcional) que já pertença a outro professor no sistema.  
* **RN-002 \- E-mail Único:** Não é permitido haver dois professores com o mesmo endereço de e-mail.  
* **RN-003 \- Obrigatoriedade:** Os campos Nome completo, E-mail e Registro são de preenchimento obrigatório para a conclusão do cadastro ou edição.  

**6\. Banco de dados (Bloco A)**

* **Dados que precisam ser armazenados:** Informações cadastrais e de identificação do professor.  
* **Entidades:** professores  
* **Atributos:**  

  * id (INT, Auto Increment)  
  * nome\_completo (VARCHAR)  
  * email (VARCHAR)  
  * registro (VARCHAR \- tratado como string para aceitar possíveis zeros à esquerda ou letras).  
  * status (ENUM: 'ativo', 'inativo', default 'ativo')  
  * **PKs:** id  
* **FKs:** Nenhuma nesta visualização isolada.  
* **Relacionamentos / Tabelas associativas:** Não aplicável nesta etapa (futuramente se relacionará com disciplinas).  
* **Restrições:** Colunas registro e email devem possuir restrição UNIQUE. Todos os campos devem ser NOT NULL. status com default 'ativo' e índice para filtro de listagem.  

**7\. Lógica de programação / Backend (Bloco B)**

* **Operações necessárias:** Recebimento do payload, validação das restrições (campos únicos e obrigatórios), execução de comandos SQL via PDO e retorno padronizado.  
* **Endpoints sugeridos (padrão da seção 6.3):**  

  * GET `/api/admin/professores/listar.php` (retorna os ativos; aceita `?busca=` e `?incluir_inativos=1`).  
  * POST `/api/admin/professores/cadastrar.php` (insere novo professor).  
  * PUT `/api/admin/professores/editar.php?id={id}` (atualiza professor existente).  
  * DELETE `/api/admin/professores/excluir.php?id={id}` (exclusão lógica).  
  * POST `/api/admin/professores/excluir-lote.php` (corpo `{ "ids": number[] }`; exclusão lógica atômica em lote).  
  * **Parâmetros/Corpo (JSON):** `nome`, `email`, `registro` e `status` (opcional, usado para reativar). No banco, `nome` corresponde a `nome_completo`; o de-para completo está em `docs/contrato_api_frontend.md`.  
* **Respostas JSON:**  

  * *Sucesso:* `{"sucesso": true, "dados": [...]}` ou `{"sucesso": true, "mensagem": "Professor cadastrado."}`  
  * *Erro:* `{"sucesso": false, "mensagem": "Este registro já está cadastrado no sistema."}`  

**8\. Frontend React (Bloco C)**

* **Informações exibidas:** Tabela listando os dados recebidos. O React deverá possuir uma função auxiliar para extrair as iniciais de `nome` e gerar o avatar (ex: "Liliane Nascimento" gera "LN").  
* **Campos e formulários:** Apenas campos de texto padrão (Text Inputs) para Nome, E-mail e Registro.  
* **Estados da interface (State):**  

  * professores (Array com a listagem).  
  * modalVisivel (Booleano controlando a exibição do modal de cadastro/edição).  
  * dadosFormulario (Objeto com os dados digitados no momento).  
  * termoBusca (String para o filtro local ou via API).  
  * **Requisições:** Funções assíncronas (fetch ou axios) consumindo os endpoints descritos.  
* **Atualizações visuais:**  

  * Realizar o fechamento automático do modal em caso de resposta sucesso: true.  
  * Atualizar o estado da lista local ou refazer o GET para exibir os dados novos instantaneamente.  
  * Exibir feedback visual (toast/alert) para validações do backend (ex: erro de registro duplicado).  

**9\. Matriz de responsabilidade**

| Necessidade | Banco de Dados | Backend (PHP) | Frontend (React) |
| :---- | :---- | :---- | :---- |
| **Armazenar professor** | Executa a persistência (INSERT em professores). | Valida recebimento e executa comando PDO. | Captura inputs no formulário e envia o JSON via POST. |
| **Validar Registro único** | Garante integridade com constraint UNIQUE. | Consulta duplicação antes de salvar; retorna erro em JSON (RN-001). | Impede o fechamento do modal e exibe a mensagem de erro recebida. |
| **Listar professores** | Fornece os registros armazenados. | Processa o SELECT e devolve array formatado. | Consome a API, extrai as iniciais do nome para o avatar e renderiza as linhas. |
| **Excluir professor** | Altera o status para 'inativo' (exclusão lógica). | Recebe o id, valida a permissão e executa o UPDATE de status. | Aciona endpoint de DELETE enviando o ID e remove a linha da tabela local. |

**10\. Operações CRUD**

* **Create:** Preenchimento do modal "Novo professor" e submissão.  
* **Read:** Renderização da tabela e uso do campo "Buscar professor...".  
* **Update:** Seleção da opção "Editar" (no ícone de 3 pontos), que reaproveita o modal para enviar um PUT ao backend.  
* **Delete:** Seleção da opção "Excluir professor", que executa a exclusão lógica (`UPDATE professores SET status = 'inativo'`) e remove a linha da listagem padrão.  

**11\. Dependências**

* **Outras páginas/Entidades:** Por enquanto, atua de forma autônoma. No futuro, será uma entidade dependente/compartilhada ao configurar o vínculo com "Disciplinas" e "Cursos".  

**12\. Decisões e pendências**

* A exclusão individual é **lógica**: o endpoint `DELETE /api/admin/professores/excluir.php?id={id}` executa `UPDATE professores SET status = 'inativo'`, preservando o histórico de seminários e avaliações.
* As listagens exibem apenas professores com `status = 'ativo'` por padrão; o parâmetro opcional `?incluir_inativos=1` permite consultar os inativos.
* A exclusão em lote está **fora do escopo do MVP**. O checkbox de seleção pode ser removido ou desabilitado até que o endpoint seja especificado.
* Professores inativos não aparecem nas listas de seleção para novas disciplinas, mas permanecem vinculados aos seminários históricos.
* Registro e e-mail continuam únicos mesmo para registros inativos; a reativação deve ser feita por edição.

**13\. Critérios de conclusão**

* Listagem populada a partir do backend.  
* Possibilidade de adicionar um professor e vê-lo refletido na lista instantaneamente.  
* Tentativas de inserir registros ou e-mails repetidos sendo bloqueadas com mensagens de feedback claras.  
* Edição atualizando os dados corretamente e exclusão lógica removendo o professor da listagem padrão sem apagar o histórico.  

---

**Manutenção:** qualquer alteração de endpoint, campo, enum ou regra deve ser refletida também em `docs/contrato_api_frontend.md` e na seção 6.3 do documento técnico.
