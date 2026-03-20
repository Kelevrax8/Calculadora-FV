#!/bin/sh
set -e

# Substitute only $PORT; leave all other nginx variables ($uri, $document_root, etc.) intact
envsubst '$PORT' < /etc/nginx/templates/app.conf.template > /etc/nginx/conf.d/app.conf

# Start php-fpm in the background
php-fpm &

# Start nginx in the foreground (keeps the container alive)
exec nginx -g "daemon off;"
