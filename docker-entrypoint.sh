#!/bin/sh
# Rebind Apache to $PORT when the platform injects one (HostForge/Railway style).
set -e
PORT="${PORT:-80}"
sed -i "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/\*:80>/*:${PORT}>/" /etc/apache2/sites-available/000-default.conf
exec "$@"
