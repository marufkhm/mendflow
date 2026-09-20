#!/bin/bash
# Упаковать auth-файлы для заливки на сервер (запускать на Mac).
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$ROOT"
OUT="${ROOT}/auth-deploy.tgz"

tar czf "$OUT" \
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
  api/auth.php \
  js/api-base.js \
  index.html \
  server-update-auth.sh \
  reset_email_verification.sql

echo "Создан: $OUT"
echo ""
echo "На Mac:"
echo "  scp auth-deploy.tgz root@104.248.62.230:/tmp/"
echo ""
echo "На сервере:"
echo "  cd /var/www/html/mendflow && tar xzf /tmp/auth-deploy.tgz && bash server-update-auth.sh"
