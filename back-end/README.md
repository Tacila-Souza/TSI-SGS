# SGS — Backend PHP

API JSON em PHP puro, sem framework. Document root: `public/`.

## Estrutura

```
back-end/
├── .env              # credenciais locais (não versionado)
├── .env.example      # modelo versionado, sem senha
├── public/api/       # um arquivo por operação
└── src/              # código interno, nunca exposto via HTTP
```

## Configuração local

1. Copie o modelo:
   ```bash
   cp .env.example .env
   ```
2. Preencha `DB_HOST`, `DB_PORT` e `DB_PASS` em `.env` conforme o seu MySQL. A porta padrão do modelo é `3306`, mas o ambiente de referência usa `3308`; confirme com `mysql -u root -p -e "SHOW VARIABLES LIKE 'port';"`. Não versionar a senha real.
3. O banco `gestao_seminarios` deve existir. Veja `README.md`, seção 8.1, para aplicar os scripts SQL.

Variáveis disponíveis:

| Variável | Descrição |
|---|---|
| `DB_HOST` | Host do MySQL (padrão `127.0.0.1`) |
| `DB_PORT` | Porta real do MySQL (padrão `3306`; neste ambiente, `3308`) |
| `DB_NAME` | Nome do banco (padrão `gestao_seminarios`) |
| `DB_USER` | Usuário (padrão `root`) |
| `DB_PASS` | Senha local |
| `APP_ORIGIN` | Origem permitida no CORS |
| `PROFESSOR_PADRAO` | ID do professor usado pelo frontend nos testes (padrão `1`) |
| `ALUNO_PADRAO` | ID do aluno usado pelo frontend nos testes (padrão `1`) |

## Subindo o servidor

Na raiz do projeto:

```bash
php -S localhost:8000 -t back-end/public
```

URL base da API: `http://localhost:8000` — os endpoints já incluem `/api/...`.

Exemplo:

```bash
curl "http://localhost:8000/api/admin/alunos/listar.php"
```

## Identidade no MVP

- Admin: sem identificador (`u-coord` é fixo, sem tabela).
- Professor: `?professor_id=1`.
- Aluno: `?aluno_id=1`.

As constantes `PROFESSOR_PADRAO` e `ALUNO_PADRAO` ficam disponíveis no bootstrap. O parâmetro na query string continua obrigatório conforme o contrato.

## Convenções

- JSON sempre camelCase; query string snake_case.
- Envelope: `{ "sucesso": true, "dados": ... }` ou `{ "sucesso": false, "mensagem": ..., "erros": ... }`.
- Nunca imprimir HTML, `var_dump` ou espaços antes do JSON.
