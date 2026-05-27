#!/bin/sh
set -e

# Clear configuration cache to load fresh env vars on boot
echo "Clearing config..."
php artisan config:clear || true

# Execute the CMD passed to docker run (starts FrankenPHP instantly)
echo "Starting FrankenPHP webserver..."
exec "$@"
