# **DOCUMENTAÇÃO DE CONTEXTUALIZAÇÃO TÉCNICA**

## **Análise de funcionalidades e integração entre Frontend Next.js/React, Backend PHP e Banco de Dados**

> **Status:** documento canônico de contexto técnico. Antes de implementar, leia `docs/README.md`, `docs/AGENTS.md` e `docs/contrato_api_frontend.md`.
>
> **Arquitetura aprovada:** backend PHP híbrido + contrato JSON. CRUD simples pode ter SQL PDO no próprio endpoint; fluxos complexos ficam em `src/dominio/`; consultas compartilhadas em `src/consultas/`; a serialização dos DTOs fica em `src/contrato/`.
>
> **Fonte de verdade do banco:** `database/01_schema_ddl.sql`. Os blocos SQL das specs são ilustrativos e não devem ser copiados.

## **1\. Objetivo do documento**

Este documento apresenta o contexto técnico e as diretrizes que devem orientar a análise, especificação e implementação das funcionalidades do sistema.

O objetivo é estabelecer um padrão para que cada integrante consiga analisar uma página ou funcionalidade do protótipo, identificar seus comportamentos e documentar as necessidades de desenvolvimento de maneira organizada e consistente.

A análise deverá resultar em uma separação clara entre três responsabilidades fundamentais:

1. **Banco de dados:** informações que precisam ser armazenadas e relacionamentos necessários.  
2. **Lógica de programação / Backend:** regras de negócio, validações, processamento e operações realizadas pelo sistema.  
3. **Frontend React:** apresentação das informações, interações, estados da interface e comunicação com o backend.

Essa divisão deverá aparecer explicitamente na documentação de cada página ou funcionalidade.

O propósito não é apenas identificar o que precisa ser desenvolvido, mas compreender onde cada responsabilidade deve ser implementada.

## **2\. Contexto tecnológico**

A arquitetura prevista para o sistema utiliza:

* **Frontend:** Next.js/React com TypeScript.  
* **Backend:** PHP 8+ puro, sem utilização de frameworks.  
* **Acesso ao banco:** PDO com prepared statements.  
* **Banco de dados:** MySQL 8.0.13 ou superior; o ambiente local usa MySQL 8.0.44.  
* **Comunicação:** requisições HTTP entre React e PHP.  
* **Formato de comunicação:** JSON.

### **2.1. Responsabilidades gerais**

**Frontend — Next.js/React**

Responsável por:

* Apresentar páginas, componentes e informações.  
* Exibir formulários, tabelas, listas, filtros e elementos de navegação.  
* Capturar os dados informados pelos usuários.  
* Controlar estados e interações da interface.  
* Realizar requisições HTTP ao backend utilizando `fetch()`.  
* Enviar identificadores e dados necessários às operações.  
* Exibir mensagens de sucesso, erro e validação retornadas pelo backend.  
* Atualizar a interface após operações de cadastro, edição, exclusão ou consulta.

**Backend — PHP**

Responsável por:

* Receber requisições HTTP.  
* Interpretar os dados enviados pelo frontend.  
* Validar os dados recebidos.  
* Aplicar as regras de negócio.  
* Verificar permissões e condições para execução das operações.  
* Executar consultas e operações no banco utilizando PDO.  
* Coordenar operações que envolvam múltiplas entidades.  
* Retornar respostas estruturadas em JSON.

**Banco de dados**

Responsável por:

* Armazenar informações persistentes do sistema.  
* Organizar entidades, atributos e relacionamentos.  
* Garantir integridade referencial.  
* Aplicar restrições de unicidade e obrigatoriedade.  
* Representar os vínculos entre os registros.  
* Permitir consultas necessárias ao funcionamento das funcionalidades.

## **3\. Separação obrigatória das responsabilidades**

Toda análise deverá apresentar uma divisão entre banco de dados, lógica de programação e frontend.

Essa separação deve ser feita considerando o comportamento real da funcionalidade, e não apenas os elementos visíveis no protótipo.

### **3.1. Banco de dados — o que precisa ser armazenado?**

Nesta parte, devem ser identificadas as informações que precisam permanecer registradas no sistema.

A análise deverá especificar:

* Entidades necessárias.  
* Atributos de cada entidade.  
* Chaves primárias (PK).  
* Chaves estrangeiras (FK).  
* Relacionamentos entre entidades.  
* Cardinalidades.  
* Tabelas associativas.  
* Restrições de integridade.  
* Informações obrigatórias e opcionais.  
* Dados que precisam ser persistidos para consultas futuras.  
* Necessidades de atualização e exclusão de registros.

Também devem ser identificadas as informações que não precisam ser armazenadas.

Por exemplo, uma opção selecionada temporariamente em um filtro pode existir apenas no estado da interface. Ela não precisa necessariamente gerar um registro no banco.

**Pergunta orientadora:** quais dados precisam existir de forma persistente para que o sistema funcione corretamente?

### **3.2. Lógica de programação / Backend — o que o sistema precisa processar?**

Nesta parte, devem ser identificadas as regras e operações que determinam como o sistema funciona.

A análise deverá especificar:

* Regras de negócio.  
* Condições para permitir ou impedir operações.  
* Validações dos dados recebidos.  
* Verificação de permissões.  
* Regras de associação entre entidades.  
* Processamento de dados.  
* Consultas e filtros executados no banco.  
* Operações de cadastro, consulta, atualização e exclusão.  
* Tratamento de situações excepcionais.  
* Necessidades de transações.  
* Endpoints necessários para comunicação com o frontend.  
* Estrutura das respostas JSON.

É importante diferenciar uma validação de interface de uma validação de segurança ou regra de negócio.

Por exemplo: o React pode impedir que um formulário seja enviado quando um campo obrigatório estiver vazio. Entretanto, o PHP também deverá validar essa condição, pois o backend não pode confiar exclusivamente nos dados recebidos do frontend.

**Pergunta orientadora:** quais condições, decisões e operações precisam ser executadas para garantir que o sistema funcione corretamente?

### **3.3. Frontend Next.js/React — o que precisa ser apresentado e controlado na interface?**

Nesta parte, devem ser identificados os elementos visuais e as interações necessárias para o usuário utilizar a funcionalidade.

A análise deverá especificar:

* Elementos apresentados na página.  
* Campos de formulários.  
* Tabelas e listagens.  
* Botões e ações disponíveis.  
* Filtros e seletores.  
* Navegação entre páginas.  
* Estados da interface.  
* Carregamento e atualização de informações.  
* Controle de formulários.  
* Mensagens visuais de sucesso e erro.  
* Confirmações de ações.  
* Requisições que deverão ser realizadas ao backend.  
* Tratamento visual das respostas recebidas.

O frontend poderá realizar validações de experiência do usuário, como verificar se um campo está preenchido ou apresentar uma mensagem antes do envio.

Entretanto, essas validações não substituem as verificações obrigatórias do backend.

**Pergunta orientadora:** o que o usuário precisa visualizar, selecionar, informar ou executar para utilizar essa funcionalidade?

## **4\. Como classificar uma informação entre as três camadas**

Uma mesma funcionalidade pode envolver as três camadas, mas cada uma terá uma responsabilidade diferente.

Exemplo: cadastro de aluno.

| Camada | Responsabilidade |
| ----- | ----- |
| Banco de dados | Armazenar nome, matrícula e demais atributos necessários do aluno. |
| Backend PHP | Validar os dados, verificar matrícula duplicada e executar o cadastro. |
| Frontend React | Apresentar o formulário, capturar os dados e exibir o resultado do cadastro. |

Outro exemplo: seleção de disciplina.

| Camada | Responsabilidade |
| ----- | ----- |
| Banco de dados | Armazenar os cursos, disciplinas e seus relacionamentos. |
| Backend PHP | Consultar as disciplinas pertencentes ao curso informado. |
| Frontend React | Exibir as disciplinas disponíveis após a seleção do curso. |

