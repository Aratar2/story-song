#!/usr/bin/env bash
set -euo pipefail

if [[ ! -f .env ]]; then
  echo "[deploy] Missing .env file. Copy .env.example and update DOMAIN/EMAIL first." >&2
  exit 1
fi

set -a
# shellcheck disable=SC1091
source .env
set +a

if [[ -z "${DOMAIN:-}" || -z "${EMAIL:-}" ]]; then
  echo "[deploy] DOMAIN and EMAIL must be set in the .env file." >&2
  exit 1
fi

echo "[deploy] Building and starting php + nginx containers..."
docker compose up -d --build php nginx

check_certificate() {
  docker compose run --rm --entrypoint /bin/sh certbot -c "test -f /etc/letsencrypt/live/${DOMAIN}/fullchain.pem"
}

ensure_ssl_support_files() {
  docker compose run --rm certbot /bin/sh -c "\
    set -eu\n\
    OPTIONS_SRC=\$(python -c \"import os, certbot_nginx._internal as m; print(os.path.join(os.path.dirname(m.__file__), 'options-ssl-nginx.conf'))\")\n\
    DHPARAM_SRC=\$(python -c \"import os, certbot._internal as m; print(os.path.join(os.path.dirname(m.__file__), 'assets', 'ssl-dhparams.pem'))\")\n\
    [ -f /etc/letsencrypt/options-ssl-nginx.conf ] || cp \"\$OPTIONS_SRC\" /etc/letsencrypt/options-ssl-nginx.conf\n\
    [ -f /etc/letsencrypt/ssl-dhparams.pem ] || cp \"\$DHPARAM_SRC\" /etc/letsencrypt/ssl-dhparams.pem\n\
  "
}

if check_certificate; then
  echo "[deploy] Existing certificate found for ${DOMAIN}. Skipping issuance."
else
  echo "[deploy] Requesting Let's Encrypt certificate for ${DOMAIN}..."
  docker compose run --rm certbot certonly \
    --webroot -w /var/www/certbot \
    --domain "${DOMAIN}" \
    --email "${EMAIL}" \
    --agree-tos \
    --no-eff-email \
    --non-interactive
fi

echo "[deploy] Ensuring TLS configuration snippets are available..."
ensure_ssl_support_files

echo "[deploy] Updating nginx configuration and reloading..."
docker compose exec nginx /bin/sh -c "envsubst '\$DOMAIN' < /etc/nginx/templates/default.conf.ssl.template > /etc/nginx/conf.d/default.conf && nginx -s reload"

echo "[deploy] Starting certbot renewal service..."
docker compose up -d certbot-renew >/dev/null

echo "[deploy] Deployment complete."
