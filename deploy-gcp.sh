#!/usr/bin/env bash
# Деплой на Google Cloud через git:
#   ./deploy-gcp.sh "сообщение коммита"   — commit + push + pull на сервере
#   ./deploy-gcp.sh                       — только push уже сделанных коммитов + pull
# Новые файлы в корне проекта добавляйте вручную: git add <файл>
set -euo pipefail

VM="${MF_GCP_VM:-mendflow}"
ZONE="${MF_GCP_ZONE:-us-central1-c}"
PROJECT="${MF_GCP_PROJECT:-project-cb6163a2-4afd-48bb-8d5}"
REMOTE_DIR="${MF_GCP_PATH:-/home/marufkhm3/mendflow}"
REMOTE_USER="${MF_GCP_OWNER:-marufkhm3}"
SITE="${MF_SITE_URL:-https://mendflow.us}"
BRANCH="main"

cd "$(dirname "$0")"

echo "→ Проверка проекта"
./check-server-ready.sh >/dev/null || { ./check-server-ready.sh; exit 1; }
echo "✓ check-server-ready OK"

if [ "$(git branch --show-current)" != "$BRANCH" ]; then
  echo "✗ Текущая ветка не $BRANCH"; exit 1
fi

if [ -n "$(git status --porcelain --untracked-files=no)" ] || { [ $# -gt 0 ] && [ -n "$(git status --porcelain)" ]; }; then
  if [ $# -eq 0 ]; then
    echo "✗ Есть незакоммиченные изменения. Запустите: ./deploy-gcp.sh \"описание изменений\""
    git status --short --untracked-files=no
    exit 1
  fi
  git add -u
  git add api js sql docs deploy icons 2>/dev/null || true
  if git diff --cached --name-only | grep -qE '(^|/)\.env$'; then
    echo "✗ .env попал в коммит — отмена"; git reset -q; exit 1
  fi
  if git diff --cached --quiet; then
    echo "• Нечего коммитить — выкатываю текущий коммит"
  else
    git commit -q -m "$1"
    echo "✓ Коммит: $1"
  fi
fi

echo "→ git push"
if ! git push -q origin "$BRANCH"; then
  echo "✗ GitHub отклонил push: на origin/$BRANCH есть коммиты, которых нет локально."
  echo "  Посмотрите: git fetch && git log --oneline HEAD..origin/$BRANCH"
  exit 1
fi
SHA="$(git rev-parse HEAD)"
echo "✓ push OK (${SHA:0:7})"

echo "→ git pull на сервере $VM"
gcloud compute ssh "$VM" --zone "$ZONE" --project "$PROJECT" --quiet --command "
  set -e
  cd '$REMOTE_DIR'
  test -f .env || { echo '✗ На сервере нет .env'; exit 1; }
  sudo -u '$REMOTE_USER' git fetch -q origin '$BRANCH'
  sudo -u '$REMOTE_USER' git reset -q --hard '$SHA'
  echo \"✓ сервер на коммите \$(sudo -u '$REMOTE_USER' git rev-parse --short HEAD)\"
  sudo systemctl reload php*-fpm 2>/dev/null || true
"

echo "→ Проверка сайта"
fail=0
for path in "api/ping.php" "api/db.php" "api/posts.php?page=1" "api/projects.php?action=explore"; do
  code=$(curl -s -o /dev/null -m 15 -w '%{http_code}' "$SITE/$path")
  if [ "$code" = "200" ]; then echo "✓ $path"; else echo "✗ $path → HTTP $code"; fail=1; fi
done
for path in ".git/config" ".env"; do
  code=$(curl -s -o /dev/null -m 15 -w '%{http_code}' "$SITE/$path")
  [ "$code" = "200" ] && { echo "! $path открыт в интернете — закройте в nginx"; fail=1; }
done

[ $fail -eq 0 ] && echo "=== Готово. Обновите страницу: Cmd+Shift+R ===" || { echo "=== Деплой прошёл, но есть проблемы (см. выше) ==="; exit 1; }
