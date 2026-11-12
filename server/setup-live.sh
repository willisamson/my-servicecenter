#!/usr/bin/env bash
# =====================================================================
#  my-servicecenter – Live-Umgebung auf my-servicecenter.com einrichten
#  Voraussetzung: setup-komplett.sh ist gelaufen (Test-Umgebung steht).
#  Aufruf:  bash /var/www/servicecenter/test/server/setup-live.sh deine@email.de
# =====================================================================
set -euo pipefail

EMAIL="${1:-}"
if [ -z "$EMAIL" ]; then echo "Aufruf: bash setup-live.sh deine@email.de"; exit 1; fi

REPO="${REPO:-willisamson/my-servicecenter}"
DOMAIN="my-servicecenter.com"
APPDIR=/var/www/servicecenter/live
ENVFILE="$APPDIR/.env"
TESTENV=/var/www/servicecenter/test/.env

exec > >(tee -a /root/setup-live.log) 2>&1
step(){ echo; echo "=================== $* ==================="; }
frage(){ echo; echo ">>> $*"; }
SERVER_IP=$(hostname -I | awk '{print $1}')
PHPV=$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')
SOCK="/run/php/php$PHPV-fpm-servicecenter.sock"

[ -f "$TESTENV" ] || { echo "Test-Umgebung nicht gefunden – zuerst setup-komplett.sh ausführen."; exit 1; }
[ -x /root/.acme.sh/acme.sh ] || { echo "acme.sh fehlt – zuerst setup-komplett.sh ausführen."; exit 1; }

# ---------------------------------------------------------------------
step "1/8 Eingaben und DNS-Prüfung"
# ---------------------------------------------------------------------
frage "Dein Admin-Zugang für https://$DOMAIN/admin (Login-E-Mail: $EMAIL)."
read -rp "    Dein Name: " ADMIN_NAME
while true; do
  read -rsp "    Admin-Passwort für LIVE (mind. 8 Zeichen, wird nicht angezeigt): " ADMIN_PASS; echo
  read -rsp "    Passwort wiederholen: " ADMIN_PASS2; echo
  if [ "${#ADMIN_PASS}" -ge 8 ] && [ "$ADMIN_PASS" = "$ADMIN_PASS2" ]; then break; fi
  echo "    Zu kurz oder nicht gleich – bitte nochmal."
done

PROBE="pruefung-$RANDOM.$DOMAIN"
until getent ahostsv4 "$PROBE" | awk '{print $1}' | grep -qx "$SERVER_IP"; do
  frage "Der DNS-Eintrag für alle Betriebe fehlt noch (oder ist noch nicht aktiv)."
  frage "IONOS → Domains → $DOMAIN → DNS: Typ A, Hostname *, Ziel $SERVER_IP anlegen."
  read -rp "    Wenn angelegt, ein paar Minuten warten und Enter drücken … " _
  PROBE="pruefung-$RANDOM.$DOMAIN"
done
echo "DNS für *.$DOMAIN zeigt auf diesen Server."

# ---------------------------------------------------------------------
step "2/8 Datenbank"
# ---------------------------------------------------------------------
mysql -e "CREATE DATABASE IF NOT EXISTS sc_live_central CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# ---------------------------------------------------------------------
step "3/8 Projekt holen"
# ---------------------------------------------------------------------
if [ ! -d "$APPDIR/.git" ]; then
  cd /var/www/servicecenter
  rm -rf "$APPDIR"
  sudo -u servicecenter -H git clone "git@github.com:$REPO.git" live
fi
cd "$APPDIR"
[ -f "$ENVFILE" ] || sudo -u servicecenter cp .env.example .env

