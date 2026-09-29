#!/usr/bin/env bash
# Public project URLs: /p/{slug} → p.php (SEO + SPA).
# Do NOT append deploy/nginx-spa-routes.conf outside server {}.
#
# Usage on server:
#   cd /var/www/html/mendflow && sudo bash deploy/fix-spa-routes.sh
set -euo pipefail

SITE="${NGINX_SITE:-/etc/nginx/sites-available/mendflow}"

if [[ ! -f "$SITE" ]]; then
  echo "Site config not found: $SITE"
  exit 1
fi

echo "==> backup $SITE"
cp "$SITE" "${SITE}.bak.$(date +%Y%m%d-%H%M%S)"

echo "==> normalize /p/ routes (p.php for SEO)"
python3 <<'PY'
from pathlib import Path
import re

site = Path("/etc/nginx/sites-available/mendflow")
text = site.read_text(encoding="utf-8", errors="replace")

# Remove wrongly appended orphan blocks
orphan = re.compile(
    r"\n# Mendflow SPA — public project links.*?"
    r"location \^~ /p/ \{[^}]+\}\n?",
    re.S,
)
text, n = orphan.subn("\n", text)
if n:
    print(f"removed {n} orphan /p/ block(s)")

orphan2 = re.compile(r"\nlocation \^~ /p/ \{[^}]+\}\n?", re.S)
text, n2 = orphan2.subn("\n", text)
if n2:
    print(f"removed {n2} legacy try_files /p/ block(s)")

# Remove old regex rewrite if re-running
old_rewrite = re.compile(
    r"\n\s*# Public project pages.*?\n\s*location ~ \^/p/\(\[a-zA-Z0-9_-\]\+\)/\?\$ \{[^}]+\}\n?",
    re.S,
)
text, n3 = old_rewrite.subn("\n", text)
if n3:
    print(f"removed {n3} old p.php rewrite block(s)")

snippet = """
    # Public project pages (SEO meta + SPA via p.php)
    location ~ ^/p/([a-zA-Z0-9_-]+)/?$ {
        rewrite ^ /p.php?slug=$1 last;
    }
"""

if "rewrite ^ /p.php?slug=" in text:
    print("already has p.php rewrite for /p/")
else:
    m = re.search(r"\n(\s*)location ~ \\\.php", text)
    if m:
        insert_at = m.start()
        text = text[:insert_at] + "\n" + snippet + text[insert_at:]
    else:
        idx = text.rfind("\n}")
        if idx == -1:
            raise SystemExit("cannot find server {} closing brace")
        text = text[:idx] + "\n" + snippet + text[idx:]
    print("inserted p.php rewrite for /p/")

site.write_text(text, encoding="utf-8")
PY

echo "==> nginx -t"
nginx -t

echo "==> reload nginx"
systemctl reload nginx

echo ""
echo "OK. /p/{slug} → p.php (SEO + SPA)."
echo "Sitemap: https://mendflow.us/sitemap.php"
echo "Robots:  https://mendflow.us/robots.txt"
