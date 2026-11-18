> **Status:** especificação funcional de referência.
> **Leia antes:** `docs/README.md`, `docs/documentacao_de_contextualizacao_tecnica.md` e `docs/contrato_api_frontend.md`.
> **Fonte de verdade:** schema oficial em `/database`; catálogo de endpoints na seção 6.3; contrato JSON no documento de contrato. Os blocos SQL deste arquivo são ilustrativos e não devem ser copiados.

# **ESPECIFICAÇÃO TÉCNICA: APRESENTAÇÃO, AVALIAÇÃO E RESULTADOS (ETAPA 3\)**

## **1\. Identificação**

* **Nome das telas:** Apresentação ao vivo & Painel de Resultados  
* **Rotas (URLs):**

  * /professor/seminarios/{id}/apresentar (Sessão ao vivo)  

  * /professor/seminarios/{id} (Aba "Resultados" liberada pós-conclusão)

* **Componentes Principais:** Sidebar de Grupos, Cronômetro Regressivo, Formulário Dinâmico de Rubrica, Dashboard de Ranking (Cartões de Pódio e Barras de Progresso).  

* **Objetivo:** Executar o seminário controlando o tempo, coletar as notas do professor (em grupo e individuais) e exibir o cálculo consolidado do ranking final.  

* **Perfil de usuário:** Professor

## **2\. Descrição do funcionamento**

* **Pré-requisitos:** O botão "Apresentar" na Etapa 2 só é habilitado se houver \>= 1 grupo na data ativa e se a lista de chamada daquela data já estiver salva.  

* **Apresentação ao vivo:** O professor seleciona o grupo na lateral esquerda. O cronômetro no topo é independente. O formulário abaixo se molda automaticamente aos critérios definidos na Etapa 1: critérios "Em Grupo" geram 1 input de nota (0 a 10\) geral; critérios "Individualmente" geram uma lista com o nome de cada aluno do grupo e 1 input de nota para cada. O professor redige o feedback e clica em "Salvar avaliação".  

* **Encerramento:** Ao clicar em "Encerrar apresentação", a sessão é fechada e o professor é redirecionado para a aba "Resultados".  

* **Resultados:** Exibe os cartões de pódio (Top 3\) baseados na Média Geral de cada grupo. O detalhamento abaixo mostra as parciais do professor e as parciais da turma (peso 60/40), a nota individual final de cada membro e os feedbacks coletados.

## **3\. Fluxo de navegação**

* **Origem:** Botão "Apresentar" no cabeçalho da configuração do seminário (Etapa 2).  

* **Destinos:** Após clicar em "Encerrar apresentação", o fluxo retorna para a tela da Etapa 2, mas com o *Status da Data* alterado para "Concluída", revelando automaticamente a aba "Resultados".  

## **4\. Funcionalidades identificadas**

* **F1 \- Cronômetro com Estado (Timer):** Lógica regressiva baseada no "Tempo por apresentação" definido na Etapa 1\.  

* **F2 \- Formulário Master-Detail Flexível:** Renderização de inputs baseada no tipo do critério (1 input por grupo vs. N inputs por integrantes).  

* **F3 \- Motor de Cálculo (Backend):** Algoritmo que recebe as notas do professor e as notas parciais dos alunos (mockadas ou reais), aplica os pesos configurados (ex: 60/40) e devolve a nota final de cada indivíduo e a média global do grupo.  

* **F4 \- Dashboard Dinâmico (Resultados):** Interface de leitura rica em UI (badges, barras de progresso, ícones de troféu) para consumo estruturado do JSON de resultados.

## **5\. Regras de negócio**

* **RN-001 \- Status do Grupo:** Ao salvar as notas de um grupo, a badge lateral deve mudar de "Pendente" para "Avaliado" (e o contador superior 0/3 avaliados deve incrementar). O professor pode re-selecionar um grupo "Avaliado" e editar as notas enquanto a sessão não for encerrada.  

* **RN-002 \- Trava de Encerramento:** O botão "Encerrar apresentação" idealmente deve avisar ou bloquear a conclusão se ainda houver grupos com status "Pendente" naquela data.  

