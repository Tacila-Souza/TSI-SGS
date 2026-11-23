#!/usr/bin/env bash
# Smoke test da API SGS: exige MySQL aplicado e php -S na porta 8000.
set -euo pipefail

BASE="${API_BASE_URL:-http://localhost:8000}"
TEMP="$(mktemp)"
trap 'rm -f "$TEMP"' EXIT

verificar() {
  local descricao="$1"
  local url="$2"

  local codigo
  codigo="$(curl -s -o "$TEMP" -w '%{http_code}' "$url")"

  if [ "$codigo" != "200" ]; then
    echo "FALHA: $descricao -> HTTP $codigo" >&2
    head -c 300 "$TEMP" >&2
    echo >&2
    exit 1
  fi

  if ! php -r '
    $dados = json_decode(file_get_contents($argv[1]), true);
    exit(json_last_error() === JSON_ERROR_NONE
      && isset($dados["sucesso"])
      && $dados["sucesso"] === true
      && array_key_exists("dados", $dados) ? 0 : 1);
  ' "$TEMP"; then
    echo "FALHA: $descricao -> envelope JSON inválido" >&2
    head -c 300 "$TEMP" >&2
    echo >&2
    exit 1
  fi

  echo "OK: $descricao"
}

verificar "admin/alunos/listar" "$BASE/api/admin/alunos/listar.php"
verificar "professor/seminarios/listar" "$BASE/api/professor/seminarios/listar.php?professor_id=1"
verificar "aluno/seminarios/listar" "$BASE/api/aluno/seminarios/listar.php?aluno_id=1"

echo "Smoke test concluído."