A documentação não deverá simplesmente repetir uma informação nas três camadas. Deve explicar qual parte da responsabilidade pertence a cada uma.

## **5\. Identificação das páginas e funcionalidades**

A análise deverá começar pela identificação das páginas e fluxos do sistema. No React (Single Page Application), muitas funcionalidades de cadastro e edição ocorrem sem mudança de URL, utilizando **Modais controlados por estados da interface**. Por isso, além da rota de navegação, a análise deve identificar os **Componentes principais** envolvidos na tela e seus respectivos estados. O foco inicial será compreender:

* Endereço da página.  
* Objetivo da página.  
* Perfil de usuário que possui acesso.  
* Informações apresentadas.  
* Ações disponíveis.  
* Fluxo de navegação.  
* Comportamentos observáveis.  
* Relação com outras páginas e funcionalidades.

É necessário diferenciar:

**Rota da página:** endereço utilizado para acessar uma tela do frontend.

Exemplo:  
`/admin/alunos`

**Endpoint do backend:** endereço utilizado para executar uma operação ou consultar informações.

Exemplo:  
`/api/admin/alunos/listar.php`

Uma rota de frontend não é necessariamente equivalente a um endpoint. Uma página pode consumir vários endpoints, e um mesmo endpoint pode atender diferentes páginas do mesmo perfil.

## **6\. Operações CRUD e comunicação com o backend**

As funcionalidades deverão ser analisadas considerando as operações necessárias:

* Create: cadastrar.  
* Read: consultar.  
* Update: atualizar.  
* Delete: excluir.

A existência de uma operação CRUD não significa que será necessário criar um arquivo PHP para cada botão ou campo da interface.

Os endpoints deverão ser organizados por responsabilidade, evitando duplicidade e excesso de arquivos.

### **6.1. Métodos HTTP**

| Método | Utilização |
| ----- | ----- |
| GET | Consultar informações. |
| POST | Cadastrar informações ou executar operações que não se enquadrem em uma atualização convencional. |
| PUT/PATCH | Atualizar informações. |
| DELETE | Excluir informações. |

A escolha deverá considerar a natureza da operação e o padrão definido para o projeto. **Neste projeto, o padrão definitivo é `PUT` para atualizações; `PATCH` não será utilizado.**

### **6.2. Identificação dos registros**

As operações de edição e exclusão deverão utilizar o identificador real do registro, normalmente sua chave primária.

Não se deve utilizar a posição do item em uma lista como identificador.

Na edição, o fluxo normalmente envolve:

1. React identifica o ID do registro selecionado.  
2. React solicita ao backend os dados daquele registro.  
3. PHP consulta o banco e retorna os dados em JSON.  
4. React preenche o formulário.  
5. O usuário modifica as informações.  
6. React envia os dados atualizados e o ID correspondente.  
7. PHP valida e executa a atualização.  
8. React atualiza a interface conforme o resultado.

### **6.3. Estrutura de Pastas do Backend (API)**

Esta seção define o padrão definitivo de organização do backend e elimina as ambiguidades entre `/backend/...`, `/api/...` e os nomes das pastas do repositório.

**Modelo aprovado: híbrido.** O endpoint é o controlador da operação e pode executar SQL PDO diretamente quando o CRUD for simples (uma tabela, sem transação e sem cálculo). Regras complexas, transações, cálculo de notas e mock ficam em `src/dominio/`. Consultas compartilhadas por mais de um endpoint podem ser extraídas para `src/consultas/`. A serialização dos DTOs fica em `src/contrato/`.

#### **6.3.1. Pastas na raiz do repositório**

As pastas oficiais do projeto são:

| Pasta | Conteúdo |
| ----- | -------- |
| `/docs/` | Documentação técnica, especificações e padrões de código. |
| `/database/` | Scripts SQL oficiais: `01_schema_ddl.sql`, `02_trigger_exclusividade.sql` e `03_seeds_dml.sql`. |
| `/back-end/` | API PHP. |
| `/front-end/` | Aplicação Next.js/React. |

As menções antigas a `/backend/`, `/frontend/` e `/dados/` devem ser lidas, respectivamente, como `/back-end/`, `/front-end/` e `/database/`. A nomenclatura canônica é sempre a desta tabela.

#### **6.3.2. Document root, URL base e prefixo `/api`**

* Apenas `back-end/public/` é exposta pelo servidor web. A pasta `back-end/src/` **nunca** é acessível via HTTP.
* Em desenvolvimento, o servidor é iniciado com: `php -S localhost:8000 -t back-end/public`.
* Em produção, o document root do Apache/Nginx aponta para `back-end/public/`.
* A URL base do servidor em desenvolvimento é `http://localhost:8000`; os endpoints documentados já incluem o prefixo `/api/...`.
* No front-end, `NEXT_PUBLIC_API_BASE_URL` deve apontar para `http://localhost:8000` (sem `/api`), e o service concatena o caminho completo do endpoint; é proibido fixar URLs no código dos componentes.
* Todo endpoint segue o formato `/api/{perfil}/{recurso}/{acao}.php`.

Assim, `/api/...` é o prefixo **lógico** da URL, enquanto `/back-end/` é a localização **física** do código. Os dois não são equivalentes e não devem ser misturados.

* A ordem de aplicação dos scripts SQL (`01`, `02`, `03`) e o uso do cliente `mysql` para o trigger estão documentados em `docs/README.md`, seção 8.1.

#### **6.3.3. Árvore de diretórios obrigatória**

```text
back-end/
├── .env                                     # credenciais locais (não versionado)
├── .env.example                             # modelo versionado, sem senha
├── public/                                  # única pasta exposta pelo servidor
│   └── api/
│       ├── admin/
│       │   ├── alunos/
│       │   │   ├── listar.php
│       │   │   ├── cadastrar.php
│       │   │   ├── editar.php
│       │   │   └── excluir.php
│       │   ├── professores/                 # CRUD completo
│       │   ├── cursos/                      # CRUD completo
│       │   └── disciplinas/                 # CRUD + detalhes.php
│       ├── professor/
│       │   ├── cursos/listar.php
│       │   ├── disciplinas/listar.php
│       │   ├── alunos/listar.php
│       │   ├── seminarios/                  # listar, cadastrar, detalhes, editar, excluir
│       │   ├── grupos/                      # listar, cadastrar, editar, excluir, sortear
│       │   ├── presencas/                   # listar, salvar
│       │   ├── apresentacao/                # carregar, iniciar, salvar-avaliacao, encerrar
│       │   └── resultados/                  # compilar
│       └── aluno/
│           ├── seminarios/                  # listar, detalhes
│           ├── avaliacoes/                  # formulario, salvar
│           └── resultados/                  # compilar
├── src/                                     # código interno, fora do document root
│   ├── bootstrap.php
│   ├── config/
│   │   ├── ambiente.php
│   │   └── conexao.php
│   ├── http/
│   │   ├── cors.php
│   │   ├── erro.php
│   │   ├── requisicao.php
│   │   └── resposta.php
│   ├── auth/
│   │   └── identidade.php
│   ├── validacao/
│   │   └── validadores.php
│   ├── contrato/                            # serializadores DTO (camelCase)
│   ├── dominio/                             # regras complexas e transações
│   └── consultas/                           # SQL compartilhado (opcional)
└── README.md
```

Cada pasta comentada contém um arquivo por operação, seguindo o padrão de nomenclatura da seção 6.3.5.

#### **6.3.4. Responsabilidade de cada pasta**

