#!/bin/sh
php-fpm -D
exec nginx -g "daemon off;"