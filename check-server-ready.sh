#!/usr/bin/env bash
# Проверка проекта перед заливкой на сервер (Mac или Linux).
# Запуск: ./check-server-ready.sh
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$ROOT"

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

errors=0
warnings=0

fail() { echo -e "${RED}✗${NC} $1"; errors=$((errors + 1)); }
ok()   { echo -e "${GREEN}✓${NC} $1"; }
warn() { echo -e "${YELLOW}!${NC} $1"; warnings=$((warnings + 1)); }

echo "=== Mendflow: проверка перед деплоем на сервер ==="
echo "Каталог: $ROOT"
echo ""

# ── PHP syntax ────────────────────────────────────────────────
echo "→ PHP syntax (api/*.php)..."
if ! command -v php >/dev/null 2>&1; then
  warn "php CLI не найден — пропуск syntax check"
else
  while IFS= read -r -d '' f; do
    if ! php -l "$f" >/dev/null 2>&1; then
      fail "syntax error: $f"
      php -l "$f" || true
    fi
  done < <(find api -name '*.php' -print0)
  ok "PHP syntax OK"
fi
echo ""

# ── Обязательные файлы ────────────────────────────────────────
echo "→ Обязательные файлы..."
REQUIRED=(
  index.html
  style.css
  sw.js
  manifest.json
  .htaccess
  .user.ini
  .env.example
  api/db.php
  api/config.php
  api/ping.php
  api/login.php
  api/register.php
  api/register-company.php
  api/register-university.php
  api/verify-email.php
  api/resend-verification.php
  api/auth_email.php
  api/mail.php
  api/auth.php
  api/upload.php
  api/posts.php
  api/users.php
  api/feed_ranking.php
  api/badges_lib.php
  api/.htaccess
  api/.user.ini
  js/api-base.js
  js/pwa.js
  js/realtime.js
  uploads/.htaccess
  deploy/nginx-upload-limits.conf
  server-check.sh
)
for f in "${REQUIRED[@]}"; do
  if [[ ! -f "$f" ]]; then
    fail "нет файла: $f"
  fi
done
[[ "$errors" -eq 0 ]] && ok "все обязательные файлы на месте"
echo ""

# ── require_once / include ────────────────────────────────────
echo "→ Зависимости PHP (require_once)..."
if command -v php >/dev/null 2>&1; then
  missing_deps=$(php -r '
    $root = getcwd() . "/api";
    $missing = [];
    foreach (glob($root . "/*.php") as $file) {
      $code = file_get_contents($file);
      if (preg_match_all("/require(?:_once)?\\s+(?:__DIR__\\s\\.\\s)?[\"'\'']([^\"'\'']+)[\"'\'']/", $code, $m)) {
        foreach ($m[1] as $rel) {
          $rel = preg_replace("#^\\./#", "", $rel);
          if (str_starts_with($rel, "__DIR__")) continue;
          $path = dirname($file) . "/" . $rel;
          if (!is_file($path)) $missing[] = basename($file) . " → $rel";
        }
      }
    }
    echo implode("\n", array_unique($missing));
  ')
  if [[ -n "$missing_deps" ]]; then
    while IFS= read -r line; do
      [[ -n "$line" ]] && fail "broken require: $line"
    done <<< "$missing_deps"
  else
    ok "все require_once указывают на существующие файлы"
  fi
fi
echo ""

# ── Auth / email verification ─────────────────────────────────
echo "→ Email verification..."
grep -q 'requires_verification' api/register.php \
  && ok 'register.php → requires_verification' \
  || fail 'register.php без requires_verification (старая версия)'

grep -q 'installRegisterVerificationPatch' js/api-base.js \
  && ok 'api-base.js → патч верификации' \
  || fail 'api-base.js без installRegisterVerificationPatch'

grep -q 'needsVerify' index.html \
  && ok 'index.html → needsVerify' \
  || fail 'index.html без needsVerify'

grep -q 'email_verified_at = NOW()' api/auth_email.php && \
  grep -q 'ALTER TABLE users ADD COLUMN email_verified_at' api/auth_email.php && \
  ok 'auth_email.php → миграция колонки без автовхода на каждый запрос' || true
echo ""

# ── index.html script src ─────────────────────────────────────
echo "→ Скрипты из index.html..."
js_missing=0
while IFS= read -r src; do
  [[ -z "$src" ]] && continue
  src="${src#./}"
  src="${src%%\?*}"
  if [[ ! -f "$src" ]]; then
    fail "index.html подключает отсутствующий файл: $src"
    js_missing=1
  fi
done < <(grep -oE 'src="[^"]+\.js[^"]*"' index.html | sed 's/src="//;s/"$//' | grep -v '^https://')
[[ "$js_missing" -eq 0 ]] && ok "все локальные .js из index.html найдены"
echo ""

# ── Realtime API ──────────────────────────────────────────────
echo "→ Realtime (presence / typing / sse)..."
for f in presence.php typing.php sse.php; do
  if grep -q "auth.php" "api/$f" && [[ -f api/auth.php ]]; then
    ok "api/$f → auth.php"
  else
    fail "api/$f требует auth.php"
  fi
done
echo ""

# ── Секреты не должны попасть в архив ─────────────────────────
echo "→ Безопасность деплоя..."
if [[ -f .env ]]; then
  warn ".env есть локально — НЕ заливайте на сервер поверх production .env"
else
  ok "локального .env нет (или не в каталоге)"
fi
if git check-ignore -q .env 2>/dev/null; then
  ok ".env в .gitignore"
fi
echo ""

# ── uploads ───────────────────────────────────────────────────
echo "→ uploads/"
if [[ -d uploads ]]; then
  ok "папка uploads/ существует"
else
  warn "создайте uploads/ на сервере: mkdir -p uploads && chmod 775 uploads"
fi
echo ""

# ── Итог ──────────────────────────────────────────────────────
echo "============================================"
if [[ "$errors" -gt 0 ]]; then
  echo -e "${RED}FAILED: $errors ошибок, $warnings предупреждений${NC}"
  echo "Исправьте ошибки перед заливкой на сервер."
  exit 1
fi

echo -e "${GREEN}OK: проект готов к заливке${NC}"
if [[ "$warnings" -gt 0 ]]; then
  echo "Предупреждений: $warnings"
fi
echo ""
echo "Дальше:"
echo "  ./pack-deploy.sh          — архив для сервера"
echo "  scp mendflow-deploy.tgz root@SERVER:/tmp/"
echo "  на сервере: cd /var/www/html/mendflow && tar xzf /tmp/mendflow-deploy.tgz && bash server-check.sh"
