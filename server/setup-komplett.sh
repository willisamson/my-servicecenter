#!/usr/bin/env bash
# =====================================================================
#  my-servicecenter – komplette Server-Einrichtung in einem Durchgang
#  Aufruf:  bash /root/setup-komplett.sh deine@email.de
#  Das Skript fragt an einigen Stellen nach Eingaben und wartet dann.
# =====================================================================
set -euo pipefail

EMAIL="${1:-}"
if [ -z "$EMAIL" ]; then echo "Aufruf: bash /root/setup-komplett.sh deine@email.de"; exit 1; fi

REPO="${REPO:-willisamson/my-servicecenter}"
TEST_DOMAIN="test.my-servicecenter.com"
APPDIR=/var/www/servicecenter/test
ENVFILE="$APPDIR/.env"

exec > >(tee -a /root/setup.log) 2>&1
export DEBIAN_FRONTEND=noninteractive
step(){ echo; echo "=================== $* ==================="; }
frage(){ echo; echo ">>> $*"; }
SERVER_IP=$(hostname -I | awk '{print $1}')

# ---------------------------------------------------------------------
step "1/12 Eingaben vorab"
# ---------------------------------------------------------------------
frage "SSH-Schlüssel: Füge die Zeile aus Bitvise ein (beginnt mit ssh-ed25519) und drück Enter."
frage "Leer lassen = Passwort-Login bleibt vorerst die einzige Anmeldung."
read -r PUBKEY
frage "Neuer IONOS-API-Schlüssel für das Wildcard-Zertifikat."
read -rp "    Präfix: " IONOS_PREFIX
read -rsp "    Secret (wird nicht angezeigt): " IONOS_SECRET; echo
frage "E-Mail-Versand über ein IONOS-Postfach (z. B. noreply@my-servicecenter.com)."
read -rp "    Postfach-Adresse (Enter = überspringen, Mails landen dann nur im Protokoll): " MAIL_USER
MAIL_PASS=""
if [ -n "$MAIL_USER" ]; then read -rsp "    Postfach-Passwort (wird nicht angezeigt): " MAIL_PASS; echo; fi
frage "Dein Admin-Zugang für https://$TEST_DOMAIN/admin (Login-E-Mail: $EMAIL)."
read -rp "    Dein Name: " ADMIN_NAME
while true; do
  read -rsp "    Admin-Passwort (mind. 8 Zeichen, wird nicht angezeigt): " ADMIN_PASS; echo
  read -rsp "    Passwort wiederholen: " ADMIN_PASS2; echo
  if [ "${#ADMIN_PASS}" -ge 8 ] && [ "$ADMIN_PASS" = "$ADMIN_PASS2" ]; then break; fi
  echo "    Zu kurz oder nicht gleich – bitte nochmal."
done

# ---------------------------------------------------------------------
step "2/12 System aktualisieren und Pakete installieren"
# ---------------------------------------------------------------------
apt-get update
apt-get -y -o Dpkg::Options::=--force-confdef -o Dpkg::Options::=--force-confold upgrade
apt-get install -y nginx mariadb-server certbot python3-certbot-nginx ufw fail2ban unattended-upgrades \
  php-fpm php-cli php-mysql php-mbstring php-xml php-curl php-zip php-bcmath php-intl php-gd php-sqlite3 \
  composer git unzip curl openssl python3
if ! command -v node >/dev/null || [ "$(node -v | cut -d. -f1 | tr -d v)" -lt 22 ]; then
  curl -fsSL https://deb.nodesource.com/setup_24.x | bash -
  apt-get install -y nodejs
fi
PHPV=$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')
SOCK="/run/php/php$PHPV-fpm-servicecenter.sock"
echo "PHP $PHPV, Node $(node -v)"

# ---------------------------------------------------------------------
step "3/12 Swap, Firewall, fail2ban, automatische Updates"
# ---------------------------------------------------------------------
if [ -z "$(swapon --show)" ]; then
  fallocate -l 2G /swapfile && chmod 600 /swapfile && mkswap /swapfile && swapon /swapfile
  grep -q '^/swapfile' /etc/fstab || echo '/swapfile none swap sw 0 0' >> /etc/fstab
fi
ufw allow OpenSSH
ufw allow 'Nginx Full'
ufw --force enable
systemctl enable --now fail2ban
printf 'APT::Periodic::Update-Package-Lists "1";\nAPT::Periodic::Unattended-Upgrade "1";\n' > /etc/apt/apt.conf.d/20auto-upgrades

