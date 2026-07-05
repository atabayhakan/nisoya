#!/usr/bin/env bash
# Nisoya — Üretim deploy betiği.
# Kullanım: cd /var/www/nisoya && git pull origin main && bash deploy/deploy.sh
# (git pull bilinçli olarak script DIŞINDA — bkz. aşağıdaki not.)
#
# Adımlar:
#   1. Bakım modu (down) — trap ile her durumda up garanti edilir
#   2. Bağımlılıklar (composer + npm)
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

# NOT: `git pull` bilinçli olarak BURADA yok. Bu script kendini güncellerse
# (deploy.sh değişmişse) bash çalışan sürümü bozar (kendini-değiştiren script).
# Kod güncellemesi bu script ÇAĞRILMADAN ÖNCE yapılır: CI'da .github/workflows/
# deploy.yml `git pull origin main` çalıştırır; elle çalıştırırken de önce
# `git pull origin main && bash deploy/deploy.sh` yapılmalı.

info "PHP bağımlılıkları yükleniyor..."
composer install --no-dev --optimize-autoloader --no-interaction

info "Frontend varlıkları derleniyor..."
npm ci --no-audit --no-fund || npm install --no-audit --no-fund
npm run build

info "Veritabanı migrasyonları..."
php artisan migrate --force

# Referans/taksonomi verilerini senkronla (para birimi, ülke, şehir,
# kategoriler, iş kategorileri). SADECE ReferenceDataSeeder çalışır —
# hepsi updateOrCreate ile idempotenttir, mevcut veriyi bozmaz ama yeni
# kod ile gelen referans verilerini otomatik canlıya taşır. Migration
# boş tablo oluşturur; seed etmezsek (örn. yeni JobCategory tablosu) tablo
# boş kalır. AdminUser/Demo/SiteSetting seeder'ları KASITLI olarak dışarıda.
info "Referans verileri senkronlanıyor..."
php artisan db:seed --class=ReferenceDataSeeder --force || warn "referans seed atlandı"

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
# NOT: nginx http→https yönlendirmesi yapıyor ve host-bazlı çalışıyor
# (server_name nisoya.com). Bu yüzden düz `http://127.0.0.1/` isteği 404/301
# döner. Doğru test: APP_URL'deki domain'i Host başlığı olarak verip, self
# origin'e (127.0.0.1) https üzerinden cert doğrulaması OLMADAN (-k) istek at.
info "Smoke test çalıştırılıyor..."
SMOKE_PASS=true
APP_HOST=$(grep '^APP_URL=' .env | sed -E 's#^APP_URL=https?://##; s#/.*$##')
APP_HOST=${APP_HOST:-nisoya.com}
smoke() { curl -sk -H "Host: $APP_HOST" -o /dev/null -w "%{http_code}" "https://127.0.0.1$1" 2>/dev/null; }

if [ "$(smoke /health)" = "200" ]; then
    info "  ✓ /health → 200"
else
    warn "  ! /health → yanıt yok (henüz route yapılandırılmamış olabilir)"
fi

if [ "$(smoke /)" = "200" ]; then
    info "  ✓ / → 200"
else
    SMOKE_PASS=false
    fail "  ✗ / → 200 bekleniyordu (deploy başarısız olabilir)"
fi

if [ "$(smoke /yonetim/login)" = "200" ]; then
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
