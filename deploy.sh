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

# 3. Fix Permissions
echo "Fixing file and storage permissions..."
chmod -R 775 storage bootstrap/cache 2>/dev/null || true
find . -type d -exec chmod 755 {} +
find . -type f -exec chmod 644 {} +
chmod +x deploy.sh

# 4. Update Dependencies & Database
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

echo ""
echo "============================================"
echo "  atoscreen Deployment finished successfully!"
echo "  URL: https://trotiluxe.ma/v"
echo "============================================"
