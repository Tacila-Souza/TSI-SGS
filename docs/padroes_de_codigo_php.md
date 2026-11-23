# Padrões de Código PHP

> **Modelo aprovado: híbrido.** O projeto continua com PHP puro, PDO e prepared statements. CRUD simples pode ter SQL no próprio endpoint; fluxos complexos ficam em `src/dominio/`; consultas compartilhadas podem ir para `src/consultas/`; a conversão para DTO fica em `src/contrato/`.
>
> **Leia antes:** `README.md`, `docs/documentacao_de_contextualizacao_tecnica.md` e `docs/contrato_api_frontend.md`.
>
> **Atenção:** os exemplos de formulário HTML com `header('Location: ...')` no final deste arquivo são didáticos e **não** devem ser usados nos endpoints da API JSON.

## 1. Princípios

- PHP puro, sem framework, sem ORM e sem biblioteca de backend.
- Um arquivo PHP representa uma única operação.
- Toda consulta usa PDO com prepared statements.
- Nenhum endpoint imprime HTML, `var_dump`, avisos ou espaços antes do JSON.
- A API devolve DTOs em camelCase, nunca linhas cruas do banco.
- O endpoint valida método, identidade, entrada e propriedade do recurso.
- Operações com mais de uma tabela usam transação.
- O cálculo de notas, o mock e as regras complexas ficam no backend.

## 2. Estrutura de referência

```text
back-end/
├── public/
│   └── api/
│       ├── admin/...
│       ├── professor/...
│       └── aluno/...
└── src/
    ├── bootstrap.php
    ├── config/
    │   ├── ambiente.php
    │   └── conexao.php
    ├── http/
    │   ├── cors.php
    │   ├── erro.php
    │   ├── requisicao.php
    │   └── resposta.php
    ├── auth/
    │   └── identidade.php
    ├── validacao/
    │   └── validadores.php
    ├── contrato/          # serializadores dos DTOs
    ├── dominio/           # regras complexas e transações
    └── consultas/         # SQL compartilhado (opcional)
```

- Apenas `back-end/public/` é exposto pelo servidor.
- `src/` nunca é acessível via HTTP.
- `src/dominio/` não lê `$_GET`/`$_POST`; recebe dados já validados.
- `src/consultas/` existe apenas quando a mesma consulta é usada por mais de um endpoint.
- `src/contrato/` concentra a conversão de `snake_case` do banco para `camelCase` da API.

## 3. Endpoint simples (CRUD)

Use SQL no próprio endpoint quando a operação envolver **uma tabela, sem transação e sem cálculo**.

```php
<?php
declare(strict_types=1);

require dirname(__DIR__, 4) . '/src/bootstrap.php';

exigir_metodo('GET');
exigir_identidade('admin');

$busca = trim((string) ($_GET['busca'] ?? ''));

$sql = 'SELECT id, nome_completo, email, matricula, ano_matricula, semestre_entrada, status
        FROM alunos
        WHERE status = "ativo"';
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

Regras do exemplo:

- `exigir_metodo()` e `exigir_identidade()` vêm antes de qualquer consulta.
- A SQL usa placeholders; valores não são concatenados.
- `serializar_aluno()` converte a linha do banco para o DTO do contrato.
- `responder_sucesso()` define o `Content-Type`, o código HTTP e o envelope JSON.

## 4. Endpoint complexo

Use `src/dominio/` quando a operação envolver **duas ou mais tabelas, transação, cálculo, mock ou operação em lote**.

```php
<?php
declare(strict_types=1);

require dirname(__DIR__, 4) . '/src/bootstrap.php';

exigir_metodo('POST');
$professorId = exigir_identidade('professor');
$dados = ler_json();

responder_sucesso(criar_seminario($pdo, $professorId, $dados), 201);
```

Exemplos de fluxos complexos:

- Cadastrar/editar seminário com datas e critérios.
- Cadastrar/editar disciplina com vínculos de alunos.
- Cadastrar/editar grupo com integrantes.
- Salvar chamada em lote.
- Salvar avaliação do professor + mock dos alunos.
- Compilar resultados e ranking.
- Encerrar sessão.

A função de domínio:

- recebe `$pdo` e dados já validados;
- abre a transação;
- executa as operações;
- faz `commit()` ou `rollBack()`;
- devolve um array/DTO;
- não imprime nada e não lê a superglobal.

## 5. Conexão PDO

A conexão canônica fica em `src/config/conexao.php` e é carregada pelo bootstrap:

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

- Credenciais reais não são versionadas.
- O banco oficial é MySQL 8.0.13+.
- Não usar `root/root` fixo no código da API.

## 6. Serialização e contrato

A API entrega camelCase e IDs numéricos. O frontend converte os IDs para strings prefixadas em `lib/services`.

Exemplo de serializador em `src/contrato/aluno.php`:

```php
<?php
declare(strict_types=1);

