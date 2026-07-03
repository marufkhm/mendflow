#!/usr/bin/env bash
# Проверка БД Mendflow: таблицы, колонки, строки, целостность.
#
# Локально / на сервере:
#   ./db-check.sh
#   DB_USER=mendflow DB_PASS=secret DB_NAME=mendflow ./db-check.sh
#
# Только отчёт в файл:
#   ./db-check.sh > db-report.txt

set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$ROOT"

DB_HOST="${DB_HOST:-localhost}"
DB_USER="${DB_USER:-mendflow}"
DB_NAME="${DB_NAME:-mendflow}"
DB_PASS="${DB_PASS:-}"

SQL_FILE="$ROOT/check_database.sql"

if [[ ! -f "$SQL_FILE" ]]; then
  echo "Нет файла check_database.sql"
  exit 1
fi

if ! command -v mysql >/dev/null 2>&1; then
  echo "mysql CLI не найден. Откройте check_database.sql в DBeaver и выполните (Alt+X)."
  exit 1
fi

MYSQL_OPTS=(-h "$DB_HOST" -u "$DB_USER" "$DB_NAME" --table)
if [[ -n "$DB_PASS" ]]; then
  MYSQL_OPTS+=(-p"$DB_PASS")
else
  MYSQL_OPTS+=(-p)
fi

echo "=== Mendflow DB check: $DB_NAME @ $DB_HOST ==="
echo ""

mysql "${MYSQL_OPTS[@]}" < "$SQL_FILE"
