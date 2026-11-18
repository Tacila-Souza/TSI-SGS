> **Status:** especificação funcional de referência.
> **Leia antes:** `docs/README.md`, `docs/documentacao_de_contextualizacao_tecnica.md` e `docs/contrato_api_frontend.md`.
> **Fonte de verdade:** schema oficial em `/database`; catálogo de endpoints na seção 6.3; contrato JSON no documento de contrato. Os blocos SQL deste arquivo são ilustrativos e não devem ser copiados.

# **ESPECIFICAÇÃO TÉCNICA: CRIAÇÃO E CONFIGURAÇÃO DE SEMINÁRIO (ETAPA 1\)**

## **1\. Identificação**

* **Nome da página:** Criação de Seminário (Escopo Inicial)  

* **Rota (URL):** /professor/seminarios/novo  

* **Componentes Principais:** Página dividida entre o formulário principal (esquerda) e um card de ações "Próximos passos" (direita).  

* **Objetivo:** Permitir ao professor cadastrar as informações vitais, cronograma básico, tempo de duração e rubrica de notas de um novo seminário.  

* **Perfil de usuário:** Professor  

* **Páginas relacionadas:** Dashboard (/professor/seminarios) e Etapa 2 de Configuração (Formação de Grupos \- Futuro).

## **2\. Descrição do funcionamento**

* **Apresentação:** Uma interface robusta com 4 blocos no formulário: (A) Informações principais, (B) Datas de apresentação, (C) Composição e Cálculo da Nota Final e (D) Critérios de avaliação. Um card lateral exibe o botão de ação principal.  

* **Ações disponíveis:**  

  * Selecionar Curso e ver a lista de Disciplinas ser filtrada automaticamente.  

  * Informar o tempo previsto por apresentação em minutos.  

  * Adicionar ou remover dinamicamente novas linhas de Data/Horário de apresentação.  

  * Preencher o peso do Professor (fazendo o front-end calcular o do aluno para fechar em 100%).  

  * Adicionar ou remover dinamicamente critérios de avaliação preenchendo Nome, Peso (%) e Tipo.  

* **Resultado das ações:** Ao clicar em "Criar e agendar seminário", o formulário valida se os pesos somam 100% e envia os dados agrupados (payload JSON) ao backend, que salva o seminário, suas datas e seus critérios atrelados ao `professor_id` informado na requisição.

## **3\. Fluxo de navegação**

* **Origem:** Acesso através do botão "Novo Seminário" na listagem /professor/seminarios.  

* **Destinos:** Após salvar com sucesso, o sistema redireciona o professor para a próxima etapa (organizar grupos e chamada) do seminário recém-criado, que recebe o status inicial "pendente" aguardando a configuração dos alunos.

## **4\. Funcionalidades identificadas**

* **F1 \- Dropdown Encadeado:** Consumo dinâmico de cursos e disciplinas.  

* **F2 \- Multiplicador de Datas:** Campos dinâmicos adicionáveis para o cronograma (mínimo 1).  

* **F3 \- Balanceamento Automático de Pesos (Composição):** Lógica de interface que garante peso do professor \+ peso dos alunos \= 100%.  

* **F4 \- Validador de Soma de Critérios:** Cálculo em tempo real que soma os pesos de todos os critérios criados e preenche uma barra de progresso visual (Total dos pesos).  

* **F5 \- Gravação Complexa (Master-Detail):** Envio de dados estruturados para múltiplas tabelas.

## **5\. Regras de negócio**

* **RN-001 \- Preenchimento Completo:** O formulário só pode ser submetido se as informações principais estiverem preenchidas e houver no mínimo 1 data e 1 critério configurado.  

* **RN-002 \- Regra dos 100% (Critérios):** O botão de submissão só deve realizar a requisição HTTP se a soma de todos os campos "Peso (%)" inseridos na seção de Critérios for exatamente igual a 100\.  

* **RN-003 \- Filtro de Disciplinas:** Uma disciplina só pode ser selecionada após a escolha prévia de um curso válido.  

* **RN-004 \- Status Automático (Pendente):** O backend deve forçar o status inicial do registro como "pendente" no banco de dados.

## **6\. Banco de dados (Bloco A)**

* **Dados que precisam ser armazenados:** Os dados mestre do seminário e suas listas detalhadas (datas e critérios).

**Entidades e Atributos:**

* **Tabela** seminarios**:**  
  * id (INT, PK)  

  * disciplina\_id (INT, FK)  

  * professor\_id (INT, FK \- ID do professor informado na requisição)  

  * titulo (VARCHAR)  

  * descricao (TEXT)  

  * tempo\_apresentacao (INT)  

  * peso\_professor (INT)  

  * peso\_alunos (INT)  

  * status (VARCHAR, default 'pendente')  

