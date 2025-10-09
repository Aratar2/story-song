#!/bin/sh
set -eu

CERT_DIR="/etc/nginx/certs"
CERT_FILE="$CERT_DIR/selfsigned.crt"
KEY_FILE="$CERT_DIR/selfsigned.key"
COMMON_NAME=${SSL_CERT_COMMON_NAME:-localhost}

mkdir -p "$CERT_DIR"

if [ ! -f "$CERT_FILE" ] || [ ! -f "$KEY_FILE" ]; then
  echo "Generating self-signed certificate for $COMMON_NAME"
  openssl req -x509 -nodes -days 365 \
    -subj "/CN=$COMMON_NAME" \
    -addext "subjectAltName=DNS:$COMMON_NAME,IP:127.0.0.1" \
    -newkey rsa:2048 \
    -keyout "$KEY_FILE" \
    -out "$CERT_FILE"
fi

exec nginx -g 'daemon off;'
