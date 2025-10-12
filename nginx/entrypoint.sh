#!/bin/sh
set -eu

apk add --no-cache inotify-tools >/dev/null

if [ -z "${DOMAIN:-}" ]; then
  echo "[nginx] DOMAIN environment variable is required" >&2
  exit 1
fi

TEMPLATE_DIR="/etc/nginx/templates"
CONF_PATH="/etc/nginx/conf.d/default.conf"
CERT_ROOT="/etc/letsencrypt"
CERT_BASE="${CERT_ROOT}/live"
CERT_DIR="${CERT_BASE}/${DOMAIN}"
SNIPPET_SOURCE_DIR="/etc/nginx/ssl"

mkdir -p "$CERT_BASE"

ensure_tls_snippets() {
  mkdir -p "$CERT_ROOT"

  if [ -f "${SNIPPET_SOURCE_DIR}/options-ssl-nginx.conf" ] && [ ! -f "${CERT_ROOT}/options-ssl-nginx.conf" ]; then
    install -m 0644 "${SNIPPET_SOURCE_DIR}/options-ssl-nginx.conf" "${CERT_ROOT}/options-ssl-nginx.conf"
  fi

  if [ -f "${SNIPPET_SOURCE_DIR}/ssl-dhparams.pem" ] && [ ! -f "${CERT_ROOT}/ssl-dhparams.pem" ]; then
    install -m 0644 "${SNIPPET_SOURCE_DIR}/ssl-dhparams.pem" "${CERT_ROOT}/ssl-dhparams.pem"
  fi
}

render_config() {
  if [ -f "${CERT_DIR}/fullchain.pem" ] && [ -f "${CERT_DIR}/privkey.pem" ]; then
    TEMPLATE="${TEMPLATE_DIR}/default.conf.ssl.template"
  else
    TEMPLATE="${TEMPLATE_DIR}/default.conf.http.template"
  fi

  envsubst '$DOMAIN' < "$TEMPLATE" > "$CONF_PATH"
}

await_certificate_directory() {
  while [ ! -d "$CERT_DIR" ]; do
    inotifywait -q -e create "$CERT_BASE" >/dev/null 2>&1 || sleep 5
    if [ -d "$CERT_DIR" ]; then
      break
    fi
  done
}

watch_certificates() {
  while true; do
    if [ -d "$CERT_DIR" ]; then
      inotifywait -q \
        -e create \
        -e delete \
        -e move \
        -e close_write \
        -e attrib \
        "$CERT_DIR" >/dev/null 2>&1 || true
      if [ -f "${CERT_DIR}/fullchain.pem" ]; then
        echo "[nginx] Detected certificate update for ${DOMAIN}. Reloading nginx..."
      else
        echo "[nginx] Certificate files for ${DOMAIN} missing after update event. Falling back to HTTP..."
      fi
      render_config
      nginx -s reload
    else
      await_certificate_directory
      if [ -f "${CERT_DIR}/fullchain.pem" ]; then
        echo "[nginx] Certificate for ${DOMAIN} became available. Enabling TLS and reloading nginx..."
      else
        echo "[nginx] Waiting for full certificate set for ${DOMAIN}..."
      fi
      render_config
      nginx -s reload
    fi
  done
}

ensure_tls_snippets

render_config

watch_certificates &

exec nginx -g "daemon off;"