envwert(){ grep -E "^$1=" "$TESTENV" | head -1 | cut -d= -f2- || true; }
setenv(){
  KEY="$1" VAL="$2" python3 - "$ENVFILE" <<'PY'
import os, re, sys
pfad = sys.argv[1]; key = os.environ['KEY']; val = os.environ['VAL']
roh = val
if val and not (val[:1] in "'\"" and val[-1:] == val[:1]) and re.search(r'[\s#"\'$\\]', val):
    val = "'" + val + "'" if "'" not in val else '"' + val.replace('\\', '\\\\').replace('"', '\\"') + '"'
text = open(pfad, encoding='utf-8').read()
muster = re.compile(r'^#?\s?' + re.escape(key) + r'=.*$', re.M)
zeile = f'{key}={val}'
text = muster.sub(lambda m: zeile, text, count=1) if muster.search(text) else text.rstrip('\n') + '\n' + zeile + '\n'
open(pfad, 'w', encoding='utf-8').write(text)
PY
}
setenv APP_NAME my-servicecenter
setenv APP_ENV production
setenv APP_DEBUG false
setenv APP_URL "https://$DOMAIN"
setenv APP_LOCALE de
setenv LOG_LEVEL warning
setenv CENTRAL_DOMAINS "$DOMAIN"
setenv TENANCY_DB_PREFIX sc_live_tenant_
setenv SESSION_SECURE_COOKIE true
setenv DB_CONNECTION mysql
setenv DB_HOST 127.0.0.1
setenv DB_PORT 3306
setenv DB_DATABASE sc_live_central
setenv DB_USERNAME servicecenter
setenv DB_PASSWORD "$(envwert DB_PASSWORD)"
for k in MAIL_MAILER MAIL_SCHEME MAIL_HOST MAIL_PORT MAIL_USERNAME MAIL_PASSWORD MAIL_FROM_ADDRESS MAIL_FROM_NAME; do
  setenv "$k" "$(envwert $k)"
done
chown servicecenter:servicecenter "$ENVFILE" && chmod 640 "$ENVFILE"

# ---------------------------------------------------------------------
step "4/8 Deploy-Befehl für Live"
# ---------------------------------------------------------------------
cat > /usr/local/bin/deploy-live <<EOF
#!/bin/bash
set -e
echo "Deploy auf LIVE ($DOMAIN). Vorher auf der Test-Umgebung geprüft? (Strg+C zum Abbrechen, Enter zum Fortfahren)"
read -r _
cd $APPDIR
echo "== Code holen ==";            sudo -u servicecenter -H git pull --ff-only
echo "== PHP-Pakete ==";            sudo -u servicecenter -H composer install --no-dev --optimize-autoloader --no-interaction
echo "== Frontend bauen ==";        if [ -f package-lock.json ]; then sudo -u servicecenter -H npm ci; else sudo -u servicecenter -H npm install; fi
                                    sudo -u servicecenter -H npm run build
echo "== Datenbank ==";             sudo -u servicecenter php artisan migrate --force
echo "== Betriebs-Datenbanken ==";  sudo -u servicecenter php artisan tenants:migrate --force
echo "== Speicher-Link ==";        sudo -u servicecenter php artisan storage:link >/dev/null 2>&1 || true
echo "== Cache ==";                 sudo -u servicecenter php artisan optimize
systemctl reload php$PHPV-fpm
echo "== Live-Deploy fertig: https://$DOMAIN =="
EOF
chmod 755 /usr/local/bin/deploy-live

grep -q '^APP_KEY=base64' "$ENVFILE" || { sudo -u servicecenter -H composer install --no-dev --no-interaction; sudo -u servicecenter php artisan key:generate --force; }
echo | /usr/local/bin/deploy-live
echo "* * * * * servicecenter cd $APPDIR && php artisan schedule:run >> /dev/null 2>&1" > /etc/cron.d/servicecenter-live-scheduler

# ---------------------------------------------------------------------
step "5/8 Wildcard-Zertifikat für $DOMAIN"
# ---------------------------------------------------------------------
cd /root
/root/.acme.sh/acme.sh --issue --dns dns_ionos -d "$DOMAIN" -d "*.$DOMAIN" || true
mkdir -p /etc/nginx/ssl
/root/.acme.sh/acme.sh --install-cert -d "$DOMAIN" --ecc \
  --key-file "/etc/nginx/ssl/$DOMAIN.key" \
  --fullchain-file "/etc/nginx/ssl/$DOMAIN.crt" \
  --reloadcmd "systemctl reload nginx" || true
