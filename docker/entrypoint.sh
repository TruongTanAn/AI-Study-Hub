#!/bin/bash
set -e

echo "======================================"
echo " AI Study Hub - Starting container"
echo "======================================"

mkdir -p /var/lib/php/sessions
chown -R www-data:www-data /var/lib/php/sessions
chmod -R 733 /var/lib/php/sessions

mkdir -p /var/www/html/uploads/documents
mkdir -p /var/www/html/logs
mkdir -p /var/www/html/cache

chown -R www-data:www-data /var/www/html/uploads
chown -R www-data:www-data /var/www/html/logs
chown -R www-data:www-data /var/www/html/cache
chmod -R 775 /var/www/html/uploads
chmod -R 775 /var/www/html/logs
chmod -R 775 /var/www/html/cache

echo "[entrypoint] Waiting for MySQL..."
MAX_TRIES=30
TRIES=0
DB_HOST='mysql'
DB_PORT='3306'

while [ $TRIES -lt $MAX_TRIES ]; do
    if mysqladmin ping -h "$DB_HOST" -P "$DB_PORT" -u root -proot_password --skip-ssl --silent 2>/dev/null; then
        echo "[entrypoint] MySQL is ready."
        break
    fi
    TRIES=$((TRIES+1))
    echo "[entrypoint] MySQL not ready yet (try $TRIES/$MAX_TRIES)..."
    sleep 3
done

if [ $TRIES -eq $MAX_TRIES ]; then
    echo "[entrypoint] WARNING: MySQL not reachable, continuing anyway..."
fi

echo "[entrypoint] Checking database import..."
if mysql -h "$DB_HOST" -P "$DB_PORT" -u root -proot_password --skip-ssl -e "USE ai_study_hub; SHOW TABLES;" 2>/dev/null | grep -q "users"; then
    echo "[entrypoint] Database already has tables. Skipping import."
else
    echo "[entrypoint] Importing database..."
    mysql -h "$DB_HOST" -P "$DB_PORT" -u root -proot_password --skip-ssl ai_study_hub < /var/www/html/database/database.sql 2>/dev/null || true
    echo "[entrypoint] Database import complete."
fi

echo "======================================"
echo " AI Study Hub - Container ready"
echo "======================================"

exec "$@"