#!/usr/bin/env bash
# Быстрый деплой Mendflow с Mac на сервер.
#
# Использование:
#   ./deploy.sh          # rsync — быстро (рекомендуется каждый день)
#   ./deploy.sh sync     # то же
#   ./deploy.sh full     # архив + scp + распаковка на сервере
#   ./deploy.sh auth     # только auth-файлы
#   ./deploy.sh check    # только проверка production
#
# Настройки (опционально в ~/.zshrc):
#   export MF_DEPLOY_HOST=root@104.248.62.230
#   export MF_DEPLOY_PATH=/var/www/html/mendflow
#   export MF_APP_URL=https://mendflow.us
#
# Один раз — вход без пароля:
#   ssh-copy-id root@104.248.62.230

set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$ROOT"

HOST="${MF_DEPLOY_HOST:-root@104.248.62.230}"
REMOTE="${MF_DEPLOY_PATH:-/var/www/html/mendflow}"
APP_URL="${MF_APP_URL:-https://mendflow.us}"
MODE="${1:-sync}"

RSYNC_EXCLUDES=(
  --exclude '.git/'
  --exclude '.env'
  --exclude '.env.*'
  --exclude 'uploads/'
  --exclude 'vendor/'
  --exclude '.DS_Store'
  --exclude '*.tgz'
  --exclude '*.log'
)

do_check_local() {
  bash "$ROOT/check-server-ready.sh"
}

do_sync() {
  echo "→ rsync → ${HOST}:${REMOTE}"
  rsync -avz --progress "${RSYNC_EXCLUDES[@]}" \
    "$ROOT/" "${HOST}:${REMOTE}/"
  echo "✓ Синхронизация завершена"
}

do_remote_smoke() {
  echo "→ Проверка ${APP_URL} ..."
  curl -sf "${APP_URL}/api/ping.php" >/dev/null && echo "✓ ping OK" || echo "! ping failed"
  LIMITS=$(curl -sS "${APP_URL}/api/ping.php?limits=1" 2>/dev/null || echo '{}')
  if echo "$LIMITS" | grep -q upload_max_filesize; then
    echo "  PHP-FPM: $LIMITS"
  fi
}

do_full() {
  do_check_local
  bash "$ROOT/pack-deploy.sh"
  echo "→ scp mendflow-deploy.tgz"
  scp "$ROOT/mendflow-deploy.tgz" "${HOST}:/tmp/mendflow-deploy.tgz"
  echo "→ распаковка на сервере"
  ssh "$HOST" bash -s <<EOF
set -e
cd ${REMOTE}
cp .env /tmp/mendflow.env.bak 2>/dev/null || true
tar xzf /tmp/mendflow-deploy.tgz
cp /tmp/mendflow.env.bak .env 2>/dev/null || true
bash server-check.sh 2>/dev/null || echo "(server-check.sh — запустите вручную при необходимости)"
EOF
  echo "✓ Полный деплой завершён"
}

do_auth() {
  echo "→ auth → ${HOST}:${REMOTE}"
  scp \
    api/register.php api/register-company.php api/register-university.php \
    api/login.php api/verify-email.php api/resend-verification.php \
    api/auth_email.php api/mail.php api/config.php api/db.php api/auth.php \
    "${HOST}:${REMOTE}/api/"
  scp js/api-base.js "${HOST}:${REMOTE}/js/"
  scp index.html "${HOST}:${REMOTE}/"
  echo "✓ Auth залит"
}

do_check_remote() {
  ssh "$HOST" "cd ${REMOTE} && bash server-check.sh" || do_remote_smoke
}

case "$MODE" in
  sync|fast|push|'')
    do_check_local
    do_sync
    do_remote_smoke
    ;;
  full|tar)
    do_full
    do_remote_smoke
    ;;
  auth)
    do_auth
    do_remote_smoke
    ;;
  check)
    do_check_remote
    ;;
  *)
    echo "Неизвестный режим: $MODE"
    echo "Режимы: sync | full | auth | check"
    exit 1
    ;;
esac

echo ""
echo "Готово. В браузере: Ctrl+Shift+R"
