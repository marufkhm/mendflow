#!/bin/bash
# Деплой email-verification + auth на production
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$ROOT"

HOST="${MF_DEPLOY_HOST:-root@104.248.62.230}"
REMOTE="${MF_DEPLOY_PATH:-/var/www/html/mendflow}"
APP_URL="${MF_APP_URL:-https://mendflow.us}"

echo "→ Deploy auth/verification to ${HOST}:${REMOTE}"

scp \
  api/register.php \
  api/register-company.php \
  api/register-university.php \
  api/login.php \
  api/verify-email.php \
  api/resend-verification.php \
  api/auth_email.php \
  api/mail.php \
  api/config.php \
  api/db.php \
  "${HOST}:${REMOTE}/api/"

scp js/api-base.js "${HOST}:${REMOTE}/js/"
scp index.html "${HOST}:${REMOTE}/"

echo ""
echo "→ Проверка production API..."
TEST_EMAIL="deploy-check-$(date +%s)@example.com"
RESP=$(curl -sS -X POST "${APP_URL}/api/register.php" \
  -H "Content-Type: application/json" \
  -d "{\"firstName\":\"Deploy\",\"lastName\":\"Check\",\"email\":\"${TEST_EMAIL}\",\"password\":\"DeployCheck1!\"}")

if echo "$RESP" | grep -q '"requires_verification"'; then
  echo "✓ register.php: requires_verification OK"
elif echo "$RESP" | grep -q '"token"'; then
  echo "✗ register.php ВСЁ ЕЩЁ СТАРЫЙ (возвращает token). Проверь путь ${REMOTE} на сервере."
  exit 1
else
  echo "? Неожиданный ответ: $RESP"
fi

if curl -sS "${APP_URL}/js/api-base.js" | grep -q 'installRegisterVerificationPatch'; then
  echo "✓ api-base.js: патч верификации на месте"
else
  echo "✗ api-base.js без патча — обнови кэш CDN/браузера или проверь путь js/"
  exit 1
fi

echo ""
echo "✓ Деплой завершён. На сервере проверь .env в ${REMOTE}:"
echo "  RESEND_API_KEY=re_..."
echo "  MAIL_FROM=noreply@mendflow.us"
echo "  APP_URL=https://mendflow.us"
echo "  MAIL_SHOW_CODE=0"
echo ""
echo "Сброс верификации для теста (на сервере):"
echo "  mysql -u mendflow -p mendflow < reset_email_verification.sql"