* **RN-003 \- Cálculo da Nota Individual Final:** para cada aluno `s` de um grupo `g`, o backend deve calcular a nota em três etapas:

  1. **Nota do professor (`T_s`):** média ponderada das notas do professor nos critérios do seminário, dividida por 100:

     `T_s = (Σ nota_prof(c, s) × peso_c) / 100`

     Em critérios do tipo `grupo`, `nota_prof(c, s)` é a nota única atribuída ao grupo. Em critérios do tipo `individual`, é a nota atribuída ao aluno `s`.

  2. **Nota dos alunos (`P_s`):** média aritmética das notas normalizadas de todos os alunos avaliadores elegíveis:

     `P_s = (1 / n) × Σ_avaliadores [ (Σ nota_avaliador(c, s) × peso_c) / 100 ]`

     `n` é o número de alunos presentes que não integram o grupo avaliado. Em critérios de grupo, a nota do avaliador vale para todos os integrantes do grupo.

  3. **Nota individual final (`F_s`):**

     `F_s = T_s × (peso_professor / 100) + P_s × (peso_alunos / 100)`

* **RN-004 \- Média do Grupo:** a Média Geral do Grupo (usada no Ranking) é a média aritmética simples das Notas Individuais Finais dos seus integrantes:

  `M_g = (1 / |G|) × Σ F_s`

* **RN-005 \- Arredondamento e ausência de avaliadores:** o cálculo deve manter a precisão total e arredondar para duas casas decimais apenas na resposta JSON. Se não houver aluno avaliador elegível (`n = 0`), o backend deve usar `P_s = T_s` e sinalizar essa condição na resposta, evitando divisão por zero em turmas com um único grupo.

## **6\. Banco de dados (Bloco A)**

* **Dados que precisam ser armazenados:** Notas brutas (por critério e por integrante) e feedbacks.  

* **Atenção Arquitetural:** Para suportar a avaliação peer-to-peer (alunos) no futuro, as tabelas de notas precisam identificar o avaliador\_id.

**Entidades e Atributos Sugeridos:**

* **Tabela** avaliacoes\_notas**:**  

  * id (INT, PK)  
  * tipo\_avaliador (ENUM: 'professor', 'aluno')   

  * seminario\_data\_id (INT, FK)  

  * grupo\_id (INT, FK)  

  * avaliador\_id (INT, FK \- ID do avaliador informado na requisição, professor ou aluno)  

  * aluno\_avaliado\_id (INT, FK \- NULL se for um critério "Em Grupo")  

  * criterio\_id (INT, FK)  

  * nota (DECIMAL 4,2 \- Ex: 9.5)  

* **Tabela** avaliacoes\_feedbacks**:**  

    * id (INT, PK)

  * tipo\_avaliador (ENUM: 'professor', 'aluno')  

  * seminario\_data\_id (INT, FK)  

  * grupo\_id (INT, FK)  

  * avaliador\_id (INT, FK)  

  * feedback\_texto (TEXT)  

## **7\. Lógica de programação / Backend (Bloco B)**

> **Identidade no MVP:** todos os endpoints desta seção exigem `?professor_id={id}`. Não há autenticação real; a limitação está registrada na seção 6.3.15 do documento técnico. Os caminhos abaixo seguem o padrão `/api/professor/...` definido na seção 6.3.
>
> **Contrato JSON:** a avaliação é enviada como `{ "notas": [{ "criterioId": 1, "alunoId": null, "nota": 9.0 }], "feedback": "..." }`; a resposta de resultados segue o `ResultadoDTO`. O de-para completo está em `docs/contrato_api_frontend.md`.

* **Endpoints Estratégicos:**  

  * `POST /api/professor/apresentacao/iniciar.php?professor_id={id}&data_id={id}`: Marca a sessão como em andamento quando o professor clica em "Apresentar".  

  * `GET /api/professor/apresentacao/carregar.php?professor_id={id}&data_id={id}`: Retorna o cronograma (grupos e alunos) e a rubrica (critérios) montando o payload necessário para o front-end desenhar a tela de avaliação.  

  * `POST /api/professor/apresentacao/salvar-avaliacao.php?professor_id={id}&data_id={id}&grupo_id={id}`: Recebe `{ "notas": [{ "criterioId": 1, "alunoId": null, "nota": 9.0 }], "feedback": "..." }`. O PHP deve usar **Transação PDO** para fazer um *UPSERT* (inserir se não existe, atualizar se já existe) de todas essas parciais de nota de uma vez e atualizar `grupos.status` para `avaliado`.  

  * `POST /api/professor/apresentacao/encerrar.php?professor_id={id}&data_id={id}`: Atualiza o status daquela data específica na tabela seminario\_datas para concluida.  

  * `GET /api/professor/resultados/compilar.php?professor_id={id}&data_id={id}`: Endpoint analítico que executa o motor de cálculo (RN-003 e RN-004). O PHP realiza as junções, aplica as porcentagens, ordena o ranking por média geral decrescente e devolve o JSON estruturado contendo o Ranking Top 3 e a lista completa de detalhamento.

