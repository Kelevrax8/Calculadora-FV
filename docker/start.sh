#!/bin/sh
set -e

# Substitute only $PORT; leave all other nginx variables ($uri, $document_root, etc.) intact
envsubst '$PORT' < /etc/nginx/templates/app.conf.template > /etc/nginx/conf.d/app.conf

# Auto-initialize the database schema (safe to run repeatedly — uses IF NOT EXISTS)
echo "Running database schema init..."
mysql \
    -h"${DB_HOST}" \
    -P"${DB_PORT:-3306}" \
    -u"${DB_USER}" \
    -p"${DB_PASSWORD}" \
    "${DB_NAME}" < /var/www/html/Schema.sql && echo "Schema init done." || echo "Schema init failed — check DB env vars."

# Start php-fpm in the background
php-fpm &

# Start nginx in the foreground (keeps the container alive)
exec nginx -g "daemon off;"
