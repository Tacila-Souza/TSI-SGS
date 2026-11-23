> **Status:** especificação funcional de referência.
> **Leia antes:** `README.md`, `docs/documentacao_de_contextualizacao_tecnica.md` e `docs/contrato_api_frontend.md`.
> **Fonte de verdade:** schema oficial em `/database`; catálogo de endpoints na seção 6.3; contrato JSON no documento de contrato. Os blocos SQL deste arquivo são ilustrativos e não devem ser copiados.

# **ESPECIFICAÇÃO TÉCNICA: CONFIGURAÇÃO DE SEMINÁRIO (ETAPA 2\)**

## **1\. Identificação**

* **Nome da página:** Configuração Complementar (Grupos, Chamada e Critérios)  

* **Rota (URL):** /professor/seminarios/{id}

* **Componentes Principais:** Cabeçalho com ações globais, Seletor de Data de Sessão, Cards de Resumo, Menu de Abas (Grupos, Presença, Critérios) e Modais de edição.  

* **Objetivo:** Permitir ao professor gerenciar as sessões do seminário por data, formando as equipes de apresentação, realizando o sorteio de ordem, registrando a chamada e iniciando a apresentação.  

* **Perfil de usuário:** Professor  

* **Páginas relacionadas:** Escopo Inicial (Etapa 1\) e Tela de Apresentação (Ao vivo).

## **2\. Descrição do funcionamento**

* **Apresentação:** A interface carrega os detalhes do seminário com base no {id} da URL. O professor utiliza um *Select* no topo para escolher qual "Data agendada" deseja gerenciar. A alteração dessa data recarrega os cards de resumo e o conteúdo das abas inferiores (Grupos e Presença) para refletir apenas os dados daquela sessão específica.  

* **Ações disponíveis:**

  * **Globais:** Editar os dados gerais (volta para a Etapa 1), Excluir o seminário completo, ou iniciar a sessão (Apresentar).  

  * **Aba Grupos:** Criar novos grupos informando o Tema e marcando os alunos. Editar/Deletar grupos existentes. Clicar no botão "Sortear Ordem de Apresentação" para embaralhar os grupos criados.  

  * **Aba Presença:** Marcar checkboxes na lista de chamada independente dos grupos e salvar a frequência do dia.  

  * **Aba Critérios:** Visualizar a rubrica de avaliação em formato somente leitura.  

* **Resultado das ações:** As modificações de grupos e presenças são salvas no banco de dados e refletidas nos contadores dos cards de resumo. O botão "Apresentar" só deve ser liberado se o status permitir e a configuração da data atual estiver concluída.

## **3\. Fluxo de navegação**

* **Origem:** Redirecionamento automático após salvar a Etapa 1 ou acesso via listagem do painel /professor/seminarios.  

* **Destinos:** Após a configuração, o professor clica em "Apresentar" para ser levado à tela de execução do seminário ao vivo.

## **4\. Funcionalidades identificadas**

* **F1 \- Gestão por Data (Seletor Global):** Isolamento de escopo de grupos e presenças atrelados à data selecionada no *dropdown* superior.  

* **F2 \- Dashboard de Resumo Dinâmico:** Cards superiores que atualizam dados de agendamento e totalizadores de presença reativamente.  

* **F3 \- CRUD de Grupos:** Inserção, edição e exclusão de equipes com verificação de exclusividade na data ativa.  

* **F4 \- Sorteador (Randomizer):** Algoritmo/ação que embaralha e salva a ordem de apresentação (ordem\_apresentacao) exibindo *badges* indicativos (ex: "1º a apresentar").  

* **F5 \- Registro de Presença em Lote:** Salvamento simultâneo do status de presença da turma para a data ativa, com contador em tempo real no front-end.

## **5\. Regras de negócio**

* **RN-001 \- Exclusividade de Grupo:** Um aluno não pode estar em dois grupos diferentes *na mesma data de apresentação*. Ao abrir o modal de novo grupo, alunos já vinculados a outros grupos daquela data não devem aparecer na lista (ou devem estar desabilitados).  

* **RN-002 \- Desacoplamento de Chamada:** A presença na chamada não afeta a pertinência ao grupo. O aluno pode pertencer ao grupo X, mas receber falta na lista de presença.  

* **RN-003 \- Permissão de Avaliação (Trava Futura):** Somente alunos marcados como presentes na chamada da respectiva data receberão acesso à tela de avaliação por pares. O sistema bloqueará a autoavaliação (avaliar o próprio grupo).  

* **RN-004 \- Mudança de Status:** Ao concluir o registro do primeiro grupo ou ao salvar a primeira chamada, o status do seminário passa para agendado.  

* **RN-005 \- Elegibilidade para avaliação por pares:** o aluno só pode avaliar se estiver em `presencas` com `presente = 1` para o `seminario_data_id` atual. Se não estiver, o acesso é bloqueado. O aluno também não pode avaliar o próprio grupo: se ele pertence ao `grupo_id` que está com status "em apresentação", a avaliação é bloqueada por conflito de interesse.

## **6\. Banco de dados (Bloco A)**

* **Dados que precisam ser armazenados:** Grupos, alunos vinculados aos grupos e lista de presença.  
* **Atenção Arquitetural:** As Foreign Keys (FKs) aqui apontam para a tabela seminario\_datas (o ID da sessão/dia específico) e não diretamente para seminarios.  

**Entidades e Atributos:**