* **Tabela** seminario\_datas **(1:N):**  

  * id (INT, PK)  

  * seminario\_id (INT, FK)  

  * data\_apresentacao (DATE)  

  * horario (TIME)  

* **Tabela** seminario\_criterios **(1:N):**  

  * id (INT, PK)  

  * seminario\_id (INT, FK)  

  * nome\_criterio (VARCHAR)  

  * peso\_criterio (INT)  

  * tipo\_avaliacao (ENUM: 'individual', 'grupo')  

## **7\. Lógica de programação / Backend (Bloco B)**

> **Identidade no MVP:** todos os endpoints desta seção exigem `?professor_id={id}`. Não há autenticação real; a limitação está registrada na seção 6.3.15 do documento técnico. Os caminhos abaixo seguem o padrão `/api/professor/...` definido na seção 6.3.
>
> **Contrato JSON:** os nomes de campos do payload seguem `docs/contrato_api_frontend.md` (`turmaId`, `titulo`, `descricao`, `tempoApresentacaoMin`, `pesoProfessor`, `datas` e `criterios`). Os nomes de colunas abaixo são do banco. O status inicial `pendente` é entregue ao frontend como `rascunho`.

* **Endpoints e Processamento:**  

  1. `GET /api/professor/cursos/listar.php?professor_id={id}` e `GET /api/professor/disciplinas/listar.php?professor_id={id}&curso_id={id}` (para os selects).  

  2. `POST /api/professor/seminarios/cadastrar.php?professor_id={id}` (recebe o JSON com objeto principal e os arrays de datas e critérios).  

* **Validação Backend:** O PHP deve somar os pesos recebidos no array de critérios e retornar erro 400 Bad Request se a soma for diferente de 100\.  

* **Regra Crítica (TRANSAÇÃO PDO):** Como os dados vão para 3 tabelas diferentes, o PHP deve obrigatoriamente usar \$pdo-\>beginTransaction(). O fluxo é:  

  1. Fazer INSERT na tabela seminarios.  

  2. Pegar o ID gerado (lastInsertId()).  

  3. Fazer loop no array de datas inserindo em seminario\_datas.  

  4. Fazer loop no array de critérios inserindo em seminario\_criterios.  

  5. Finalizar com \$pdo-\>commit(). Se falhar, acionar \$pdo-\>rollBack().  

## **8\. Frontend React (Bloco C)**

* **Estados da Interface (States):**  

  * cursoId e disciplinaId.  

  * titulo, descricao, tempoApresentacaoMin (inteiro).  

  * datasApresentacao (Array de objetos com data e hora).  

  * criteriosAvaliacao (Array de objetos com nome, peso e tipo; critério não possui descrição).  

  * pesoProf e pesoAlunos (Inteiros de 0 a 100 no formulário; o payload envia `pesoProfessor` de 0 a 1).  

* **Dinâmica Visual:**

  * **Input de Peso (Regra 100% \- Composição):** O evento onChange do peso do professor seta o estado para "X" e o peso dos alunos para "100 \- X". A barra "Total da composição" exibe visualmente 100% / 100%.  

  * **Validadores Visuais de Critérios:** Sempre que um critério for adicionado, editado (alterando o peso) ou deletado (lixeira), um useEffect ou função memoizada deve somar os pesos totais e refletir na barra "Total dos pesos". Se a soma for diferente de 100%, a barra deve indicar o erro visualmente e o botão "Criar e agendar seminário" deve ser desabilitado (disabled={true}).

## **9\. Matriz de responsabilidade**

| Necessidade | Banco de Dados | Backend (PHP) | Frontend (React) |
| :---- | :---- | :---- | :---- |
| **Garantir Soma 100% (Critérios)** | Armazena os inteiros de cada critério. | Valida se a soma dos critérios é exatamente 100 antes de gravar. | Soma os inputs em tempo real, preenche a barra de progresso e desabilita o botão se \!= 100\. |
| **Filtro Curso/Disciplina** | Representa o relacionamento FK. | Consulta e retorna JSON com disciplinas via GET. | Dispara novo fetch ao alterar curso. |
| **Gravação Múltipla** | Tabelas filhas recebendo FK do seminário mestre. | Realiza múltiplos INSERTs dentro de uma **Transação PDO**. | Controla os arrays localmente e envia o Payload formatado. |
| **Tempo de Apresentação** | Armazena um INT (minutos). | Recebe no payload e insere no BD. | Input de número simples na interface principal. |

---

**Manutenção:** qualquer alteração de endpoint, campo, enum ou regra deve ser refletida também em `docs/contrato_api_frontend.md` e na seção 6.3 do documento técnico.

