#!/usr/bin/env bash
# Полный архив для заливки на сервер (запускать на Mac после check-server-ready.sh).
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$ROOT"
OUT="${ROOT}/mendflow-deploy.tgz"

bash "$ROOT/check-server-ready.sh"

echo ""
echo "→ Упаковка $OUT ..."

tar czf "$OUT" \
  --exclude='.git' \
  --exclude='.env' \
  --exclude='.env.*' \
  --exclude='uploads/*' \
  --exclude='!uploads/.htaccess' \
  --exclude='.DS_Store' \
  --exclude='mendflow-deploy.tgz' \
  --exclude='auth-deploy.tgz' \
  --exclude='vendor' \
  --exclude='*.log' \
  -C "$ROOT" \
  api \
  js \
  icons \
  deploy \
  index.html \
  style.css \
  sw.js \
  manifest.json \
  .htaccess \
  .user.ini \
  composer.json \
  server-check.sh \
  server-update-auth.sh \
  check-server-ready.sh \
  pack-deploy.sh \
  pack-auth.sh \
  deploy-auth.sh \
  reset_email_verification.sql \
  mendflow_schema.sql \
  fix_missing_core_tables.sql \
  uploads/.htaccess

# uploads/.htaccess separately if exclude broke it
if ! tar tzf "$OUT" | grep -q 'uploads/.htaccess'; then
  tar rf "$OUT" uploads/.htaccess 2>/dev/null || true
fi

SIZE=$(du -h "$OUT" | cut -f1)
echo ""
echo "✓ Создан: $OUT ($SIZE)"
echo ""
echo "На сервере:"
echo "  cd /var/www/html/mendflow"
echo "  cp .env /tmp/mendflow.env.bak    # сохранить .env"
echo "  tar xzf /tmp/mendflow-deploy.tgz"
echo "  cp /tmp/mendflow.env.bak .env    # вернуть .env"
echo "  bash server-check.sh"