* **Tabela** grupos**:**

  * id (INT, PK)  

  * seminario\_data\_id (INT, FK referenciando a data específica da apresentação)  

  * tema (VARCHAR)  

  * ordem\_apresentacao (INT, NULL \- preenchido após sorteio)  

  * status (ENUM: 'pendente', 'em_apresentacao', 'avaliado', default 'pendente')  

* **Tabela Associativa** grupo\_alunos **(N:M):**  

  * grupo\_id (INT, FK)  

  * aluno\_id (INT, FK)  

  * *PK Composta:* (grupo\_id, aluno\_id)  

* **Tabela** presencas**:**  

  * id (INT, PK)  

  * seminario\_data\_id (INT, FK referenciando a data específica da apresentação)  

  * aluno\_id (INT, FK)  

  * presente (BOOLEAN/TINYINT, default 0\)

## **7\. Lógica de programação / Backend (Bloco B)**

> **Identidade no MVP:** todos os endpoints desta seção exigem `?professor_id={id}`. Não há autenticação real; a limitação está registrada na seção 6.3.15 do documento técnico. Os caminhos abaixo seguem o padrão `/api/professor/...` definido na seção 6.3.
>
> **Contrato JSON:** o sorteio recebe `{ "gruposIds": [1, 2, 3] }`; a chamada em lote recebe `{ "presencas": [{ "alunoId": 1, "presente": true }] }`. O DTO de grupo e o de sessão estão em `docs/contrato_api_frontend.md`.

* **Endpoints Estratégicos:**  

  * `GET /api/professor/seminarios/detalhes.php?professor_id={id}&id={id}`: Retorna o JSON contendo os dados gerais do seminário, as datas disponíveis (para o *Select*) e a configuração dos grupos, alunos e critérios para popular a tela inicial.  

  * `GET /api/professor/alunos/listar.php?professor_id={id}&disciplina_id={id}`: Busca os alunos ativos matriculados para popular a aba de Grupos e a Lista de Presença.  

  * `POST /api/professor/grupos/cadastrar.php?professor_id={id}&data_id={id}`: Recebe `{ "tema": "...", "integrantesIds": [1, 2] }`.

    * **Validação Backend:** Fazer um SELECT verificando se algum aluno\_id recebido já consta na tabela grupo\_alunos atrelado a um grupo do mesmo seminario\_data\_id. Se sim, retornar erro 400.  

    * **Transação PDO:** Inserir o grupo, recuperar o ID gerado, inserir alunos na tabela pivô e fazer *commit*.  

  * `POST /api/professor/grupos/sortear.php?professor_id={id}&data_id={id}`: Recebe `{ "gruposIds": [1, 2, 3] }` e realiza múltiplos UPDATE em `grupos.ordem_apresentacao`.  

  * `POST /api/professor/presencas/salvar.php?professor_id={id}&data_id={id}`: Gravação em lote da chamada do dia recebendo `{ "presencas": [{ "alunoId": 1, "presente": true }] }`.  

## **8\. Frontend React (Bloco C)**

* **Estados da Interface (States):**

  * dataSelecionadaId (Controla qual Sessão/Data dita os dados renderizados abaixo).  

  * abaAtiva (Grupos, Presença, Critérios).  

  * alunosDaDisciplina (Array global populado no Mount).  

  * gruposDaData (Array contendo os grupos da dataSelecionadaId).  

  * listaPresenca (Objeto ou Array de controle dos *checkboxes* ativos).

* **Dinâmica Visual:**  

  * **Reload Local (Select de Data):** A alteração do dropdown "Data agendada" deve disparar um novo filtro local nos dados já em memória ou um novo *fetch* na API para atualizar os Cards e as Abas inferiores.  

  * **Contador de Presença:** O texto "X presença(s) confirmada(s)" é atualizado a cada clique nos *checkboxes* calculando listaPresenca.filter(p \=\> p \=== true).length.  

  * **Sorteio:** Ao clicar em "Sortear", o React embaralha a array de grupos (Math.random()), reordena visualmente os *Cards* inserindo as *badges* "1º a apresentar" e envia a nova ordem para o backend.

## **9\. Matriz de responsabilidade**

| Necessidade | Banco de Dados | Backend (PHP) | Frontend (React) |
| :---- | :---- | :---- | :---- |
| **Isolamento por Data** | FKs de Grupos e Presenças apontam para seminario\_datas\_id. | Retorna os dados aninhados ou filtrados por data\_id. | O Seletor de Data dita qual fatia dos dados será renderizada nos *Cards* e Abas. |
| **Exclusividade no Grupo** | Sem restrição física direta no banco (lógica). | Valida no POST se o aluno já tem grupo naquela sessão. | Filtra e remove alunos já alocados do *Modal* de novos grupos. |
| **Sorteio de Ordem** | Armazena o inteiro na coluna ordem\_apresentacao. | Atualiza os registros via UPDATE em massa. | Habilita botão se grupos.length \>= 2. Embaralha, exibe as *badges* ordenadas e envia. |
| **Lista de Presença** | Armazena um booleano de presença por aluno/data. | Realiza UPSERT (Insert on Duplicate Key Update) em lote. | Controla os *checkboxes* múltiplos e atualiza o totalizador do rodapé. |

---

**Manutenção:** qualquer alteração de endpoint, campo, enum ou regra deve ser refletida também em `docs/contrato_api_frontend.md` e na seção 6.3 do documento técnico.

