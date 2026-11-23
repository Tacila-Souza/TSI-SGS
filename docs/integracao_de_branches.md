# Integração de branches — SGS

> Este documento registra o estado das branches em 23/11/2026 e a estratégia para não perder a integração feita na branch `refactor/juncao-adm-professor`. Ele não substitui o contrato nem a documentação técnica.

## 1. Regra geral

A branch `refactor/juncao-adm-professor` é a base canônica: ela reconciliou admin, professor e aluno com a API e com o contrato JSON em camelCase.

- Branches **ancestrais** podem ser trazidas sem conflito: `origin/main`, `feat/administrador-alunos`, `feat/19-professor-integracao`, `feat/admin-professor+curso+disciplina`, `feat/professor-presenca-apresentacao`, `add-backend-gitkeep`.
- Branches **divergentes** não devem ser mescladas diretamente nesta base: `reabrir-professor-presenca-apresentacao`, `feat/professor-apresentacao`, `feat/aluno-avaliacao`, `feat/19-professor`, `setup-inicial`.

`origin/main` é ancestral da base canônica, portanto atualizações vindas de `main` são seguras. O risco aparece quando uma PR de branch divergente é mergeada no `main` e depois puxada para cá.

## 2. Branches divergentes e riscos

| Branch | Base | Conflitos textuais | Risco principal |
|---|---|---|---|
| `reabrir-professor-presenca-apresentacao` | `9133c09` | 3 A/A | Traz `dominio/elegibilidade.php`, `results-view.tsx` (legado) e um `require` no bootstrap que quebra todos os endpoints se o arquivo não vier junto. |
| `feat/professor-apresentacao` | `6fe96eb` | 22 A/A | Infraestrutura PHP e domínio de apresentação em versões concorrentes; traz as duas rotas que hoje já existem aqui, com regras próprias. |
| `feat/aluno-avaliacao` | `6fe96eb` | 13 A/A | `.env.example` com `DB_PASS=`, `conexao.php` como função `criar_conexao()` (aqui é `$pdo` global), `avaliacao_aluno.php` duplicando `avaliacao.php`, além de `package-lock.json`. |
| `feat/19-professor` | `6fe96eb` | 18 A/A | Versão antiga do módulo de professor; já substituída por `feat/19-professor-integracao`. |
| `setup-inicial` | `de6a1bd` | 1 A/A | Apenas `back-end/.gitkeep`. |

## 3. Conflitos silenciosos (o maior perigo)

Alguns arquivos são alterados **somente** no lado divergente. O Git resolve o merge sem conflito, mas o resultado é incoerente com esta base:

- `back-end/src/bootstrap.php` e `back-end/src/contrato/serializadores.php` (branch `reabrir-...`): aceitar a versão deles insere, em `bootstrap.php`, o `require_once .../dominio/elegibilidade.php` — arquivo inexistente aqui → `Fatal error` em todos os endpoints.
- `front-end/lib/services/api.ts` (branches `feat/professor-apresentacao` e `feat/19-professor`): volta ao helper antigo, incompatível com `lib/services/*.ts` atual.
- `.gitignore` e `package-lock.json` (branch `feat/aluno-avaliacao`): reintroduzem lockfile concorrente com `pnpm-lock.yaml`.

## 4. Estratégia recomendada

1. Manter a versão desta base ("ours") em todos os arquivos de infraestrutura, domínio, services e documentação.
2. Não trazer `elegibilidade.php`, `apresentacoes.php`, `avaliacao_aluno.php`, `serializacao.php`, `seminario.php`, `results-view.tsx` nem `package-lock.json`.
3. Recuperar funcionalidades faltantes por **reimplementação** sobre as funções já existentes, nunca por cópia direta.
4. Se for necessário marcar uma branch antiga como incorporada, usar `git merge -s ours <branch>` e registrar o motivo no PR.
5. Após qualquer integração, rodar `pnpm typecheck`, `pnpm build`, `./scripts/smoke-api.sh` e conferir `git diff` manualmente — a ausência de conflito não garante compatibilidade.