| Caminho | Responsabilidade |
| ------- | ---------------- |
| `public/api/{perfil}/{recurso}/{acao}.php` | Controlador da operação: valida método e identidade, lê a entrada, executa SQL simples ou chama o domínio e devolve JSON. Não contém HTML. |
| `src/dominio/` | Funções de regra de negócio, cálculos, mock e transações. Organizadas por entidade, independentes de HTTP. Não leem `$_GET`/`$_POST`. |
| `src/consultas/` | Consultas SQL compartilhadas por mais de um endpoint/perfil. Opcional; não é obrigatório para CRUD simples. |
| `src/contrato/` | Funções que convertem linhas do banco nos DTOs camelCase definidos em `docs/contrato_api_frontend.md`. |
| `src/http/` | Helpers de resposta JSON, leitura do corpo da requisição, CORS e exceções HTTP. |
| `src/validacao/` | Validações reutilizáveis de campos, tipos e formatos. |
| `src/auth/` | Identificação do perfil no MVP: lê e valida `professor_id`/`aluno_id` enviados na requisição. Não há autenticação real; ver seção 6.3.15. |
| `src/config/` | Configuração de ambiente e conexão PDO. |
| `src/bootstrap.php` | Ponto único de inclusão: carrega configuração, CORS, helpers e registra o tratamento global de exceções. |

**Regra de decisão:** simples = uma tabela, sem transação, sem cálculo → SQL no endpoint. Complexo = duas ou mais tabelas, transação, cálculo, mock ou operação em lote → função em `src/dominio/`. Se a mesma consulta aparecer em mais de um endpoint, extrair para `src/consultas/`.

O isolamento por perfil vale para **rotas e autorização**, não para infraestrutura. `src/` concentra código compartilhado, não expõe endpoint e não decide sozinho qual perfil pode acessar o quê. Essa é a exceção necessária à regra de separação rígida, evitando duplicação de conexão, resposta e regras complexas.

#### **6.3.5. Convenções de nomes e URLs**

* Pastas de recursos: plural, minúsculas, sem acentos e sem camelCase. Exemplos: `alunos`, `professores`, `cursos`, `disciplinas`, `seminarios`, `grupos`, `presencas`, `avaliacoes`, `resultados`.
* Arquivos: verbo no infinitivo, minúsculo, com hífen apenas quando necessário. Exemplos: `listar.php`, `cadastrar.php`, `editar.php`, `excluir.php`, `detalhes.php`, `salvar.php`, `sortear.php`, `salvar-avaliacao.php`.
* Um arquivo representa uma única operação. Não criar `index.php`, `processa.php`, `crud.php` ou um arquivo por botão da interface.
* Operações de edição e exclusão usam sempre a chave primária real do registro. É proibido usar a posição do item na lista.
* Identificadores de contexto e filtros vão na query string: `?id=15`, `?curso_id=2`, `?data_id=7`, `?grupo_id=3`, `?disciplina_id=4`, `?busca=maria`.
* Todo endpoint do perfil professor exige `?professor_id={id}`; todo endpoint do perfil aluno exige `?aluno_id={id}`. Endpoints do perfil admin não exigem identificador.
* O corpo das requisições `POST` e `PUT` é sempre JSON e contém apenas os dados da operação (campos, arrays de IDs e notas).
* `GET` nunca altera estado. Ações que mudam estado usam `POST`, `PUT` ou `DELETE`.

#### **6.3.6. Métodos HTTP por operação**

| Operação | Método | Exemplo |
| -------- | ------ | ------- |
| Listar ou consultar | GET | `/api/admin/alunos/listar.php?busca=maria` |
| Detalhar um registro | GET | `/api/professor/seminarios/detalhes.php?professor_id=1&id=1` |
| Cadastrar | POST | `/api/admin/alunos/cadastrar.php` |
| Atualizar | PUT | `/api/admin/alunos/editar.php?id=1` |
| Excluir | DELETE | `/api/admin/alunos/excluir.php?id=1` |
| Ação de domínio | POST | `/api/professor/grupos/sortear.php?professor_id=1&data_id=7` |
| Preflight CORS | OPTIONS | Qualquer endpoint |

#### **6.3.7. Catálogo de endpoints por perfil**

**Administrador**

Endpoints administrativos não exigem identificador de usuário no MVP.

| Método | Endpoint | Finalidade |
| ------ | -------- | ---------- |
| GET | `/api/admin/alunos/listar.php` | Lista e busca alunos (`?busca=`); por padrão apenas ativos (`?incluir_inativos=1` inclui inativos). |
| POST | `/api/admin/alunos/cadastrar.php` | Cadastra aluno. |
| PUT | `/api/admin/alunos/editar.php?id={id}` | Atualiza aluno. |
| DELETE | `/api/admin/alunos/excluir.php?id={id}` | Exclusão lógica do aluno (status inativo). |
| POST | `/api/admin/alunos/excluir-lote.php` | Exclusão lógica em lote; corpo `{ "ids": number[] }`; atômica. |
| GET | `/api/admin/professores/listar.php` | Lista e busca professores; por padrão apenas ativos (`?incluir_inativos=1` inclui inativos). |
| POST | `/api/admin/professores/cadastrar.php` | Cadastra professor. |
| PUT | `/api/admin/professores/editar.php?id={id}` | Atualiza professor. |
| DELETE | `/api/admin/professores/excluir.php?id={id}` | Exclusão lógica do professor (status inativo). |
| POST | `/api/admin/professores/excluir-lote.php` | Exclusão lógica em lote; corpo `{ "ids": number[] }`; atômica. |
| GET | `/api/admin/cursos/listar.php` | Lista cursos. |
| POST | `/api/admin/cursos/cadastrar.php` | Cadastra curso. |
| PUT | `/api/admin/cursos/editar.php?id={id}` | Atualiza curso. |
| DELETE | `/api/admin/cursos/excluir.php?id={id}` | Exclui curso. |
| GET | `/api/admin/disciplinas/listar.php?curso_id={id}` | Lista disciplinas do curso. |
| GET | `/api/admin/disciplinas/detalhes.php?id={id}` | Retorna a disciplina com professor e alunos vinculados. |
| POST | `/api/admin/disciplinas/cadastrar.php` | Cadastra disciplina e vínculos. |
| PUT | `/api/admin/disciplinas/editar.php?id={id}` | Atualiza disciplina e vínculos. |
| DELETE | `/api/admin/disciplinas/excluir.php?id={id}` | Exclui disciplina. |

**Professor**

Todos os endpoints abaixo exigem `?professor_id={id}`.

