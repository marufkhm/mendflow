#!/usr/bin/env bash
# Включить HTTPS (Let's Encrypt) для mendflow.us — убирает «Небезопасно» в браузере.
# Запуск НА СЕРВЕРЕ (root):
#   cd /var/www/html/mendflow && sudo bash deploy/setup-ssl.sh
#
# Перед запуском:
#   1. DNS: A-запись mendflow.us → IP этого сервера (и www при необходимости)
#   2. nginx слушает :80, сайт открывается по http://mendflow.us
#   3. Порты 80 и 443 открыты: ufw allow 'Nginx Full' && ufw status

set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
DOMAIN="${MF_DOMAIN:-mendflow.us}"
EMAIL="${MF_SSL_EMAIL:-admin@${DOMAIN}}"
WEBROOT="/var/www/html/mendflow"
INCLUDE_WWW="${MF_SSL_WWW:-1}"

echo "=== Mendflow: HTTPS (Let's Encrypt) ==="
echo "Домен:  $DOMAIN"
echo "Email:  $EMAIL (уведомления об истечении сертификата)"
echo ""

if [[ "$(id -u)" -ne 0 ]]; then
  echo "Запустите с sudo: sudo bash deploy/setup-ssl.sh"
  exit 1
fi

if ! command -v nginx >/dev/null 2>&1; then
  echo "nginx не найден. Установите: apt install nginx"
  exit 1
fi

SERVER_IP="$(curl -4sS --max-time 5 ifconfig.me 2>/dev/null || curl -4sS --max-time 5 icanhazip.com 2>/dev/null || true)"
RESOLVED_IP="$(getent ahostsv4 "$DOMAIN" 2>/dev/null | awk '{print $1; exit}' || true)"
if [[ -n "$SERVER_IP" && -n "$RESOLVED_IP" && "$SERVER_IP" != "$RESOLVED_IP" ]]; then
  echo "! DNS: $DOMAIN → $RESOLVED_IP, IP сервера → $SERVER_IP"
  echo "  Certbot может не выдать сертификат, пока DNS не укажет на этот сервер."
  read -r -p "Продолжить? [y/N] " ans
  [[ "${ans,,}" == "y" ]] || exit 1
fi

echo "==> certbot"
if ! command -v certbot >/dev/null 2>&1; then
  export DEBIAN_FRONTEND=noninteractive
  apt-get update -qq
  apt-get install -y certbot python3-certbot-nginx
fi

CERT_ARGS=(--nginx --non-interactive --agree-tos -m "$EMAIL" --redirect -d "$DOMAIN")
if [[ "$INCLUDE_WWW" == "1" ]]; then
  CERT_ARGS+=(-d "www.$DOMAIN")
fi

# Убедиться, что nginx знает про mendflow (certbot правит site-конфиг)
if ! grep -rqE "mendflow|${WEBROOT}" /etc/nginx/sites-enabled/ /etc/nginx/conf.d/ 2>/dev/null; then
  SITE="/etc/nginx/sites-available/mendflow"
  echo "==> создаём базовый site-конфиг: $SITE"
  cat > "$SITE" <<EOF
server {
    listen 80;
    listen [::]:80;
    server_name ${DOMAIN} www.${DOMAIN};

    root ${WEBROOT};
    index index.html index.php;

    client_max_body_size 64M;

    location / {
        try_files \$uri \$uri/ /index.html;
    }

    location ~ \\.php\$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;' 2>/dev/null || echo '8.5')-fpm.sock;
    }

    location ~ /\\. {
        deny all;
    }
}
EOF
  ln -sf "$SITE" /etc/nginx/sites-enabled/mendflow
  rm -f /etc/nginx/sites-enabled/default 2>/dev/null || true
  nginx -t
  systemctl reload nginx
fi

echo "==> получение сертификата (certbot --nginx)"
certbot "${CERT_ARGS[@]}"

echo "==> автообновление"
systemctl enable certbot.timer 2>/dev/null || true
systemctl start certbot.timer 2>/dev/null || true

echo "==> nginx test + reload"
nginx -t
systemctl reload nginx

echo ""
echo "==> Проверка"
curl -sSI "https://${DOMAIN}/api/ping.php" | head -5 || true

echo ""
if [[ -f "$WEBROOT/.env" ]]; then
  if grep -q '^APP_URL=http://' "$WEBROOT/.env" 2>/dev/null; then
    sed -i "s|^APP_URL=http://|APP_URL=https://|" "$WEBROOT/.env"
    echo "✓ APP_URL в .env обновлён на https://"
  elif ! grep -q '^APP_URL=https://' "$WEBROOT/.env" 2>/dev/null; then
    echo "! Добавьте в $WEBROOT/.env:"
    echo "  APP_URL=https://${DOMAIN}"
  else
    echo "✓ APP_URL уже https://"
  fi
else
  echo "! Нет .env — задайте APP_URL=https://${DOMAIN}"
fi

echo ""
echo "OK. Откройте https://${DOMAIN} — должна быть иконка замка."
echo "HTTP автоматически перенаправляется на HTTPS (certbot --redirect)."
