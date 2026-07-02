#!/bin/bash
# Заливка email-verification на production
set -euo pipefail

HOST="${MF_DEPLOY_HOST:-root@104.248.62.230}"
REMOTE="${MF_DEPLOY_PATH:-/var/www/html/mendflow}"

echo "→ Deploy to ${HOST}:${REMOTE}"

scp \
  api/register.php \
  api/login.php \
  api/register-company.php \
  api/register-university.php \
  api/verify-email.php \
  api/auth_email.php \
  "${HOST}:${REMOTE}/api/"

scp js/api-base.js "${HOST}:${REMOTE}/js/"
scp index.html "${HOST}:${REMOTE}/"

echo ""
echo "✓ Готово. На сервере должно быть:"
echo "  grep installRegisterVerificationPatch ${REMOTE}/js/api-base.js"
echo "  grep requires_verification ${REMOTE}/api/register.php"
echo ""
echo "В браузере: Ctrl+Shift+R, затем регистрация с новым email."