* **Geração de Mock de Alunos (MVP):** Ao salvar a avaliação do professor no endpoint `POST /api/professor/apresentacao/salvar-avaliacao.php?professor_id={id}&data_id={id}&grupo_id={id}`, o backend deve garantir a existência das avaliações simuladas dos alunos.

    * Identificar os alunos com `presencas.presente = 1` na `seminario_data_id`.
    * Excluir os integrantes do grupo que está sendo avaliado.
    * Para cada aluno elegível (avaliador), para cada critério do seminário:
      * Critério `grupo`: inserir uma nota com `aluno_avaliado_id = NULL`.
      * Critério `individual`: inserir uma nota para cada integrante do grupo.
    * Gerar notas aleatórias entre 7,00 e 10,00.
    * Usar `tipo_avaliador = 'aluno'` e `avaliador_id` igual ao aluno elegível.
    * Executar apenas para registros inexistentes, sem sobrescrever avaliações reais ou já geradas. O mock deve ser idempotente.
    * Se não houver aluno elegível, não gerar registros e aplicar a regra RN-005.
    * Toda a operação deve ocorrer dentro da mesma transação da avaliação do professor.

## **8\. Frontend React (Bloco C)**

* **Estados da Interface (States \- Apresentação):**

  * grupoSelecionadoId (Controla qual formulário exibir na direita).  

  * timer (State numérico dos segundos restantes, manipulado por setInterval).  

  * formAvaliacao (Objeto centralizando os inputs de todos os critérios e do feedback antes do submit).  

* **Estados da Interface (States \- Resultados):**

  * Consome o JSON do backend sem alterar dados. State focado em renderização condicional (Ex: renderizar o "Pódio" no topo e o .map() dos cards detalhados em seguida).  

* **Dinâmica Visual:**  

  * **Cronômetro:** O botão "Iniciar" dispara um useEffect de contagem regressiva. "Pausar" dá um clearInterval. "Reiniciar" reseta o estado para os minutos originais do seminário.  

  * **Barras de Progresso (Resultados):** A barra que mostra os pesos (ex: 60% e 40%) pode ser implementada utilizando o valor do banco atrelado a uma propriedade style={{ width: '60%' }} para preenchimento visual dinâmico usando Tailwind ou CSS nativo.  

## **9\. Matriz de responsabilidade**

| Necessidade | Banco de Dados | Backend (PHP) | Frontend (React) |
| :---- | :---- | :---- | :---- |
| **Inputs Flexíveis** | Fornece os metadados dos critérios (tipo, peso). | Retorna JSON indicando se o critério é Grupo ou Individual. | Gera .map() iterando apenas 1 campo ou N campos (por aluno) baseado no tipo. |
| **Edição pós-salvamento** | Utiliza PKs e UNIQUE KEYs. | Executa INSERT ON DUPLICATE KEY UPDATE ou valida existência e faz UPDATE. | Mantém o formulário preenchido caso o professor clique em um grupo já "Avaliado". |
| **Cálculo de Médias** | Armazena Notas Brutas de 0 a 10\. | **Regra Crucial:** O PHP assume a carga matemática, aplicando as contas de peso e agrupando o ranking. | Apenas recebe a "Nota Final Individual" e "Média Geral" prontas e exibe em tela. |
| **Liberar Aba Resultados** | Coluna status em seminario\_datas \= 'concluida'. | Confirma no GET o status da sessão. | Habilita a 4ª aba condicionalmente com base no status lido do backend. |

## **10. Decisões e pendências**

* **Mock de Notas dos Alunos — decidido:** o mock é completo e idempotente, conforme a seção 7. Não é mais uma pendência.
* **Persistência das avaliações reais dos alunos:** a estrutura de `avaliacoes_notas` já suporta `tipo_avaliador = 'aluno'`; a tela do aluno será implementada em etapa futura, substituindo gradualmente o mock.

---

**Manutenção:** qualquer alteração de endpoint, campo, enum ou regra deve ser refletida também em `docs/contrato_api_frontend.md` e na seção 6.3 do documento técnico.

