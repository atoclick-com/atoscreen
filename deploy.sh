#!/bin/bash

# Configuration for trotiluxe.ma smart signage deployment
APP_DIR="/home/trotiluxe.ma/public_html/v"
BACKUP_DIR="/home/trotiluxe.ma/backups_v"
DATE=$(date +%Y%m%d_%H%M%S)

echo "Starting deployment for atoscreen at $DATE..."

# 1. Create Backup (excluding large dirs)
mkdir -p $BACKUP_DIR
echo "Creating backup of current code..."
tar -czf $BACKUP_DIR/code_backup_$DATE.tar.gz --exclude='.git' --exclude='node_modules' --exclude='vendor' --exclude='storage' -C $APP_DIR . 2>/dev/null || true

# 2. Pull from GitHub
echo "Pulling latest changes from GitHub (atoscreen)..."
cd $APP_DIR
git fetch origin main
git reset --hard origin/main

# 3. Environment & Key Configuration
if [ ! -f .env ]; then
    echo "Creating .env from .env.example..."
    cp .env.example .env
fi

if ! grep -q "^APP_KEY=base64:" .env; then
    echo "Generating Application Key..."
    php artisan key:generate --force
fi

if [ ! -f database/database.sqlite ]; then
    echo "Creating database.sqlite..."
    touch database/database.sqlite
fi
chmod 666 database/database.sqlite 2>/dev/null || true

# 4. Fix Permissions
echo "Fixing file and storage permissions..."
mkdir -p storage/framework/{sessions,views,cache/data} storage/logs storage/app/public bootstrap/cache
chmod -R 777 storage bootstrap/cache 2>/dev/null || true
find . -type d -exec chmod 755 {} +
find . -type f -exec chmod 644 {} +
chmod -R 777 storage bootstrap/cache 2>/dev/null || true
chmod +x deploy.sh

# 5. Update Dependencies & Database
echo "Updating Composer dependencies..."
composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev

echo "Running migrations..."
php artisan migrate --force

echo "Creating storage symlink..."
php artisan storage:link 2>/dev/null || true

# 5. Clear Caches
echo "Clearing Laravel caches..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
php artisan view:cache
chmod -R 777 storage bootstrap/cache 2>/dev/null || true

echo ""
echo "============================================"
echo "  atoscreen Deployment finished successfully!"
echo "  URL: https://trotiluxe.ma/v"
echo "============================================"
