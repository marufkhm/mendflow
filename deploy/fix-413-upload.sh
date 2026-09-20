#!/usr/bin/env bash
# Fix "413 Request Entity Too Large" on mendflow.us (nginx + PHP-FPM).
# Run ON THE SERVER as root:
#   cd /var/www/html/mendflow && sudo bash deploy/fix-413-upload.sh

set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
NGINX_SNIPPET="/etc/nginx/conf.d/mendflow-upload-limits.conf"

echo "==> nginx: client_max_body_size 64M (conf.d)"
install -m 644 "$ROOT/deploy/nginx-upload-limits.conf" "$NGINX_SNIPPET"

# Также в site-конфиг (если conf.d не подхватился или лимит переопределён)
patch_site() {
  local f="$1"
  [[ -f "$f" ]] || return 0
  if grep -q 'client_max_body_size' "$f"; then
    sed -i 's/^[[:space:]]*client_max_body_size.*/    client_max_body_size 64M;/' "$f"
    echo "    updated client_max_body_size in $f"
  elif grep -q 'server_name' "$f"; then
    sed -i '/server_name/a\    client_max_body_size 64M;' "$f"
    echo "    added client_max_body_size to $f"
  fi
}

echo "==> nginx: site configs"
for f in /etc/nginx/sites-enabled/* /etc/nginx/conf.d/*.conf; do
  [[ -f "$f" ]] || continue
  if grep -qE 'mendflow|/var/www/html/mendflow' "$f" 2>/dev/null; then
    patch_site "$f"
  fi
done
patch_site /etc/nginx/sites-enabled/default 2>/dev/null || true

PHP_VER="$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;' 2>/dev/null || echo '8.5')"
for PHP_INI in "/etc/php/${PHP_VER}/fpm/php.ini" "/etc/php/${PHP_VER}/cli/php.ini"; do
  if [[ -f "$PHP_INI" ]]; then
    echo "==> PHP ($PHP_INI)"
    sed -i 's/^[;[:space:]]*upload_max_filesize.*/upload_max_filesize = 64M/' "$PHP_INI"
    sed -i 's/^[;[:space:]]*post_max_size.*/post_max_size = 68M/' "$PHP_INI"
  fi
done

# Pool override (часто надёжнее php.ini на Ubuntu)
POOL_DIR="/etc/php/${PHP_VER}/fpm/pool.d"
if [[ -d "$POOL_DIR" ]]; then
  OVERRIDE="${POOL_DIR}/99-mendflow-uploads.conf"
  cat > "$OVERRIDE" <<'EOF'
; Mendflow upload limits
php_admin_value[upload_max_filesize] = 64M
php_admin_value[post_max_size] = 68M
EOF
  echo "==> PHP-FPM pool: $OVERRIDE"
fi

systemctl reload "php${PHP_VER}-fpm" 2>/dev/null || systemctl reload php-fpm 2>/dev/null || true

echo "==> nginx test + reload"
nginx -t
systemctl reload nginx

echo ""
echo "==> Текущие лимиты:"
nginx -T 2>/dev/null | grep -m3 client_max_body_size || true
php -r 'echo "PHP upload_max_filesize=".ini_get("upload_max_filesize")." post_max_size=".ini_get("post_max_size")."\n";'
echo ""
echo "OK. nginx 64M, PHP 64M/68M. App: фото до 25M, видео до 50M."
