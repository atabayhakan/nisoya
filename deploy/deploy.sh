#!/usr/bin/env bash
# Nisoya — Üretim deploy betiği.
# Kullanım: cd /var/www/nisoya && bash deploy/deploy.sh
#
# Adımlar:
#   1. Bakım modu (down) — trap ile her durumda up garanti edilir
#   2. Kod güncelleme (git pull)
#   3. Bağımlılıklar (composer + npm)
#   4. Migrasyonlar
#   5. Önbellekler (config/route/view/event)
#   6. Storage symlink
#   7. storage + bootstrap/cache sahipliği www-data'ya
#   8. Kuyruk worker nazik restart (queue:restart sinyali)
#   9. Yayın (up)
#  10. Smoke test (temel sağlık kontrolleri)
set -euo pipefail

cd /var/www/nisoya

# Renkli çıktı
RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'; NC='\033[0m'
info()    { printf "${GREEN}→${NC} %s\n" "$*"; }
warn()    { printf "${YELLOW}⚠${NC}  %s\n" "$*"; }
fail()    { printf "${RED}✗${NC}  %s\n" "$*"; exit 1; }

START=$(date +%s)

# GÜVENLİK: Deploy hangi adımda patlarsa patlasın (git pull, composer, migrate...)
# site ASLA bakım modunda kilitli kalmasın. trap, script çıkışında (başarı ya da
# hata) her zaman `php artisan up` çağırır. cleanup'ın ilk satırı $?'yı yakalamalı.
cleanup() {
    local code=$?
    if [ "$code" -ne 0 ]; then
        warn "Deploy başarısız oldu (çıkış kodu $code) — site yayına geri alınıyor..."
    fi
    php artisan up >/dev/null 2>&1 || true
}
trap cleanup EXIT

info "Bakım moduna alınıyor..."
php artisan down --render="errors::503" --retry=15 || warn "down komutu başarısız (devam ediliyor)"

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

info "Önbellekler oluşturuluyor (config + route + view + event)..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Deploy root olarak (GitHub Actions SSH) çalışır; artisan komutlarının ürettiği
# cache/log dosyaları root sahipli olur. php-fpm (www-data) storage ve
# bootstrap/cache içine YAZABİLMELİ. SADECE bu iki dizin chown edilir — tüm
# ağaçta chown (vendor'da binlerce dosya) SSH komutunu 5 dk timeout'a takar.
info "storage + bootstrap/cache sahipliği www-data'ya veriliyor..."
chown -R www-data:www-data storage bootstrap/cache

info "Kuyruk worker nazikçe yeniden başlatılıyor (queue:restart sinyali)..."
php artisan queue:restart || warn "queue:restart başarısız (devam ediliyor)"

info "Yayına alınıyor..."
php artisan up
trap - EXIT   # buradan sonra deploy başarılı; trap'e gerek yok

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

info "İlk seferde (eski görseller için) çalıştırılacak komutlar:"
echo "    php artisan images:reverse-geocode    # GPS'ten şehir/ülke tespiti"
echo "    php artisan images:reprocess          # EXIF orientation + metadata temizliği"
echo "  (Nominatim rate limit: 1.1 sn/görsel, dakikada max 60 işlem)"