# ---------------------------------------------------------------------
step "4/12 SSH-Schlüssel hinterlegen (Passwort-Login bleibt vorerst an)"
# ---------------------------------------------------------------------
mkdir -p /root/.ssh && chmod 700 /root/.ssh
touch /root/.ssh/authorized_keys && chmod 600 /root/.ssh/authorized_keys
SSHMSG="Kein SSH-Schlüssel übergeben – Passwort-Login bleibt an."
if [ -n "$PUBKEY" ]; then
  case "$PUBKEY" in
    ssh-ed25519\ *|ssh-rsa\ *|ecdsa-sha2-*)
      grep -qxF "$PUBKEY" /root/.ssh/authorized_keys || echo "$PUBKEY" >> /root/.ssh/authorized_keys
      SSHMSG="SSH-Schlüssel hinterlegt. Erst Login per Schlüssel testen + Bitvise-Profil speichern, dann: bash /root/ssh-absichern.sh" ;;
    *) SSHMSG="WARNUNG: Der eingefügte Text ist kein SSH-Schlüssel und wurde NICHT hinterlegt." ;;
  esac
fi
cat > /root/ssh-absichern.sh <<'EOF'
#!/bin/bash
# Schaltet den Passwort-Login ab. ERST ausführen, wenn der Login per Schlüssel funktioniert
# UND das Bitvise-Profil gespeichert ist!
if [ ! -s /root/.ssh/authorized_keys ]; then echo "Kein Schlüssel hinterlegt – abgebrochen."; exit 1; fi
printf 'PasswordAuthentication no\nKbdInteractiveAuthentication no\nPermitRootLogin prohibit-password\n' > /etc/ssh/sshd_config.d/00-haertung.conf
mkdir -p /run/sshd
if sshd -t; then
  systemctl restart ssh
  sshd -T | grep -E "^(passwordauthentication|permitrootlogin)"
  echo "Fertig. Dieses Fenster offen lassen und eine NEUE Verbindung per Schlüssel testen."
  echo "Rückgängig: rm /etc/ssh/sshd_config.d/00-haertung.conf && systemctl restart ssh"
else
  rm -f /etc/ssh/sshd_config.d/00-haertung.conf
  echo "Fehler in der SSH-Konfiguration – nichts geändert."
fi
EOF
chmod 700 /root/ssh-absichern.sh
echo "$SSHMSG"

# ---------------------------------------------------------------------
step "5/12 MariaDB, Benutzer, PHP-Prozess"
# ---------------------------------------------------------------------
systemctl enable --now mariadb
mysql -e "DROP USER IF EXISTS ''@'localhost'; DROP USER IF EXISTS ''@'$(hostname)'; DROP DATABASE IF EXISTS test; FLUSH PRIVILEGES;"

id servicecenter >/dev/null 2>&1 || useradd -m -s /bin/bash servicecenter
mkdir -p /var/www/servicecenter && chown servicecenter:servicecenter /var/www/servicecenter && chmod 755 /var/www/servicecenter
id vibhub >/dev/null 2>&1 || useradd -m -s /bin/bash vibhub
mkdir -p /var/www/vibhub && chown vibhub:vibhub /var/www/vibhub && chmod 755 /var/www/vibhub

cat > "/etc/php/$PHPV/fpm/pool.d/servicecenter.conf" <<EOF
[servicecenter]
user = servicecenter
group = servicecenter
listen = $SOCK
listen.owner = www-data
listen.group = www-data
pm = dynamic
pm.max_children = 8
pm.start_servers = 2
pm.min_spare_servers = 1
pm.max_spare_servers = 3
EOF
systemctl restart "php$PHPV-fpm"

secret(){ local f=$1; [ -s "$f" ] || { openssl rand -hex 20 > "$f"; chmod 600 "$f"; }; cat "$f"; }
DBPASS=$(secret /root/.sc-dbpass)
mysql -e "CREATE USER IF NOT EXISTS 'servicecenter'@'localhost' IDENTIFIED BY '$DBPASS'; ALTER USER 'servicecenter'@'localhost' IDENTIFIED BY '$DBPASS';"
mysql <<'SQL'
CREATE DATABASE IF NOT EXISTS sc_central CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON `sc\_%`.* TO 'servicecenter'@'localhost';
FLUSH PRIVILEGES;
SQL

# ---------------------------------------------------------------------
step "6/12 GitHub: Lesezugriff für den Server"
# ---------------------------------------------------------------------
sudo -u servicecenter -H bash -c 'mkdir -p ~/.ssh && chmod 700 ~/.ssh && ssh-keyscan github.com >> ~/.ssh/known_hosts 2>/dev/null'
[ -f /home/servicecenter/.ssh/github ] || sudo -u servicecenter -H ssh-keygen -q -t ed25519 -N "" -f /home/servicecenter/.ssh/github -C "server-deploy"
sudo -u servicecenter -H bash -c 'printf "Host github.com\n  IdentityFile ~/.ssh/github\n  IdentitiesOnly yes\n" > ~/.ssh/config && chmod 600 ~/.ssh/config'

