#!/usr/bin/env bash
# Fix "413 Request Entity Too Large" on mendflow.us (nginx + PHP-FPM).
# Run ON THE SERVER as root or with sudo:
#   cd /var/www/html/mendflow && sudo bash deploy/fix-413-upload.sh

set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
NGINX_SNIPPET="/etc/nginx/conf.d/mendflow-upload-limits.conf"

echo "==> nginx: client_max_body_size 64M"
install -m 644 "$ROOT/deploy/nginx-upload-limits.conf" "$NGINX_SNIPPET"

PHP_VER="$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;' 2>/dev/null || echo '8.5')"
PHP_INI="/etc/php/${PHP_VER}/fpm/php.ini"

if [[ -f "$PHP_INI" ]]; then
  echo "==> PHP-FPM ($PHP_INI): upload_max_filesize / post_max_size"
  sed -i 's/^[;[:space:]]*upload_max_filesize.*/upload_max_filesize = 64M/' "$PHP_INI"
  sed -i 's/^[;[:space:]]*post_max_size.*/post_max_size = 68M/' "$PHP_INI"
  systemctl reload "php${PHP_VER}-fpm" || systemctl reload php-fpm
else
  echo "WARN: $PHP_INI not found — set upload_max_filesize=64M and post_max_size=68M manually."
fi

echo "==> nginx test + reload"
nginx -t
systemctl reload nginx

echo "OK. Limits: nginx 64M, PHP upload 64M / post 68M (app max: photo 25M, video 50M)."