| Método | Endpoint | Finalidade |
| ------ | -------- | ---------- |
| GET | `/api/professor/cursos/listar.php?professor_id={id}` | Lista todos os cursos cadastrados para os selects do formulário. |
| GET | `/api/professor/disciplinas/listar.php?professor_id={id}&curso_id={id}` | Lista as disciplinas do curso para os selects do formulário. |
| GET | `/api/professor/alunos/listar.php?professor_id={id}&disciplina_id={id}` | Alunos ativos matriculados na disciplina. |
| GET | `/api/professor/seminarios/listar.php?professor_id={id}` | Lista seminários do professor identificado por `professor_id`. |
| POST | `/api/professor/seminarios/cadastrar.php?professor_id={id}` | Cria seminário, datas e critérios. |
| GET | `/api/professor/seminarios/detalhes.php?professor_id={id}&id={id}` | Detalhes do seminário, datas, grupos e critérios. |
| PUT | `/api/professor/seminarios/editar.php?professor_id={id}&id={id}` | Edita dados gerais do seminário. |
| DELETE | `/api/professor/seminarios/excluir.php?professor_id={id}&id={id}` | Exclui seminário. |
| GET | `/api/professor/grupos/listar.php?professor_id={id}&data_id={id}` | Lista grupos da sessão. |
| POST | `/api/professor/grupos/cadastrar.php?professor_id={id}&data_id={id}` | Cria grupo e alunos. |
| PUT | `/api/professor/grupos/editar.php?professor_id={id}&id={id}` | Edita grupo e alunos. |
| DELETE | `/api/professor/grupos/excluir.php?professor_id={id}&id={id}` | Exclui grupo. |
| POST | `/api/professor/grupos/sortear.php?professor_id={id}&data_id={id}` | Salva a ordem sorteada. |
| PUT | `/api/professor/grupos/sincronizar.php?professor_id={id}&data_id={id}` | Sincroniza grupos em lote; corpo `{ "grupos": [{ "id": number\|null, "tema": string, "integrantesIds": number[] }] }`. |
| GET | `/api/professor/presencas/listar.php?professor_id={id}&data_id={id}` | Lista a chamada da sessão. |
| POST | `/api/professor/presencas/salvar.php?professor_id={id}&data_id={id}` | Salva a chamada em lote. |
| GET | `/api/professor/apresentacao/carregar.php?professor_id={id}&data_id={id}` | Carrega cronograma e rubrica da tela ao vivo. |
| POST | `/api/professor/apresentacao/iniciar.php?professor_id={id}&data_id={id}` | Marca a sessão como em andamento. |
| POST | `/api/professor/apresentacao/salvar-avaliacao.php?professor_id={id}&data_id={id}&grupo_id={id}` | Salva notas e feedback, com transação. |
| POST | `/api/professor/apresentacao/encerrar.php?professor_id={id}&data_id={id}` | Conclui a sessão. |
| GET | `/api/professor/resultados/compilar.php?professor_id={id}&data_id={id}` | Compila ranking e detalhamento. |

**Aluno**

Todos os endpoints abaixo exigem `?aluno_id={id}`.

| Método | Endpoint | Finalidade |
| ------ | -------- | ---------- |
| GET | `/api/aluno/seminarios/listar.php?aluno_id={id}` | Seminários das disciplinas em que o aluno está matriculado. |
| GET | `/api/aluno/seminarios/detalhes.php?aluno_id={id}&id={id}` | Detalhes, datas, grupos e presença. |
| GET | `/api/aluno/avaliacoes/formulario.php?aluno_id={id}&data_id={id}` | Rubrica e grupos elegíveis para avaliação. |
| POST | `/api/aluno/avaliacoes/salvar.php?aluno_id={id}&data_id={id}&grupo_id={id}` | Registra a avaliação do aluno. |
| GET | `/api/aluno/resultados/compilar.php?aluno_id={id}&data_id={id}` | Resultados permitidos ao aluno. |

Endpoints adicionais devem seguir exatamente o mesmo padrão de pasta, nome e método.

#### **6.3.8. Isolamento por perfil e consultas compartilhadas**

* Endpoints em `/api/admin`, `/api/professor` e `/api/aluno` são destinados exclusivamente ao respectivo perfil. Cada arquivo chama `exigir_identidade()` antes de qualquer processamento, informando o perfil esperado. No MVP, a função lê `?professor_id=`/`?aluno_id=`; para o perfil admin, é um no-op. ID ausente ou inválido retorna `400`; ID inexistente retorna `404`. Como não há autenticação, a separação é organizacional e não impede tecnicamente chamadas cruzadas; ver seção 6.3.15.
* Uma tela de um perfil nunca chama endpoint de outro perfil. Quando dois perfis precisam da mesma entidade, cada perfil possui seu próprio endpoint com as regras de visibilidade adequadas. Por exemplo: o administrador lista todos os alunos; o professor lista apenas os alunos das disciplinas que leciona.
* Consultas simples podem ficar no próprio endpoint. Quando a mesma consulta for necessária em mais de um perfil ou endpoint, extrair para `src/consultas/`, passando filtros e contexto diferentes. Consultas complexas ou transacionais ficam em `src/dominio/`.
* Os exemplos `GET /api/cursos/listar.php` e `GET /api/alunos/listar.php` das especificações ficam substituídos por:
  * `/api/admin/cursos/listar.php` e `/api/admin/alunos/listar.php` para a coordenação;
  * `/api/professor/cursos/listar.php?professor_id={id}` e `/api/professor/alunos/listar.php?professor_id={id}&disciplina_id={id}` para o professor;
  * `/api/aluno/seminarios/listar.php?aluno_id={id}` para o aluno.
* Não existe namespace `/api/comum`. Leitura compartilhada é resolvida por endpoints específicos de cada perfil e, quando a mesma consulta é reutilizada, por funções em `src/consultas/`.

#### **6.3.9. Contrato obrigatório dos endpoints**

Todo endpoint deve seguir esta sequência:

1. Incluir `src/bootstrap.php` por caminho absoluto.
2. Exigir o método HTTP correto com `exigir_metodo()`.
3. Exigir a identidade do perfil com `exigir_identidade('admin'|'professor'|'aluno')` e, quando aplicável, validar a propriedade do recurso.
4. Ler e validar os dados de entrada.
5. Executar a operação:
   * **CRUD simples:** SQL PDO no próprio endpoint, com prepared statements.
   * **Fluxo complexo:** chamar uma função de `src/dominio/`.
6. Responder com `responder_sucesso()` ou lançar uma exceção HTTP tratada pelo bootstrap.

Exemplo de endpoint simples:

```php
<?php
declare(strict_types=1);

require dirname(__DIR__, 4) . '/src/bootstrap.php';

exigir_metodo('GET');
exigir_identidade('admin');

$busca = trim((string) ($_GET['busca'] ?? ''));
$sql = "SELECT id, nome_completo, email, matricula, ano_matricula, semestre_entrada, status
        FROM alunos
        WHERE status = 'ativo'";
$params = [];

if ($busca !== '') {
    $sql .= ' AND (nome_completo LIKE ? OR matricula LIKE ?)';
    $params[] = "%{$busca}%";
    $params[] = "%{$busca}%";
}

$sql .= ' ORDER BY nome_completo';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);

responder_sucesso(array_map('serializar_aluno', $stmt->fetchAll()));
```

Exemplo de endpoint complexo:

```php
<?php
declare(strict_types=1);

require dirname(__DIR__, 4) . '/src/bootstrap.php';

exigir_metodo('POST');
$professorId = exigir_identidade('professor');
$dados = ler_json();

responder_sucesso(criar_seminario($pdo, $professorId, $dados), 201);
```

Para o perfil admin, `exigir_identidade('admin')` não exige parâmetro e retorna `null`. Para professor e aluno, a função lê e valida `?professor_id=`/`?aluno_id=` antes de qualquer consulta.

Nenhum endpoint deve imprimir HTML, `var_dump`, avisos ou espaços antes do JSON.

#### **6.3.10. Formato de resposta e códigos HTTP**

Resposta de sucesso:

```json
{
  "sucesso": true,
  "dados": [],
  "mensagem": "Operação realizada com sucesso."
}
```

Resposta de erro:

```json
{
  "sucesso": false,
  "mensagem": "Matrícula já cadastrada.",
  "erros": {
    "registro": "Esta matrícula já pertence a outro aluno."
  }
}
```

* `dados` pode ser objeto, array ou `null`.
* Os campos dentro de `dados` seguem o contrato JSON de `docs/contrato_api_frontend.md`: camelCase, IDs numéricos (exceto o `id` sintético de avaliação) e enums do frontend.
* `mensagem` é obrigatória em erros e opcional em sucessos.
* `erros` é opcional e serve para validações por campo.
* O `Content-Type` de toda resposta é `application/json; charset=utf-8`.

| Código | Uso |
| ------ | --- |
| 200 | Consulta, atualização ou exclusão concluída. |
| 201 | Cadastro concluído. |
| 400 | Dados inválidos ou regra de negócio violada. |
| 401 | Reservado para autenticação real; não utilizado no MVP. |
| 403 | Perfil sem permissão ou recurso pertencente a outro usuário. |
| 404 | Registro não encontrado. |
| 409 | Conflito de unicidade (e-mail, matrícula, registro ou código) ou dependência que impede a exclusão. |
| 500 | Erro interno não previsto. |

