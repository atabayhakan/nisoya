#!/usr/bin/env bash
# Nisoya — Üretim deploy betiği.
# Kullanım: cd /var/www/nisoya && bash deploy/deploy.sh
#
# Adımlar:
#   1. Bakım modu (down)
#   2. Kod güncelleme (git pull)
#   3. Bağımlılıklar (composer + npm)
#   4. Migrasyonlar
#   5. Önbellekler (config/route/view)
#   6. Storage symlink
#   7. Kuyruk worker nazik restart (queue:restart sinyali)
#   8. Yayın (up)
#   9. Smoke test (temel sağlık kontrolleri)
set -euo pipefail

cd /var/www/nisoya

# Renkli çıktı
RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'; NC='\033[0m'
info()    { printf "${GREEN}→${NC} %s\n" "$*"; }
warn()    { printf "${YELLOW}⚠${NC}  %s\n" "$*"; }
fail()    { printf "${RED}✗${NC}  %s\n" "$*"; exit 1; }

START=$(date +%s)

info "Bakım moduna alınıyor..."
php artisan down --render="errors::503" || warn "down komutu başarısız (devam ediliyor)"

info "Kod güncelleniyor..."
git pull origin main

info "PHP bağımlılıkları yükleniyor..."
composer install --no-dev --optimize-autoloader --no-interaction

info "Frontend varlıkları derleniyor..."
npm ci --no-audit --no-fund || npm install --no-audit --no-fund
npm run build

info "Veritabanı migrasyonları..."
php artisan migrate --force

info "Spatie Filament upgrade (varsa)..."
php artisan filament:upgrade || true

info "Storage symlink..."
php artisan storage:link || true

info "Önbellekler temizleniyor..."
php artisan optimize:clear

info "Önbellekler oluşturuluyor (config + route + view)..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

info "Kuyruk worker nazikçe yeniden başlatılıyor (queue:restart sinyali)..."
php artisan queue:restart || warn "queue:restart başarısız (devam ediliyor)"

info "Yayına alınıyor..."
php artisan up

# Smoke test
info "Smoke test çalıştırılıyor..."
SMOKE_PASS=true
if curl -s -o /dev/null -w "%{http_code}" -f http://127.0.0.1/health 2>/dev/null | grep -q '^200$'; then
    info "  ✓ /health → 200"
else
    warn "  ! /health → yanıt yok (henüz route yapılandırılmamış olabilir)"
fi

if curl -s -o /dev/null -w "%{http_code}" -f http://127.0.0.1/ 2>/dev/null | grep -q '^200$'; then
    info "  ✓ / → 200"
else
    fail "  ✗ / → 200 bekleniyordu (deploy başarısız olabilir)"
    SMOKE_PASS=false
fi

if curl -s -o /dev/null -w "%{http_code}" -f http://127.0.0.1/yonetim/login 2>/dev/null | grep -q '^200$'; then
    info "  ✓ /yonetim/login → 200"
else
    warn "  ! /yonetim/login → yanıt yok (admin panel route'ları eksik olabilir)"
fi

ELAPSED=$(( $(date +%s) - START ))
printf "${GREEN}✓${NC} Deploy tamamlandı (%d sn)\n" "$ELAPSED"

if [ "$SMOKE_PASS" = false ]; then
    warn "Smoke test BAŞARISIZ — logları kontrol edin: tail -50 storage/logs/laravel.log"
    exit 1
fi

info "İlk seferde reverse geocoding için:"
echo "    php artisan images:reverse-geocode"
echo "  (bir kere çalıştırın; 1.1 sn/görsel rate limit var)"