#!/bin/bash
set -e

echo "==> Starting mPM Laravel Application..."

# ============================================
# Environment Setup
# ============================================

if [ -f "/config/.env" ]; then
    echo "==> Using .env from persistent volume..."
    cp /config/.env /app/.env
    chown www-data:www-data /app/.env
else
    echo "==> WARNING: No .env file found in /config volume!"
    echo "==> Please mount your .env file to /config/.env"
fi

# ============================================
# Wait for Database
# ============================================

echo "==> Waiting for database connection..."

DB_HOST="${DB_HOST:-pgsql}"
DB_PORT="${DB_PORT:-5432}"
DB_DATABASE="${DB_DATABASE:-mpm}"

max_tries=30
counter=0

until pg_isready -h "$DB_HOST" -p "$DB_PORT" -U "${DB_USERNAME:-mpm}" > /dev/null 2>&1; do
    counter=$((counter+1))
    if [ $counter -gt $max_tries ]; then
        echo "==> ERROR: Could not connect to database after $max_tries attempts"
        echo "==> Connection details: $DB_HOST:$DB_PORT"
        exit 1
    fi
    echo "==> Waiting for database... (attempt $counter/$max_tries)"
    sleep 2
done

echo "==> Database connection established!"

# ============================================
# Storage Volume Setup
# ============================================

echo "==> Checking persistent storage volume..."

# If the mounted /app/storage is empty (fresh volume), seed it with the default structure
if [ -z "$(ls -A /app/storage 2>/dev/null)" ]; then
    echo "==> Storage volume is empty. Seeding default directory structure..."
    cp -r /app/storage-default/. /app/storage/
fi

# ============================================
# Laravel Optimizations
# ============================================

echo "==> Running Laravel optimizations..."

echo "==> Running database migrations..."
php /app/artisan migrate --force --no-interaction

echo "==> Caching configuration..."
php /app/artisan config:cache
php /app/artisan route:cache
php /app/artisan view:cache

echo "==> Refreshing storage symbolic link..."
rm -rf /app/public/storage
php /app/artisan storage:link

echo "==> Setting permissions..."
mkdir -p /app/storage/logs /app/bootstrap/cache
chown -R www-data:www-data /app/storage /app/bootstrap/cache
chmod -R 775 /app/storage /app/bootstrap/cache

# ============================================
# Prepare for Supervisor
# ============================================

export QUEUE_WORKERS="${QUEUE_WORKERS:-4}"
export QUEUE_NAME="${QUEUE_NAME:-default}"

echo "==> Queue worker and scheduler will be managed by Supervisor"
echo "==> Queue: ${QUEUE_NAME}"
echo "==> Queue workers: ${QUEUE_WORKERS}"

# ============================================
# Health Check
# ============================================

echo "==> Application is ready!"
echo "==> Environment: ${APP_ENV:-production}"
echo "==> Debug mode: ${APP_DEBUG:-false}"
echo "==> Database: $DB_HOST:$DB_PORT/$DB_DATABASE"

exec "$@"
