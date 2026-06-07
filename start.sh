#!/bin/sh

# Default to port 80 if PORT is not set
export PORT=${PORT:-80}

# Replace ${PORT} in nginx config with actual port value
envsubst '${PORT}' < /etc/nginx/http.d/default.conf > /tmp/default.conf
mv /tmp/default.conf /etc/nginx/http.d/default.conf

# Start PHP-FPM in background
php-fpm -D

# Start Nginx in foreground
exec nginx -g "daemon off;"