github_ok(){ local out; out=$(sudo -u servicecenter -H ssh -o StrictHostKeyChecking=accept-new -T git@github.com 2>&1 || true); [[ "$out" == *"successfully authenticated"* ]]; }
if ! github_ok; then
  frage "Neuer Server-Schlüssel für GitHub. Kopiere diese Zeile:"
  echo
  cat /home/servicecenter/.ssh/github.pub
  echo
  frage "Auf GitHub: Repository $REPO → Settings → Deploy keys."
  frage "Den alten Schlüssel 'Server' löschen, dann 'Add deploy key', Titel 'Server', Zeile einfügen, KEIN Schreibzugriff."
  until github_ok; do
    read -rp "    Wenn erledigt, Enter drücken … " _
  done
fi
echo "GitHub-Zugriff klappt."

# ---------------------------------------------------------------------
step "7/12 Projekt holen und einrichten"
# ---------------------------------------------------------------------
if [ ! -d "$APPDIR/.git" ]; then
  cd /var/www/servicecenter
  rm -rf "$APPDIR"
  sudo -u servicecenter -H git clone "git@github.com:$REPO.git" test
fi
cd "$APPDIR"
[ -f "$ENVFILE" ] || sudo -u servicecenter cp .env.example .env

setenv(){
  KEY="$1" VAL="$2" python3 - "$ENVFILE" <<'PY'
import os, re, sys
pfad = sys.argv[1]; key = os.environ['KEY']; val = os.environ['VAL']
if val and re.search(r'[\s#"\'$\\]', val):
    val = "'" + val + "'" if "'" not in val else '"' + val.replace('\\', '\\\\').replace('"', '\\"') + '"'
text = open(pfad, encoding='utf-8').read()
muster = re.compile(r'^#?\s?' + re.escape(key) + r'=.*$', re.M)
zeile = f'{key}={val}'
text = muster.sub(lambda m: zeile, text, count=1) if muster.search(text) else text.rstrip('\n') + '\n' + zeile + '\n'
open(pfad, 'w', encoding='utf-8').write(text)
PY
}
setenv APP_NAME my-servicecenter
setenv APP_ENV staging
setenv APP_DEBUG true
setenv APP_URL "https://$TEST_DOMAIN"
setenv APP_LOCALE de
setenv CENTRAL_DOMAINS "$TEST_DOMAIN"
setenv TENANCY_DB_PREFIX sc_tenant_
setenv DB_CONNECTION mysql
setenv DB_HOST 127.0.0.1
setenv DB_PORT 3306
setenv DB_DATABASE sc_central
setenv DB_USERNAME servicecenter
setenv DB_PASSWORD "$DBPASS"
if [ -n "$MAIL_USER" ]; then
  setenv MAIL_MAILER smtp
  setenv MAIL_SCHEME smtps
  setenv MAIL_HOST smtp.ionos.de
  setenv MAIL_PORT 465
  setenv MAIL_USERNAME "$MAIL_USER"
  setenv MAIL_PASSWORD "$MAIL_PASS"
  setenv MAIL_FROM_ADDRESS "$MAIL_USER"
  setenv MAIL_FROM_NAME my-servicecenter
fi
chown servicecenter:servicecenter "$ENVFILE" && chmod 640 "$ENVFILE"

cat > /usr/local/bin/deploy-servicecenter <<EOF
#!/bin/bash
set -e
cd $APPDIR
echo "== Code holen ==";            sudo -u servicecenter -H git pull --ff-only
echo "== PHP-Pakete ==";            sudo -u servicecenter -H composer install --no-dev --optimize-autoloader --no-interaction
echo "== Frontend bauen ==";        if [ -f package-lock.json ]; then sudo -u servicecenter -H npm ci; else sudo -u servicecenter -H npm install; fi
                                    sudo -u servicecenter -H npm run build
echo "== Datenbank ==";             sudo -u servicecenter php artisan migrate --force
echo "== Betriebs-Datenbanken ==";  sudo -u servicecenter php artisan tenants:migrate --force
echo "== Cache ==";                 sudo -u servicecenter php artisan optimize
systemctl reload php$PHPV-fpm
echo "== Deploy fertig: https://$TEST_DOMAIN =="
EOF
chmod 755 /usr/local/bin/deploy-servicecenter