if [ ! -s "/etc/nginx/ssl/$DOMAIN.crt" ]; then
  echo "FEHLER: Kein Wildcard-Zertifikat für $DOMAIN. Ist der IONOS-API-Schlüssel noch gültig? Protokoll: /root/setup-live.log"
  exit 1
fi

# ---------------------------------------------------------------------
step "6/8 Nginx: my-servicecenter.com, www und alle Betriebe"
# ---------------------------------------------------------------------
cat > "/etc/nginx/sites-available/$DOMAIN" <<EOF
server {
    listen 80;
    listen [::]:80;
    server_name $DOMAIN www.$DOMAIN *.$DOMAIN;
    return 301 https://\$host\$request_uri;
}

server {
    listen 443 ssl;
    listen [::]:443 ssl;
    server_name www.$DOMAIN;
    ssl_certificate     /etc/nginx/ssl/$DOMAIN.crt;
    ssl_certificate_key /etc/nginx/ssl/$DOMAIN.key;
    ssl_protocols TLSv1.2 TLSv1.3;
    return 301 https://$DOMAIN\$request_uri;
}

server {
    listen 443 ssl;
    listen [::]:443 ssl;
    server_name $DOMAIN *.$DOMAIN;

    ssl_certificate     /etc/nginx/ssl/$DOMAIN.crt;
    ssl_certificate_key /etc/nginx/ssl/$DOMAIN.key;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_session_cache shared:SSL_live:10m;

    add_header Strict-Transport-Security "max-age=31536000" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;

    root $APPDIR/public;
    index index.php;
    charset utf-8;
    client_max_body_size 20M;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }
    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }
    error_page 404 /index.php;

    location ~ ^/index\.php(/|\$) {
        fastcgi_pass unix:$SOCK;
        fastcgi_split_path_info ^(.+\.php)(/.*)\$;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT \$realpath_root;
        fastcgi_hide_header X-Powered-By;
    }
    location ~ /\.(?!well-known).* { deny all; }
}
EOF
ln -sf "/etc/nginx/sites-available/$DOMAIN" "/etc/nginx/sites-enabled/$DOMAIN"
nginx -t && systemctl reload nginx
certbot delete --cert-name "$DOMAIN" --non-interactive 2>/dev/null || true

# ---------------------------------------------------------------------
step "7/8 Admin-Zugang und Vorführ-Betrieb auf Live"
# ---------------------------------------------------------------------
cd "$APPDIR"
if [ "$(mysql -N -e "SELECT COUNT(*) FROM sc_live_central.users WHERE email='$EMAIL'")" = "0" ]; then
  sudo -u servicecenter php artisan make:filament-user --panel=admin --name="$ADMIN_NAME" --email="$EMAIL" --password="$ADMIN_PASS"
fi
if [ "$(mysql -N -e "SELECT COUNT(*) FROM sc_live_central.tenants WHERE id='demo'")" = "0" ]; then
  sudo -u servicecenter php artisan betrieb:demo "$EMAIL" | tee /root/demo-zugang-live.txt
  chmod 600 /root/demo-zugang-live.txt
fi
cd /root

# ---------------------------------------------------------------------
step "8/8 Prüfung"
# ---------------------------------------------------------------------
echo
echo "=================== FERTIG ==================="
for d in "$DOMAIN" "www.$DOMAIN" "demo.$DOMAIN" "$DOMAIN/admin/login" "test.$DOMAIN"; do
  host=${d%%/*}
  printf "%-38s %s\n" "$d:" "$(curl -s -o /dev/null -w '%{http_code}' --max-time 10 --resolve "$host:443:$SERVER_IP" "https://$d" || true)"
done
echo
echo "Erwartet: 200 / 301 / 200 / 200 / 401"
echo "Neuen Stand auf Live bringen:  deploy-live   (vorher immer erst deploy-servicecenter + auf Test prüfen)"
echo "Demo-Zugänge Live:             cat /root/demo-zugang-live.txt"
