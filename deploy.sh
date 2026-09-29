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
# -4: обход «Network is unreachable» при битом IPv6 на Mac/Wi‑Fi
SSH_OPTS="${MF_SSH_OPTS:--4 -o ConnectTimeout=20}"

deploy_fail_network() {
  echo ""
  echo "✗ Не удалось подключиться к ${HOST}"
  echo "  Проверка с Mac:"
  echo "    ping -c 2 104.248.62.230"
  echo "    ssh ${SSH_OPTS} ${HOST}"
  echo "    curl -I ${APP_URL}"
  echo ""
  echo "  Частые причины: нет интернета, VPN/файрвол блокирует порт 22, другая Wi‑Fi сеть."
  echo "  Если ssh вручную работает — повторите: ./deploy.sh"
  echo "  Иначе: ./pack-deploy.sh и залейте mendflow-deploy.tgz через панель DigitalOcean."
  exit 1
}

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
  if ! rsync -avz --progress -e "ssh ${SSH_OPTS}" "${RSYNC_EXCLUDES[@]}" \
    "$ROOT/" "${HOST}:${REMOTE}/"; then
    deploy_fail_network
  fi
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
  scp $SSH_OPTS "$ROOT/mendflow-deploy.tgz" "${HOST}:/tmp/mendflow-deploy.tgz" || deploy_fail_network
  echo "→ распаковка на сервере"
  ssh $SSH_OPTS "${HOST}" bash -s <<EOF || deploy_fail_network
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
  scp $SSH_OPTS \
    api/register.php api/register-company.php api/register-university.php \
    api/login.php api/verify-email.php api/resend-verification.php \
    api/auth_email.php api/mail.php api/config.php api/db.php api/auth.php \
    "${HOST}:${REMOTE}/api/" || deploy_fail_network
  scp $SSH_OPTS js/api-base.js "${HOST}:${REMOTE}/js/" || deploy_fail_network
  scp $SSH_OPTS index.html "${HOST}:${REMOTE}/" || deploy_fail_network
  echo "✓ Auth залит"
}

do_check_remote() {
  ssh $SSH_OPTS "$HOST" "cd ${REMOTE} && bash server-check.sh" || do_remote_smoke
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