grep -q '^APP_KEY=base64' "$ENVFILE" || { sudo -u servicecenter -H composer install --no-dev --no-interaction; sudo -u servicecenter php artisan key:generate --force; }
/usr/local/bin/deploy-servicecenter
echo "* * * * * servicecenter cd $APPDIR && php artisan schedule:run >> /dev/null 2>&1" > /etc/cron.d/servicecenter-scheduler

# ---------------------------------------------------------------------
step "8/12 Nginx: Grundeinstellungen und Platzhalter-Seiten"
# ---------------------------------------------------------------------
cd /root
rm -f /etc/nginx/sites-enabled/default /etc/nginx/conf.d/haertung.conf
if grep -qE '^\s*server_tokens\s' /etc/nginx/nginx.conf; then
  sed -i -E 's/^(\s*)server_tokens\s+\w+;/\1server_tokens off;/' /etc/nginx/nginx.conf
else
  echo "server_tokens off;" > /etc/nginx/conf.d/haertung.conf
fi
printf 'server {\n    listen 80 default_server;\n    listen [::]:80 default_server;\n    server_name _;\n    return 444;\n}\n' > /etc/nginx/sites-available/000-unbekannt
ln -sf /etc/nginx/sites-available/000-unbekannt /etc/nginx/sites-enabled/000-unbekannt

platzhalter(){ local names=$1 dir=$2 owner=$3 titel=$4 text=$5 file=${1%% *}
  mkdir -p "$dir"
  cat > "$dir/index.html" <<EOF
<!doctype html><html lang="de"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>$titel</title></head>
<body style="margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;background:#F5F4F0;font-family:system-ui,sans-serif;color:#12161F">
<div style="text-align:center;padding:24px"><h1 style="font-size:40px;margin:0 0 12px">$titel</h1><p style="font-size:18px;color:#4B5563;margin:0">$text</p></div>
</body></html>
EOF
  chown -R "$owner:$owner" "$dir"
  cat > "/etc/nginx/sites-available/$file" <<EOF
server {
    listen 80;
    listen [::]:80;
    server_name $names;
    root $dir;
    index index.html;
    location / { try_files \$uri \$uri/ =404; }
}
EOF
  ln -sf "/etc/nginx/sites-available/$file" "/etc/nginx/sites-enabled/$file"
}
platzhalter "my-servicecenter.com www.my-servicecenter.com" /var/www/servicecenter/platzhalter servicecenter "my-servicecenter" "Bald verfügbar."
platzhalter "vib-hub.com www.vib-hub.com" /var/www/vibhub/platzhalter vibhub "vib-hub" "Neue Website in Arbeit."
nginx -t && systemctl reload nginx