Mensagens de erro internas, SQL e stack traces nunca são enviados ao front-end; devem ser registrados no log do servidor.

#### **6.3.11. CORS e preflight**

O tratamento de CORS é centralizado em `src/http/cors.php` e carregado pelo bootstrap.

* Origem permitida configurável por `APP_ORIGIN`; em desenvolvimento, `http://localhost:3000`.
* Métodos permitidos: `GET, POST, PUT, DELETE, OPTIONS`.
* Cabeçalhos permitidos: `Content-Type`. O cabeçalho `Authorization` será incluído quando houver autenticação real.
* Requisições `OPTIONS` respondem `204` sem corpo.
* O MVP não usa cookies nem sessão. Quando a autenticação real for adicionada, `Access-Control-Allow-Credentials: true` e a origem exata deverão ser usados; nunca `*` nesse caso.

#### **6.3.12. Conexão com o banco de dados**

A conexão fica em `src/config/conexao.php` e é carregada pelo bootstrap:

```php
<?php
declare(strict_types=1);

$host = getenv('DB_HOST') ?: '127.0.0.1';
$porta = getenv('DB_PORT') ?: '3306';
$dbname = getenv('DB_NAME') ?: 'gestao_seminarios';
$usuario = getenv('DB_USER') ?: 'root';
$senha = getenv('DB_PASS') ?: '';

$pdo = new PDO(
    "mysql:host={$host};port={$porta};dbname={$dbname};charset=utf8mb4",
    $usuario,
    $senha,
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]
);
```

Credenciais reais não devem ser versionadas. Os exemplos de `docs/padroes_de_codigo_php.md` permanecem como referência didática de PDO e prepared statements; o modelo de formulário HTML com `header('Location: ...')` não se aplica à API JSON. O padrão de código da API é o modelo híbrido descrito nesta seção e em `docs/padroes_de_codigo_php.md`.

#### **6.3.13. Regras de segurança obrigatórias**

* Toda consulta usa prepared statements; é proibido concatenar valores na SQL.
* No MVP, o identificador do usuário é enviado explicitamente por `?professor_id=` ou `?aluno_id=` e validado contra o banco. Como não há autenticação, esse valor pode ser falsificado; a limitação está registrada na seção 6.3.15.
* O backend limita os dados pelo ID informado: o professor só altera seminários, grupos, presenças e avaliações dos quais é responsável; o aluno só consulta seus próprios dados e os do seu grupo. Essa validação impede erros de escopo, mas não substitui autenticação.
* Identificadores recebidos devem ser validados como inteiros e ter existência confirmada no banco.
* Erros internos devem ser registrados e devolvidos como `500` genérico.
* Em produção, `display_errors` fica desligado e `log_errors` ligado.

#### **6.3.14. Transações obrigatórias**

As operações abaixo exigem transação por alterarem várias tabelas/linhas ou por precisarem de atomicidade; devem usar `beginTransaction()`, `commit()` e `rollBack()` em `try/catch`:

| Operação | Tabelas envolvidas |
| -------- | ------------------ |
| Cadastrar ou editar disciplina | `disciplinas`, `disciplina_aluno` |
| Cadastrar ou editar seminário | `seminarios`, `seminario_datas`, `seminario_criterios` |
| Cadastrar ou editar grupo | `grupos`, `grupo_alunos` |
| Salvar chamada em lote | `presencas` |
| Salvar avaliação | `avaliacoes_notas`, `avaliacoes_feedbacks` (incluindo o mock) |
| Sortear ordem de apresentação | `grupos` |
| Encerrar sessão | `seminario_datas` e validação de grupos pendentes |

Quando qualquer etapa falhar, a transação é desfeita e o erro é propagado para o tratamento global, que devolve o JSON de erro adequado.

#### **6.3.15. Limitação consciente do MVP: identidade sem autenticação**

O MVP não possui login, sessão, token ou senha. O perfil ativo é identificado por:

* **Admin:** pela própria pasta `/api/admin`, sem identificador.
* **Professor:** parâmetro `?professor_id={id}` em todas as requisições.
* **Aluno:** parâmetro `?aluno_id={id}` em todas as requisições.

O backend valida que o identificador existe, está ativo e que o recurso pertence ao usuário informado. Ainda assim, um cliente malicioso pode enviar outro ID; portanto, essa é uma decisão de escopo do MVP, não uma recomendação de produção.

Para simplificar os testes, `back-end/.env` define `PROFESSOR_PADRAO=1` e `ALUNO_PADRAO=1`, expostos como constantes pelo bootstrap. O frontend envia esses valores na query string; o parâmetro continua obrigatório e ausência retorna `400`.

A evolução prevista é substituir o corpo de `exigir_identidade()` por uma verificação de sessão ou token, mantendo os mesmos endpoints e as mesmas validações de propriedade.

#### **6.3.16. Contrato JSON e integração com o frontend**

O contrato detalhado está em `docs/contrato_api_frontend.md`. As regras obrigatórias são:

* O JSON de entrada e saída usa **camelCase**, alinhado a `front-end/lib/types.ts`.
* A query string usa **snake_case** (`professor_id`, `aluno_id`, `data_id`, `grupo_id`, `disciplina_id`, `incluir_inativos`).
* Os IDs no JSON da API são **numéricos**. O frontend usa IDs opacos com prefixo (`u-al-1`, `u-prof-1`, `t-1`, `s-1`, `g-1`); a camada `front-end/lib/services` faz o de-para.
* Enums, pesos, datas e campos compostos são convertidos pelos serializadores de `src/contrato/`; o frontend não recebe nomes de colunas do banco.
* A API nunca devolve linhas cruas do banco.
* O cálculo de notas e o mock ficam no backend. O painel de resultados do React consome `/api/professor/resultados/compilar.php` ou `/api/aluno/resultados/compilar.php`, conforme o perfil, e não recalcula a nota oficial.
* O `AppProvider` continua sendo a fachada de estado do frontend; as chamadas HTTP ficam em `lib/services`.
* Qualquer endpoint, campo, enum ou payload novo deve atualizar o contrato JSON antes ou junto da implementação.

#### **6.3.17. Fase 1 da implementação de referência — infraestrutura e admin**

> As seções 6.3.17 a 6.3.20 registram a implementação de referência já validada. A seção 6.3.22 descreve o estado atual do `back-end/`; apenas duas rotas de professor do catálogo continuam pendentes.

Na implementação de referência, a Fase 1 foi concluída e validada:

- Infraestrutura PHP em `back-end/src/` (bootstrap, config, HTTP, auth, validação, contrato e consultas).
- Ambiente em `back-end/.env`, modelo em `back-end/.env.example` e orientações em `back-end/README.md`.
- 19 endpoints admin: CRUD de alunos, professores, cursos e disciplinas, mais exclusão lote de alunos e professores.
- Serializadores DTO em `src/contrato/` e consultas reutilizáveis em `src/consultas/admin.php`.
- Frontend: camada `front-end/lib/services/`; telas `admin/alunos`, `admin/professores` e `admin/cursos` passaram a consumir a API.
- `AppProvider` hidrata alunos/professores da API; `seminarios` e `avaliacoes` ainda vêm de mocks até a Fase 2.

Comandos usados no ambiente local:

```bash
mysql -u root -p < database/01_schema_ddl.sql
mysql -u root -p < database/02_trigger_exclusividade.sql
mysql -u root -p < database/03_seeds_dml.sql
php -S localhost:8000 -t back-end/public
curl "http://localhost:8000/api/admin/alunos/listar.php"
cd front-end && npx tsc --noEmit && npm run build
```

