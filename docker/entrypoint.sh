#!/bin/sh

echo "Waiting for database..."

until nc -z "$DB_HOST" "$DB_PORT"; do
  sleep 1
done

echo "Database is up, running migrations..."
php artisan migrate --force

chown -R www-data:www-data /var/www/storage
chown -R www-data:www-data /var/www/bootstrap/cache

chmod -R 775 /var/www/storage
chmod -R 775 /var/www/bootstrap/cache

echo "Starting PHP-FPM..."
exec "$@"

