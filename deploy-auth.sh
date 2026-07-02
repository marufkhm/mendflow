#!/bin/bash
# Заливка auth-файлов на production (email verification)
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

scp index.html "${HOST}:${REMOTE}/"

echo "✓ Done. Проверка register.php:"
curl -s -X POST "https://mendflow.us/api/register.php" \
  -H 'Content-Type: application/json' \
  -d '{"firstName":"X","lastName":"Y","email":"noop","password":"x"}' | head -c 120
echo