Validações executadas: `php -l` em todos os arquivos PHP, chamadas `curl` de CRUD/lote e build de produção do Next.js.

#### **6.3.18. Fase 2 da implementação de referência — domínio e endpoints de professor**

A Fase 2 backend está concluída e validada:

- Serializadores de critério, grupo, sessão e seminário em `src/contrato/`.
- Consultas e validações de propriedade em `src/consultas/professor.php`.
- Domínio transacional em `src/dominio/`: `seminarios.php`, `grupos.php` e `presencas.php`.
- 16 endpoints de professor implementados: cursos, disciplinas, alunos, seminários ( CRUD completo ), grupos ( CRUD, sortear, sincronizar ) e presença (listar/salvar).
- Edição completa de seminário com **diff por ID**: datas/critérios enviados são atualizados; novos são criados; omitidos são removidos apenas se não houver histórico — caso contrário, `409 Conflict`.
- Sincronização de grupos em lote com remoção prévia de vínculos para respeitar o trigger de exclusividade.
- Erros `SQLSTATE 45000` dos triggers são convertidos para `400` com a mensagem da regra.

A `apresentacao/*` e `resultados/compilar.php` foram entregues na Fase 3 (seções 6.3.19 e 6.3.20); esta seção registra a entrega da Fase 2.

Comandos de validação usados:

```bash
curl "http://localhost:8000/api/professor/seminarios/listar.php?professor_id=1"
curl -X POST "http://localhost:8000/api/professor/seminarios/cadastrar.php?professor_id=1" -H "Content-Type: application/json" -d '{...}'
curl -X PUT "http://localhost:8000/api/professor/grupos/sincronizar.php?professor_id=1&data_id=1" -H "Content-Type: application/json" -d '{"grupos":[]}'
php -S localhost:8000 -t back-end/public
```

> Atenção: o perfil padrão `PROFESSOR_PADRAO=1` precisa estar ativo no banco; se o usuário tiver inativado o professor 1 pela interface admin, os endpoints retornam `403`.

#### **6.3.19. Fase 3 da implementação de referência — avaliação e resultados (professor)**

Implementado e validado:

- Domínio de avaliação em `src/dominio/avaliacao.php`: carregar apresentação, iniciar, salvar nota/feedback, gerar mock idempotente e encerrar sessão.
- Domínio de resultados em `src/dominio/resultados.php`: `T_s`, `P_s`, `F_s`, média do grupo, ranking, fallback `P_s = T_s` e arredondamento só na resposta.
- 4 endpoints presentes: `apresentacao/carregar.php`, `salvar-avaliacao.php`, `encerrar.php` e `resultados/compilar.php`; `apresentacao/iniciar.php` segue pendente (ver 6.3.22).
- Frontend: página de apresentação e aba de resultados passaram a consumir a API; componente `ResultadoView` renderiza o `ResultadoDTO`.
- Múltiplas datas validadas: grupos, presença e resultados são independentes por `seminario_data_id`.

Os endpoints do aluno e a privacidade de resultados foram entregues na sequência (seção 6.3.20); o fluxo completo está amarrado na seção 6.3.21 e no contrato, seção 9.4.

> IDs mockados como `s-live` não existem no banco. Para testar avaliação, use um seminário criado pela API, por exemplo `/professor/seminarios/s-9/apresentar`.

#### **6.3.20. Fase 3 da implementação de referência — perfil aluno**

Implementado e validado:

- 5 endpoints do aluno: `seminarios/listar`, `seminarios/detalhes`, `avaliacoes/formulario`, `avaliacoes/salvar` e `resultados/compilar`.
- Consultas de matrícula: o aluno só acessa seminários da sua disciplina.
- Elegibilidade: só alunos presentes na chamada podem avaliar, e nunca o próprio grupo.
- `formulario.php` devolve critérios, grupos elegíveis e avaliações já salvas pelo aluno.
- `resultados/compilar.php` usa o mesmo motor do professor e aplica privacidade: aluno vê integralmente apenas o próprio grupo; nos demais, ranking sem integrantes e sem feedbacks de pares.
- Frontend: dashboard, listagem, detalhes, avaliação ao vivo e histórico de resultados passaram a consumir `alunoService`.

> O aluno ativo usado nos testes locais é definido por `NEXT_PUBLIC_ALUNO_ID` em `front-end/.env.local`.

#### **6.3.21. Amarração explícita dos formatos de resposta**

Os formatos que antes estavam apenas implícitos nas specs agora estão formalizados no contrato e devem ser seguidos por qualquer reimplementação do backend:

| Resposta | DTO / shape | Onde está definido | Consumidor |
| --- | --- | --- | --- |
| `apresentacao/carregar.php`, `iniciar.php`, `salvar-avaliacao.php` | `ApresentacaoDTO` — `seminarioId`, `dataId`, `dataSessao`, `status`, `grupos`, `gruposAvaliados`, `totalGrupos`; `grupos` traz `avaliado`, `notas` e `feedback` do professor | Contrato 7.11 | `professor.ts` → tela ao vivo |
| `apresentacao/encerrar.php` | `{ dataId, status: "finalizado", gruposPendentes }` | Contrato 7.11 (nota) | `professor.ts` |
| `aluno/avaliacoes/formulario.php`, `aluno/avaliacoes/salvar.php` | `FormularioAvaliacaoAlunoDTO` — `seminarioId`, `dataId`, `dataSessao`, `criterios`, `grupos` elegíveis e `minhasAvaliacoes` | Contrato 7.12 | `aluno.ts` → tela ao vivo do aluno |
| `professor/presencas/listar.php`, `salvar.php` | `[{ alunoId, nome, presente }]` | Contrato 9.4 | `professor.ts` → aba de chamada |
| `resultados/compilar.php` (professor e aluno) | `ResultadoDTO`, com privacidade no perfil aluno (DP-036) | Contrato 7.10 | `ResultadoView` |

Regras de amarração:

- `ApresentacaoDTO` mantém `status` bruto do banco e chaves `snake_case` em `grupos` — exceção documentada, exigida pelo consumidor atual (contrato 7.11). Qualquer mudança exige atualizar `front-end/lib/services/professor.ts` e a página de apresentação juntas.
- `FormularioAvaliacaoAlunoDTO` usa `CriterioDTO`/`GrupoDTO` em camelCase; `minhasAvaliacoes` reidrata o formulário ao trocar de grupo.
- Mutações que retornam estado devolvem o DTO da tela, não apenas confirmação genérica.
- Índice completo endpoint → DTO → consumidor: contrato, seção **9.4**.

#### **6.3.22. Estado de handoff (pacote para a equipe)**

O diretório `back-end/` conta hoje com infraestrutura em `src/`, **19 endpoints admin**, **19 endpoints do professor** e **5 endpoints do aluno** em `public/api/`. Faltam apenas `apresentacao/iniciar.php` e `grupos/sincronizar.php` do catálogo 6.3.7; as funções de domínio correspondentes já existem, e a exposição HTTP deve seguir este documento, o contrato, os padrões PHP, as specs e o DDL oficial.

Ordem de trabalho recomendada para o que falta: expor as duas rotas pendentes (`iniciar.php` e `sincronizar.php`) → validação com `curl`, `php -l`, `npx tsc --noEmit` e `npm run build`. O `front-end/lib/services/` já implementado define o formato esperado; o contrato, seção 9.4, é o índice de amarração.

## **7\. Filtros e consultas**

Os filtros deverão ser classificados conforme seu comportamento.

**Filtro simples:** utiliza um único critério.

Exemplo: pesquisar alunos pelo nome.

**Filtros combinados:** utilizam mais de um critério simultaneamente.

Exemplo: pesquisar apresentações por curso, disciplina e situação.

**Filtros encadeados:** a seleção de uma informação determina as opções disponíveis em outro campo.

Exemplo:

