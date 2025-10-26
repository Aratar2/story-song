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

echo "[deploy] Preparing SQLite data directory..."
mkdir -p var/data

echo "[deploy] Installing composer dependencies..."
docker compose run --rm \
  --entrypoint composer \
  php install --no-dev --optimize-autoloader --no-interaction

echo "[deploy] Installing Symfony assets..."
docker compose run --rm php php bin/console assets:install --no-interaction --symlink --relative public

echo "[deploy] Building and starting application containers (php, cron, nginx, geoip)..."
docker compose up -d --build php cron nginx geoip

check_certificate() {
  docker compose run --rm --entrypoint /bin/sh certbot -c "test -f /etc/letsencrypt/live/${DOMAIN}/fullchain.pem"
}

ensure_ssl_support_files() {
  local options_file="nginx/ssl/options-ssl-nginx.conf"
  local dhparams_file="nginx/ssl/ssl-dhparams.pem"

  if [[ ! -f "$options_file" || ! -f "$dhparams_file" ]]; then
    echo "[deploy] TLS snippets missing. Ensure $options_file and $dhparams_file exist." >&2
    exit 1
  fi

  docker compose run --rm \
    --volume "$(pwd)/nginx/ssl:/tls:ro" \
    --entrypoint /bin/sh certbot -c "\
      set -eu\n\
      mkdir -p /etc/letsencrypt\n\
      [ -f /etc/letsencrypt/options-ssl-nginx.conf ] || cp /tls/options-ssl-nginx.conf /etc/letsencrypt/options-ssl-nginx.conf\n\
      [ -f /etc/letsencrypt/ssl-dhparams.pem ] || cp /tls/ssl-dhparams.pem /etc/letsencrypt/ssl-dhparams.pem\n\
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
