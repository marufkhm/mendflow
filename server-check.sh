#!/usr/bin/env bash
# Проверка на СЕРВЕРЕ после заливки файлов.
# Запуск: cd /var/www/html/mendflow && bash server-check.sh
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$ROOT"
APP_URL="${MF_APP_URL:-https://mendflow.us}"

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

errors=0
warnings=0

fail() { echo -e "${RED}✗${NC} $1"; errors=$((errors + 1)); }
ok()   { echo -e "${GREEN}✓${NC} $1"; }
warn() { echo -e "${YELLOW}!${NC} $1"; warnings=$((warnings + 1)); }

echo "=== Mendflow: проверка на сервере ==="
echo "Путь: $ROOT"
echo ""

# ── Файлы ─────────────────────────────────────────────────────
CRITICAL=(
  api/register.php api/login.php api/verify-email.php api/resend-verification.php
  api/auth_email.php api/mail.php api/auth.php api/db.php api/config.php
  js/api-base.js index.html .htaccess
)
for f in "${CRITICAL[@]}"; do
  [[ -f "$f" ]] || fail "нет: $f"
done
[[ "$errors" -eq 0 ]] && ok "критичные файлы на месте"

grep -q 'requires_verification' api/register.php \
  && ok 'register.php — новая версия' \
  || fail 'register.php СТАРЫЙ (нет requires_verification)'

grep -q 'installRegisterVerificationPatch' js/api-base.js \
  && ok 'api-base.js — патч верификации' \
  || fail 'api-base.js СТАРЫЙ'

grep -q 'needsVerify' index.html \
  && ok 'index.html — шаг verify' \
  || fail 'index.html СТАРЫЙ'
echo ""

# ── .env ──────────────────────────────────────────────────────
echo "→ .env"
if [[ ! -f .env ]]; then
  fail ".env отсутствует — скопируйте с .env.example и заполните DB_*, RESEND_API_KEY"
else
  ok ".env существует"
  for key in DB_HOST DB_USER DB_PASS DB_NAME APP_URL; do
    grep -q "^${key}=" .env && grep "^${key}=" .env | grep -qv '=$' \
      && ok "$key задан" \
      || warn "$key пустой или отсутствует"
  done
  grep -q '^RESEND_API_KEY=re_' .env \
    && ok "RESEND_API_KEY задан" \
    || warn "RESEND_API_KEY не задан — письма не уйдут (включите MAIL_SHOW_CODE=1 для теста)"
fi
echo ""

# ── Права uploads ─────────────────────────────────────────────
echo "→ uploads/"
if [[ -d uploads ]]; then
  if [[ -w uploads ]]; then
    ok "uploads/ доступна для записи"
  else
    fail "uploads/ не writable — chmod 775 uploads && chown www-data:www-data uploads"
  fi
else
  fail "нет папки uploads/ — mkdir -p uploads && chmod 775 uploads"
fi
echo ""

# ── PHP-FPM ───────────────────────────────────────────────────
echo "→ PHP limits"
if command -v php >/dev/null 2>&1; then
  up=$(php -r 'echo ini_get("upload_max_filesize");')
  post=$(php -r 'echo ini_get("post_max_size");')
  ok "upload_max_filesize=$up post_max_size=$post"
  if [[ -f /etc/nginx/conf.d/mendflow-upload-limits.conf ]]; then
    ok "nginx client_max_body_size настроен"
  else
    warn "нет /etc/nginx/conf.d/mendflow-upload-limits.conf — возможен 413 при загрузке фото"
    warn "  sudo cp deploy/nginx-upload-limits.conf /etc/nginx/conf.d/mendflow-upload-limits.conf && sudo nginx -t && sudo systemctl reload nginx"
  fi
fi
echo ""

# ── HTTP API ──────────────────────────────────────────────────
echo "→ HTTP тесты ($APP_URL)"
if command -v curl >/dev/null 2>&1; then
  PING=$(curl -sS "${APP_URL}/api/ping.php" 2>/dev/null || echo '{}')
  echo "$PING" | grep -q '"ok"' \
    && ok "ping.php OK" \
    || fail "ping.php: $PING"

  LIMITS=$(curl -sS "${APP_URL}/api/ping.php?limits=1" 2>/dev/null || echo '{}')
  if echo "$LIMITS" | grep -q 'upload_max_filesize'; then
    UP=$(echo "$LIMITS" | grep -o '"upload_max_filesize":"[^"]*"' | cut -d'"' -f4)
    PO=$(echo "$LIMITS" | grep -o '"post_max_size":"[^"]*"' | cut -d'"' -f4)
    ok "PHP-FPM limits: upload=$UP post=$PO"
    if [[ "$UP" == "2M" ]] || [[ "$PO" == "8M" ]]; then
      warn "PHP-FPM всё ещё 2M/8M — выполните: sudo bash deploy/fix-413-upload.sh"
    fi
  fi

  TEST_EMAIL="srv-$(date +%s)@example.com"
  REG=$(curl -sS -X POST "${APP_URL}/api/register.php" \
    -H "Content-Type: application/json" \
    -d "{\"firstName\":\"Srv\",\"lastName\":\"Check\",\"email\":\"${TEST_EMAIL}\",\"password\":\"SrvCheck1!\"}" 2>/dev/null || echo '{}')

  if echo "$REG" | grep -q '"requires_verification"'; then
    ok "register API → requires_verification"
  elif echo "$REG" | grep -q '"token"'; then
    fail "register API всё ещё выдаёт token — файлы не обновились или другой каталог"
  else
    fail "register API: $REG"
  fi
else
  warn "curl не найден — HTTP тесты пропущены"
fi
echo ""

# ── Итог ──────────────────────────────────────────────────────
echo "============================================"
if [[ "$errors" -gt 0 ]]; then
  echo -e "${RED}FAILED: $errors ошибок${NC}"
  exit 1
fi
echo -e "${GREEN}OK: сервер готов${NC}"
[[ "$warnings" -gt 0 ]] && echo "Предупреждений: $warnings — проверьте .env и nginx"