1. Selecionar um curso.  
2. Consultar as disciplinas pertencentes ao curso.  
3. Selecionar uma disciplina.  
4. Consultar os alunos associados à disciplina.

Nesse cenário:

* O banco precisa representar os relacionamentos entre cursos, disciplinas e alunos.  
* O backend precisa consultar as informações respeitando esses relacionamentos.  
* O frontend precisa controlar as seleções e atualizar os campos dependentes.  
* Quando uma seleção superior mudar, as seleções dependentes deverão ser reavaliadas ou limpas para evitar combinações inválidas.

Não será necessário criar um endpoint exclusivo para cada filtro. Deve-se avaliar a possibilidade de reutilizar consultas e endpoints com parâmetros diferentes.

## **8\. Regras de negócio**

As regras de negócio deverão ser identificadas independentemente dos elementos visuais.

Elas representam as condições que determinam o funcionamento correto do sistema.

Cada regra deverá ser:

* Objetiva.  
* Compreensível.  
* Verificável.  
* Identificada por um código.  
* Relacionada às funcionalidades que a utilizam.

Exemplo:

**RN-001 — Associação de alunos à disciplina**

Um aluno somente poderá ser apresentado como participante de uma disciplina quando existir o vínculo correspondente no sistema.

**RN-002 — Seleção de disciplinas**

Ao selecionar um curso, o sistema deverá disponibilizar somente as disciplinas associadas a esse curso.

**RN-003 — Integridade das operações**

Uma operação não deverá ser executada quando os dados recebidos violarem as regras de negócio ou a integridade dos relacionamentos.

As regras devem ser implementadas no backend sempre que representarem condições que precisam ser garantidas pelo sistema, independentemente da interface utilizada.

## **9\. Modelagem e estrutura do banco de dados**

A modelagem deverá ser analisada com base nas funcionalidades, nos comportamentos identificados no protótipo e nas regras de negócio.

A análise deverá identificar:

* Entidades necessárias.  
* Atributos.  
* PKs e FKs.  
* Relacionamentos.  
* Cardinalidades.  
* Tabelas associativas.  
* Restrições.  
* Dependências entre registros.  
* Informações que precisam ser persistidas.  
* Consultas que dependem dos relacionamentos.

O modelo lógico-relacional deverá representar adequadamente os requisitos identificados.

Caso a análise das funcionalidades revele necessidades não contempladas na estrutura existente, deverão ser propostas as atualizações necessárias, com justificativa baseada nos requisitos.

A finalidade é garantir que o banco sustente o funcionamento esperado do sistema.

A geração ou alteração de SQL deverá ocorrer depois da identificação e validação dos requisitos e relacionamentos necessários.

O DDL oficial do projeto é `database/01_schema_ddl.sql`. Os blocos SQL apresentados nas especificações são ilustrativos e não devem ser copiados para a implementação. Mudanças de schema exigem justificativa e atualização da documentação.

## **10\. Padrão de comunicação entre React e PHP**

A comunicação deverá utilizar requisições HTTP e respostas JSON.

Exemplo ilustrativo de resposta de consulta:

```
{
  "sucesso": true,
  "dados": [
    {
      "id": 15,
      "nome": "Maria"
    }
  ]
}
```

Exemplo ilustrativo de erro:

```
{
  "sucesso": false,
  "mensagem": "Matrícula já cadastrada."
}
```

O padrão definitivo de respostas é o definido na seção 6.3.10 e deve ser mantido de forma consistente em todos os endpoints. O formato dos DTOs está em `docs/contrato_api_frontend.md`: camelCase, IDs numéricos e enums do frontend. O service do frontend converte IDs numéricos em IDs prefixados antes de entregar os dados aos componentes.

O frontend deverá interpretar as respostas para atualizar a interface e apresentar o resultado ao usuário.

## **11\. Resultado esperado da análise de cada página**

Cada página ou funcionalidade deverá possuir uma documentação própria, contendo obrigatoriamente:

1. Identificação da página e sua rota.  
2. Objetivo.  
3. Perfil de usuário.  
4. Descrição do funcionamento.  
5. Fluxo de navegação.  
6. Funcionalidades identificadas.  
7. Regras de negócio.  
8. Separação das responsabilidades:  
   * Banco de dados.  
   * Lógica de programação / Backend.  
   * Frontend React.  
9. Operações CRUD necessárias.  
10. Endpoints necessários.  
11. Parâmetros e dados transmitidos.  
12. Filtros e consultas.  
13. Entidades e relacionamentos envolvidos.  
14. Impactos na modelagem do banco.  
15. Dependências com outras páginas ou funcionalidades.  
16. Critérios de conclusão.

A seção de separação das responsabilidades é obrigatória e deverá ser suficientemente detalhada para orientar a implementação.

## **12\. Diretriz final**

A documentação deve transformar as informações do protótipo e do fluxo de navegação em especificações técnicas claras.

O resultado precisa permitir que qualquer integrante compreenda:

* O que precisa ser armazenado no banco.  
* Quais relacionamentos precisam existir.  
* Quais regras o sistema deve respeitar.  
* O que o PHP precisa processar.  
* Quais endpoints precisam ser disponibilizados.  
* O que o React precisa apresentar e controlar.  
* Como as três camadas se comunicam.

A análise deve priorizar a compreensão dos requisitos e a separação das responsabilidades antes de iniciar a implementação.

Este documento constitui a referência técnica geral do projeto e deverá ser utilizado em conjunto com `docs/README.md`, `docs/AGENTS.md`, `docs/contrato_api_frontend.md`, `docs/padroes_de_codigo_php.md` e as especificações de cada funcionalidade.

## **13. Decisões de planejamento consolidadas**