cert(){ local ds=() d
  for d in "$@"; do
    if getent ahostsv4 "$d" | awk '{print $1}' | grep -qx "$SERVER_IP"; then ds+=(-d "$d")
    else echo "Übersprungen (DNS zeigt nicht auf $SERVER_IP): $d"; fi
  done
  [ ${#ds[@]} -eq 0 ] && return 0
  certbot --nginx --non-interactive --agree-tos -m "$EMAIL" --redirect --keep-until-expiring "${ds[@]}" \
    || echo "WARNUNG: Zertifikat fehlgeschlagen für: $*"
}
cert my-servicecenter.com www.my-servicecenter.com
cert vib-hub.com www.vib-hub.com

# ---------------------------------------------------------------------
step "9/12 Wildcard-Zertifikat für $TEST_DOMAIN und alle Betriebe"
# ---------------------------------------------------------------------
[ -x /root/.acme.sh/acme.sh ] || curl -fsSL https://get.acme.sh | sh -s email="$EMAIL"
/root/.acme.sh/acme.sh --set-default-ca --server letsencrypt
IONOS_PREFIX="$IONOS_PREFIX" IONOS_SECRET="$IONOS_SECRET" \
  /root/.acme.sh/acme.sh --issue --dns dns_ionos -d "$TEST_DOMAIN" -d "*.$TEST_DOMAIN" || true
mkdir -p /etc/nginx/ssl
/root/.acme.sh/acme.sh --install-cert -d "$TEST_DOMAIN" --ecc \
  --key-file "/etc/nginx/ssl/$TEST_DOMAIN.key" \
  --fullchain-file "/etc/nginx/ssl/$TEST_DOMAIN.crt" \
  --reloadcmd "systemctl reload nginx" || true
if [ ! -s "/etc/nginx/ssl/$TEST_DOMAIN.crt" ]; then
  echo "FEHLER: Kein Wildcard-Zertifikat. Bitte Präfix/Secret und den DNS-Eintrag *.test prüfen, dann Skript erneut starten."
  exit 1
fi

# ---------------------------------------------------------------------
step "10/12 Nginx: Test-Umgebung mit allen Betriebs-Subdomains"
# ---------------------------------------------------------------------
TESTPASS=$(secret /root/.sc-testpass | cut -c1-16)
printf "test:%s\n" "$(openssl passwd -apr1 "$TESTPASS")" > /etc/nginx/.htpasswd-servicecenter
chown root:www-data /etc/nginx/.htpasswd-servicecenter && chmod 640 /etc/nginx/.htpasswd-servicecenter

cat > "/etc/nginx/sites-available/$TEST_DOMAIN" <<EOF
server {
    listen 80;
    listen [::]:80;
    server_name $TEST_DOMAIN *.$TEST_DOMAIN;
    return 301 https://\$host\$request_uri;
}

server {
    listen 443 ssl;
    listen [::]:443 ssl;
    server_name $TEST_DOMAIN *.$TEST_DOMAIN;

    ssl_certificate     /etc/nginx/ssl/$TEST_DOMAIN.crt;
    ssl_certificate_key /etc/nginx/ssl/$TEST_DOMAIN.key;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_session_cache shared:SSL:10m;

    root $APPDIR/public;
    index index.php;
    charset utf-8;
    client_max_body_size 20M;

    auth_basic "my-servicecenter Test";
    auth_basic_user_file /etc/nginx/.htpasswd-servicecenter;

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
ln -sf "/etc/nginx/sites-available/$TEST_DOMAIN" "/etc/nginx/sites-enabled/$TEST_DOMAIN"
nginx -t && systemctl reload nginx

# ---------------------------------------------------------------------
step "11/12 Tägliche Datenbank-Sicherung"
# ---------------------------------------------------------------------
cat > /usr/local/bin/db-backup <<'EOF'
#!/bin/bash
set -e
DIR=/var/backups/mysql
mkdir -p "$DIR"
for db in $(mysql -N -e "SHOW DATABASES" | grep -Ev '^(information_schema|performance_schema|mysql|sys)$'); do
  mysqldump --single-transaction --routines --events "$db" | gzip > "$DIR/$db-$(date +%F).sql.gz"
done
find "$DIR" -name '*.sql.gz' -mtime +14 -delete
EOF
chmod 700 /usr/local/bin/db-backup
echo "30 3 * * * root /usr/local/bin/db-backup" > /etc/cron.d/db-backup

# ---------------------------------------------------------------------
step "12/12 Admin-Zugang und Vorführ-Betrieb"
# ---------------------------------------------------------------------
cd "$APPDIR"
if [ "$(mysql -N -e "SELECT COUNT(*) FROM sc_central.users WHERE email='$EMAIL'")" = "0" ]; then
  sudo -u servicecenter php artisan make:filament-user --panel=admin --name="$ADMIN_NAME" --email="$EMAIL" --password="$ADMIN_PASS"
fi
if [ "$(mysql -N -e "SELECT COUNT(*) FROM sc_central.tenants WHERE id='demo'")" = "0" ]; then
  sudo -u servicecenter php artisan betrieb:demo "$EMAIL" | tee /root/demo-zugang.txt
  chmod 600 /root/demo-zugang.txt
fi
cd /root
/usr/local/bin/db-backup

cat > /root/zugangsdaten.txt <<EOF
my-servicecenter Datenbank
  Benutzer: servicecenter
  Passwort: $DBPASS
  Datenbank: sc_central (Betriebs-Datenbanken beginnen mit sc_tenant_)

Passwortschutz der Test-Umgebung (https://$TEST_DOMAIN)
  Benutzer: test
  Passwort: $TESTPASS

Vorführ-Betrieb: siehe /root/demo-zugang.txt
EOF
chmod 600 /root/zugangsdaten.txt

echo
echo "=================== FERTIG ==================="
echo "$SSHMSG"
for d in my-servicecenter.com vib-hub.com "$TEST_DOMAIN" "demo.$TEST_DOMAIN"; do
  printf "%-36s %s\n" "$d:" "$(curl -s -o /dev/null -w '%{http_code}' --max-time 10 --resolve "$d:443:$SERVER_IP" "https://$d" || true)"
done
echo
echo "Erwartet: 200 / 200 / 401 / 401 (401 = Passwortschutz der Test-Umgebung, richtig so)."
echo "Zugangsdaten anzeigen:  cat /root/zugangsdaten.txt"
echo "Protokoll:              /root/setup.log"
