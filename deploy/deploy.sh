#!/usr/bin/env bash
# Nisoya — Üretim deploy betiği. Sunucuda: bash deploy/deploy.sh
set -euo pipefail

cd /var/www/nisoya

echo "→ Bakım moduna alınıyor..."
php artisan down --render="errors::503" || true

echo "→ Kod güncelleniyor..."
git pull origin main

echo "→ PHP bağımlılıkları..."
composer install --no-dev --optimize-autoloader --no-interaction

echo "→ Frontend derleniyor..."
npm ci
npm run build

echo "→ Migrasyonlar..."
php artisan migrate --force

echo "→ Önbellekler yenileniyor..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan storage:link || true

echo "→ Kuyruk işçisi yeniden başlatılıyor..."
php artisan queue:restart || true
sudo supervisorctl restart nisoya-worker:* || true

echo "→ Yayına alınıyor..."
php artisan up

echo "✓ Deploy tamamlandı."