function serializar_aluno(array $row): array
{
    return [
        'id' => (int) $row['id'],
        'nome' => $row['nome_completo'],
        'email' => $row['email'],
        'role' => 'aluno',
        'status' => $row['status'],
        'registro' => $row['matricula'],
        'turmaIds' => array_map('intval', $row['turma_ids'] ?? []),
        'anoMatricula' => (int) $row['ano_matricula'],
        'semestreEntrada' => (string) $row['semestre_entrada'],
    ];
}
```

Observações:

- `turmaIds` exige uma consulta/join adicional; o exemplo é ilustrativo.
- O DTO completo está em `docs/contrato_api_frontend.md`.
- Não colocar SQL em `src/contrato/`; o serializador recebe a linha já carregada.
- Não devolver `nome_completo`, `ano_matricula` ou `matricula` para o frontend.

## 7. Segurança e validação

- Validar o método HTTP com `exigir_metodo()`.
- Validar a identidade com `exigir_identidade('admin'|'professor'|'aluno')`.
- Validar a propriedade do recurso antes de atualizar/excluir.
- Validar tipos, campos obrigatórios e formatos com `src/validacao/validadores.php`.
- Usar prepared statements em todas as consultas.
- Não confiar em validação feita só no React.
- Não expor SQL, stack trace ou detalhes internos no JSON.
- Em produção, `display_errors` desligado e `log_errors` ligado.

## 8. Transações

Use transação quando a operação alterar mais de uma tabela ou precisar de atomicidade:

| Operação | Tabelas envolvidas |
| --- | --- |
| Cadastrar/editar disciplina | `disciplinas`, `disciplina_aluno` |
| Cadastrar/editar seminário | `seminarios`, `seminario_datas`, `seminario_criterios` |
| Cadastrar/editar grupo | `grupos`, `grupo_alunos` |
| Salvar chamada em lote | `presencas` |
| Salvar avaliação | `avaliacoes_notas`, `avaliacoes_feedbacks` (incluindo mock) |
| Sortear ordem | `grupos` |
| Encerrar sessão | `seminario_datas` e validação de grupos pendentes |

Fluxo:

```php
try {
    $pdo->beginTransaction();
    // operações
    $pdo->commit();
} catch (Throwable $erro) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    throw $erro;
}
```

## 9. O que não fazer

- Não usar framework, ORM ou biblioteca de backend.
- Não criar `index.php`, `processa.php`, `crud.php` ou `/api/comum`.
- Não usar `PATCH`; o padrão é `PUT`.
- Não concatenar valores na SQL.
- Não colocar HTML, `header('Location: ...')` ou `var_dump` em endpoint da API.
- Não colocar regra complexa no endpoint; extrair para `src/dominio/`.
- Não colocar SQL em `src/contrato/`.
- Não apagar fisicamente alunos/professores.
- Não recalcular notas no React.
- Não copiar SQL das specs; usar `database/01_schema_ddl.sql`.
- Não importar `mock-data.ts` em código novo do frontend.

## 10. Checklist de um endpoint

- [ ] O endpoint está no catálogo da seção 6.3.7.
- [ ] O método HTTP está correto.
- [ ] A identidade foi exigida e a propriedade validada.
- [ ] A entrada foi lida e validada.
- [ ] A operação é simples (SQL no endpoint) ou complexa (domínio).
- [ ] Prepared statements em todas as consultas.
- [ ] Transação quando necessário.
- [ ] O DTO segue o contrato JSON.
- [ ] O envelope e o código HTTP estão corretos.
- [ ] A documentação foi atualizada se o payload mudou.

---

## Apêndice — exemplos originais (didáticos)

> Os exemplos abaixo foram mantidos como referência do modelo original de PDO e prepared statements. Eles usam formulário HTML e redirect e **não** representam o padrão da API JSON.

### A.1. `update_carro_form.php`

```php
<?php

require 'conexao.php';

$id = $_GET['id'];

$consulta = $pdo->prepare('SELECT * FROM carros WHERE id = ?');
$consulta->execute([$id]);

$carro = $consulta->fetch(PDO::FETCH_ASSOC);

?>

<form method="post" action="update_carro_action.php">
    <input name="id" type="hidden" value="<?= $carro['id'] ?>">

    Marca: <input name="marca" value="<?= $carro['marca'] ?>"><br>
    Modelo: <input name="modelo" value="<?= $carro['modelo'] ?>"><br>
    Data de revisão: <input type="date" name="data_revisao" value="<?= $carro['data_revisao'] ?>"><br>
    ano: <input type="number" name="ano" value="<?= $carro['ano'] ?>"><br>
    <button>Atualizar</button>
</form>
```

### A.2. `update_carro_action.php`

```php
<?php

require 'conexao.php';

$marca = $_POST['marca'];
$modelo = $_POST['modelo'];
$data_revisao = $_POST['data_revisao'];
$ano = $_POST['ano'];
$id = $_POST['id'];

$comando = $pdo->prepare(
    'UPDATE carros SET marca = ?, modelo = ?, data_revisao = ?, ano = ?
     WHERE id = ?'
);
$comando->execute([ $marca, $modelo, $data_revisao, $ano, $id ]);

header('Location: /index_carro.php');
exit;
```

### A.3. `conexao.php` didático

```php
<?php
$dbname = 'crud_concessionaria';
$usuario = 'root';
$senha = 'root';
$porta = 3306;

$pdo = new PDO(
    "mysql:host=127.0.0.1;port=$porta;dbname=$dbname;charset=utf8mb4",
    $usuario,
    $senha
);
```

**Para a API do SGS, use a conexão com variáveis de ambiente da seção 5.**