| Código | Decisão | Consequência |
| ------ | ------- | ------------ |
| DP-001 | Backend híbrido: endpoints em `back-end/public/api/{perfil}/{recurso}/{acao}.php`; CRUD simples com SQL PDO no endpoint; fluxos complexos em `src/dominio/`; consultas compartilhadas em `src/consultas/`; serialização de DTOs em `src/contrato/`. | Menos arquivos que o modelo em camadas, sem abrir mão de transações e regras complexas; document root restrito a `public/`. |
| DP-002 | O MVP não terá autenticação real. O perfil é identificado por `?professor_id=` ou `?aluno_id=`; o admin não envia identificador. | O backend valida existência e propriedade, mas não impede falsificação. A evolução prevista é sessão ou token. |
| DP-003 | A nota individual final usa média ponderada normalizada. | A fórmula detalhada está na spec da Etapa 3; o ranking é calculado exclusivamente no PHP. |
| DP-004 | O mock de notas dos alunos é completo e idempotente. | Gera notas para todos os critérios, apenas para alunos presentes e não integrantes do grupo; não sobrescreve avaliações existentes. |
| DP-005 | A exclusão de alunos e professores é lógica, por meio de `status` ativo/inativo. | O histórico de notas e presenças é preservado; as listagens filtram ativos por padrão. |
| DP-006 | A exclusão de cursos com disciplinas e de disciplinas com seminários é bloqueada. | O backend retorna `409 Conflict` com mensagem orientando a remoção das dependências primeiro. |
| DP-007 | O SGBD oficial é MySQL 8.0.13 ou superior; o ambiente local usa MySQL 8.0.44. | O índice funcional de `avaliacoes_notas` é suportado; MariaDB/XAMPP não é o alvo. |
| DP-008 | As respostas JSON seguem o envelope e os códigos HTTP da seção 6.3.10. | O frontend trata `sucesso`, `dados`, `mensagem` e `erros` de forma uniforme. |
| DP-009 | O contrato JSON é a fonte de verdade da integração frontend ↔ API: JSON camelCase, query string snake_case, IDs numéricos na API e prefixados no frontend via `lib/services`. | Evita que o frontend dependa de nomes de colunas e reduz o retrabalho dos componentes. |
| DP-010 | O frontend acessa a API por `lib/services`; o `AppProvider` mantém a fachada de estado; o cálculo de resultados vem de `/api/professor/resultados/compilar.php` ou `/api/aluno/resultados/compilar.php`. | Componentes visuais mudam pouco e o cálculo de notas não é duplicado no React. |
| DP-011 | `cursos.sigla` é persistida e `Turma.nome` deriva dela. | A UI exibe `TSI`, `ES` etc. sem valor fixo nem derivação frágil. |
| DP-012 | `Criterio.descricao` foi removido do frontend e do contrato; `seminario_criterios` não possui coluna de descrição. | Critérios são nome, peso e tipo, exatamente como no schema. |
| DP-013 | Período existe apenas no cadastro de aluno, via `ano_matricula` + `semestre_entrada`. `Turma.periodo` foi removido. | Nenhuma tabela de curso/disciplina precisa de coluna de período. |
| DP-014 | Os scripts SQL são aplicados em ordem pelo cliente `mysql`; `DELIMITER $$` é diretiva do cliente, não do servidor. | O trigger é criado corretamente; `01_schema_ddl.sql` é destrutivo e usado só na inicialização. |
| DP-015 | O coordenador continua sendo a identidade fixa `u-coord`, sem tabela no banco. | Não há cadastro/login de admin no MVP; o perfil é apenas uma chave de UI. |
| DP-016 | `LiveState` (cronômetro, grupo selecionado, play/pause) permanece somente no cliente. | Recarregar a página reinicia o cronômetro; o backend persiste apenas status de sessão/grupo, notas, feedbacks e presença. |
| DP-017 | Integridade entre sessão e grupo de avaliação, e entre presença e matrícula na disciplina, é validada na aplicação. O banco cobre a exclusividade de grupo por data nos triggers de `INSERT` e `UPDATE`. | Evita regras ocultas em triggers para validações complexas; o endpoint retorna `400` com mensagem clara. |
| DP-018 | Credenciais locais ficam em `back-end/.env`, não versionado. `back-end/.env.example` é versionado sem senha; `.gitignore` da raiz ignora `.env` e `.env.*`, exceto o example. | Nenhuma senha entra no repositório; novos devs copiam o example e preenchem localmente. |
| DP-019 | `PROFESSOR_PADRAO=1` e `ALUNO_PADRAO=1` são constantes expostas pelo bootstrap. A query string continua obrigatória. | Testes são simplificados sem alterar o contrato nem enfraquecer a validação de identidade. |
| DP-020 | Comandos de aplicação do banco e de subida do servidor PHP ficam documentados em `docs/README.md` e `back-end/README.md`. | Qualquer dev reproduz o ambiente local com os mesmos comandos validados. |
| DP-021 | Exclusão em lote de alunos/professores usa `POST ...excluir-lote.php` com `{ "ids": number[] }`, validação prévia de todos os IDs e transação única. | A UI de seleção múltipla tem suporte real no backend, sem exclusões parciais. |
| DP-022 | Implementação em fases: Fase 1 entregou infraestrutura e admin, com integração das telas administrativas via `lib/services`. | Reduz risco e permite validar cada camada antes de avançar. |
| DP-023 | Fases seguintes: edição completa de seminário com diff por ID e proteção de histórico; filtro de resultados do aluno no backend; sincronização de grupos em lote. | Decisões registradas agora para manter contrato e documentação coerentes com a entrega prevista. |
| DP-024 | Edição de seminário usa diffs por ID para datas e critérios. Itens omitidos só são excluídos se não houver grupos, presenças ou notas; caso contrário, retorna `409`. | Histórico de avaliações nunca é destruído por uma edição descuidada. |
| DP-025 | Sincronização de grupos em lote usa `PUT .../sincronizar.php` com `id: null` para novos grupos e remove vínculos antes de reinserir para respeitar o trigger de exclusividade por data. | A UI pode substituir o array inteiro sem falsos conflitos ao mover alunos entre grupos. |
| DP-026 | Erros `SQLSTATE 45000` dos triggers são traduzidos pelo handler global para `400` com a mensagem da regra de negócio. | O frontend recebe mensagens legíveis em vez de um `500` genérico. |
| DP-027 | Os selects de curso/disciplina do formulário de seminário consomem a API e listam cursos/disciplinas cadastrados no banco. A propriedade do professor ainda é validada no cadastro/edição do seminário. | Nenhum dado mockado aparece no formulário; o usuário vê exatamente o que cadastrou na área admin. |
| DP-028 | A página de detalhes do seminário carrega o DTO da API (`detalhes.php`), os alunos via `alunos/listar.php` e groups/presença via endpoints de professor. O ID da URL é prefixado no frontend e convertido para numérico no service. | O redirecionamento após criar mostra os dados recém-salvos; não depende mais do estado em memória do `AppProvider`. |
| DP-029 | Dashboard e apresentação do professor consomem `listarSeminarios`, `carregarApresentacao` e `salvarAvaliacao`; o cronômetro permanece local. | O painel reflete seminários criados pela API e a avaliação é gravada no banco. |
| DP-030 | Resultados são compilados por `resultados/compilar.php` e renderizados por `ResultadoView` a partir do `ResultadoDTO`. | Mais nenhum cálculo de nota no React; o componente antigo `ResultsView` fica legado. |
| DP-031 | Grupos, presença e resultados são escopados por `seminario_data_id`, permitindo múltiplas datas independentes. | Trocar a data na UI recarrega corretamente grupos, chamada e ranking daquela sessão. |
| DP-032 | O status do seminário passa de `pendente` para `agendado` ao criar/editar o primeiro grupo, sincronizar grupos ou salvar a primeira chamada. | Regra RN-004 aplicada no backend, independente da tela que disparou a ação. |
| DP-033 | `apresentacao/carregar.php` devolve as notas e o feedback já salvos do professor por grupo. | Clicar em “Editar Avaliação” ou trocar de grupo e voltar recupera os valores persistidos, sem recarregar do zero. |
| DP-034 | Aluno só acessa seminários da disciplina em que está matriculado, validado no backend por `validar_matricula_seminario`. | Nenhuma tela do aluno depende de filtro no cliente para segurança. |
| DP-035 | Avaliação do aluno exige presença na chamada e bloqueia autoavaliação; as notas são upsertadas com `tipo_avaliador = 'aluno'`. | Regras RN-003/RN-004 aplicadas também no endpoint, não só na UI. |
| DP-036 | Resultados do aluno usam o mesmo compilador do professor com filtro de privacidade no backend. | O frontend nunca recebe notas/feedbacks de outros grupos além do ranking. |
| DP-037 | Formatos de resposta antes implícitos (`ApresentacaoDTO`, `FormularioAvaliacaoAlunoDTO`, chamada e encerramento) passam a ser definidos no contrato e amarrados ao consumidor na seção 9.4; o catálogo 6.3.7 é a fonte de verdade, com 19 rotas de professor presentes e duas pendentes. | A equipe reimplementa o backend com um único índice endpoint → DTO → tela, sem precisar inferir shapes das specs ou do código antigo. |
| DP-038 | A exclusão em lote de professores é implementada apesar da especificação antiga indicar que ficaria fora do MVP. | O catálogo da seção 6.3.7 e o `adminService.excluirProfessoresLote` do frontend já dependem dela; sem o endpoint a seleção múltipla falharia. |
| DP-039 | `GET /api/admin/disciplinas/listar.php` aceita `?curso_id=` opcional: sem o filtro devolve todas as disciplinas, com ele devolve apenas as do curso. | O service do frontend chama o endpoint nas duas formas; `query_int()` retorna `null` quando o parâmetro falta e `listar_disciplinas()` trata `null` como “sem filtro”. |

