#!/bin/sh
set -eu

CRON_SOURCE="${CRON_FILE_PATH:-/var/www/html/docker/common/cron/app.cron}"
CRON_TARGET=/etc/cron.d/app-cron

if [ ! -f "$CRON_SOURCE" ]; then
    echo "[cron] Cron definition not found: $CRON_SOURCE" >&2
    exit 1
fi

install -m 0644 "$CRON_SOURCE" "$CRON_TARGET"

# Ensure the file ends with a newline so cron doesn't ignore the last entry
if [ -n "$(tail -c1 "$CRON_TARGET" 2>/dev/null)" ]; then
    printf '\n' >>"$CRON_TARGET"
fi

crontab "$CRON_TARGET"

touch /var/log/cron.log
chown www-data:www-data /var/log/cron.log || true

exec "$@"
