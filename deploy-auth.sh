#!/bin/bash
# Деплой email-verification + auth на production
set -euo pipefail

HOST="${MF_DEPLOY_HOST:-root@104.248.62.230}"
REMOTE="${MF_DEPLOY_PATH:-/var/www/html/mendflow}"

echo "→ Deploy auth/verification to ${HOST}:${REMOTE}"

scp \
  api/register.php \
  api/login.php \
  api/register-company.php \
  api/register-university.php \
  api/verify-email.php \
  api/resend-verification.php \
  api/auth_email.php \
  api/db.php \
  "${HOST}:${REMOTE}/api/"

scp js/api-base.js "${HOST}:${REMOTE}/js/"
scp index.html "${HOST}:${REMOTE}/"

echo ""
echo "✓ Залито. На сервере проверь .env:"
echo "  RESEND_API_KEY=re_..."
echo "  MAIL_FROM=noreply@mendflow.us"
echo "  MAIL_SHOW_CODE=0"
echo ""
echo "Сброс верификации для теста (на сервере):"
echo "  mysql -u mendflow -p mendflow < reset_email_verification.sql"
