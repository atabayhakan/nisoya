# Nisoya — Hostinger VPS Yayın Rehberi (Runbook)

Hedef: Ubuntu 22.04/24.04 VPS üzerinde Nginx + PHP 8.3-FPM + MySQL + Redis + Supervisor + Let's Encrypt SSL.
Uygulama dizini: `/var/www/nisoya` · Çalışan kullanıcı: `www-data`

> Bu betikler/komutlar erişim sağlanınca sunucuda uygulanacaktır. Yerel geliştirme SQLite, üretim MySQL kullanır (migrasyonlar DB-bağımsız yazıldı).

## 0. Ön gereksinimler
- VPS IP, SSH erişimi (root veya sudo'lu kullanıcı)
- Domain DNS A kaydı → VPS IP (www dahil)
- (Gerçek e-posta için) SMTP bilgileri

## 1. Sistem paketleri
```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y nginx mysql-server redis-server supervisor unzip git curl \
  php8.3-fpm php8.3-cli php8.3-mysql php8.3-mbstring php8.3-xml php8.3-curl \
  php8.3-zip php8.3-gd php8.3-intl php8.3-bcmath php8.3-redis
```
Composer:
```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```
Node (build için):
```bash
curl -fsSL https://deb.nodesource.com/setup_22.x | sudo -E bash -
sudo apt install -y nodejs
```

## 2. Veritabanı
```bash
sudo mysql -e "CREATE DATABASE nisoya CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
sudo mysql -e "CREATE USER 'nisoya'@'127.0.0.1' IDENTIFIED BY 'GÜÇLÜ_PAROLA';"
sudo mysql -e "GRANT ALL ON nisoya.* TO 'nisoya'@'127.0.0.1'; FLUSH PRIVILEGES;"
```

## 3. Kod
```bash
sudo mkdir -p /var/www && cd /var/www
sudo git clone <REPO_URL> nisoya      # veya rsync ile yükleme
sudo chown -R www-data:www-data /var/www/nisoya
cd nisoya
```
> Git remote yoksa: yerelden `rsync -avz --exclude vendor --exclude node_modules ./ kullanıcı@IP:/var/www/nisoya` ile yüklenebilir.

## 4. Uygulama yapılandırması
```bash
cp deploy/.env.production.example .env
# .env içini doldur (DB_PASSWORD, APP_URL, MAIL_*, ADSENSE_*, ANALYTICS_*, DONATION_*, REVERSE_GEOCODING_*)
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan key:generate
php artisan migrate --force
php artisan db:seed --class=Database\\Seeders\CurrencySeeder
php artisan db:seed --class=Database\\Seeders\CountrySeeder
php artisan db:seed --class=Database\\Seeders\CategorySeeder
php artisan db:seed --class=Database\\Seeders\AdminUserSeeder   # admin@nisoya.test — sonra şifre değiştir!
php artisan storage:link
php artisan config:cache && php artisan route:cache && php artisan view:cache
sudo chown -R www-data:www-data storage bootstrap/cache
```

## 4.5. İlk seferde çalıştırılması gereken komutlar
```bash
# Tüm GPS'li görseller için şehir/ülke tespiti (~1.1 sn/görsel, 100 görsel = ~2 dk)
# NOT: Laravel rate limit policy 'reverse-geocode' admin başına dakikada max 60 işlem
# uygular. Cron job ile arka planda çalıştırmak için:
#    * * * * * cd /var/www/nisoya && php artisan schedule:run >> /dev/null 2>&1
php artisan images:reverse-geocode

# Activity log tablosu otomatik migrate ile oluştu; geriye uyumluluk için eski
# ilan görsellerini yeniden işle (EXIF orientation + metadata temizliği):
php artisan images:reprocess

# Spam temizliği (gerekirse)
php artisan activitylog:clean   # Eski activity log'ları temizler
```

## 5. Nginx
```bash
sudo cp deploy/nginx-nisoya.conf /etc/nginx/sites-available/nisoya
sudo ln -s /etc/nginx/sites-available/nisoya /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
```

## 6. SSL (Let's Encrypt)
```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d nisoya.com -d www.nisoya.com
```

## 7. Kuyruk işçisi (Supervisor)
```bash
sudo cp deploy/supervisor-nisoya-worker.conf /etc/supervisor/conf.d/nisoya-worker.conf
sudo supervisorctl reread && sudo supervisorctl update
sudo supervisorctl start nisoya-worker:*
sudo supervisorctl start nisoya-schedule:*
# İki iş programı başlar:
#   - nisoya-worker:* (queue:work) — e-posta, bildirim, EXIF işleme
#   - nisoya-schedule:* (schedule:run) — reverse-geocode, expire-featured, saved-search alerts
sudo supervisorctl status
```

## 8. Zamanlayıcı (cron)
Supervisor `nisoya-schedule` zaten `schedule:run` çağrısını her dakika yapar. Ek cron gerekmez.

Alternatif (supervisor yerine cron isteyenler için):
```bash
sudo crontab -u www-data -e
# Ekle:
* * * * * cd /var/www/nisoya && php artisan schedule:run >> /dev/null 2>&1
```

## 9. Yedekleme (günlük cron)
```bash
sudo tee /etc/cron.daily/nisoya-backup > /dev/null <<'EOF'
#!/bin/bash
set -e
BACKUP_DIR=/var/backups/nisoya
mkdir -p $BACKUP_DIR
TS=$(date +%F)
# Veritabanı
mysqldump nisoya | gzip > $BACKUP_DIR/nisoya-db-$TS.sql.gz
# Storage (yüklenen görseller)
tar czf $BACKUP_DIR/nisoya-storage-$TS.tar.gz -C /var/www/nisoya storage/app/public
# 14 günden eski yedekleri sil
find $BACKUP_DIR -name 'nisoya-*' -mtime +14 -delete
EOF
sudo chmod +x /etc/cron.daily/nisoya-backup
```

Yedek doğrulama:
```bash
# Manuel test
sudo /etc/cron.daily/nisoya-backup
ls -la /var/backups/nisoya/
# DB geri yükleme testi
zcat /var/backups/nisoya/nisoya-db-2026-07-02.sql.gz | mysql -u root -p nisoya_test
```

## 10. Sonraki deploylar
```bash
cd /var/www/nisoya && bash deploy/deploy.sh
```

## 11. CI/CD (GitHub Actions → SSH)

`.github/workflows/deploy.yml` zaten `main` push'unda VPS'e SSH ile deploy yapar.

**GitHub Secrets** (repo → Settings → Secrets):
- `SSH_HOST` — VPS IP (örn. `72.62.115.3`)
- `SSH_USER` — SSH kullanıcısı (`root` veya `deployer`)
- `SSH_PRIVATE_KEY` — deploy anahtarı (sunucuda `~/.ssh/authorized_keys`)

**Ön koşul:** Sunucuda bir kez:
```bash
sudo mkdir -p /var/www/nisoya
sudo chown deployer:deployer /var/www/nisoya
cd /var/www/nisoya
git clone git@github.com:KULLANICI/nisoya.git .
```

Deploy otomatik tetiklenir:
1. `git push origin main`
2. GitHub Actions → test + build + SSH deploy
3. Sunucuda `bash deploy/deploy.sh` çalışır (smoke test dahil)
4. Slack/Discord'a bildirim (opsiyonel)

## 12. Monitoring (Uptime + Health)

**Ücretsiz uptime monitoring:**
- UptimeRobot.com → `https://nisoya.com/health` (HTTP 200 beklenecek)
- BetterStack.com → aynı şekilde
- Her 5 dakikada bir ping; 5xx yanıt → SMS/e-posta uyarısı

**Admin dashboard widget'ları** (otomatik):
- `/yonetim` → DB latency, cache, queue, storage durumu
- GPS'li görseller (gizlilik uyarıları)
- Toplam kullanıcı/ilan/mesaj istatistikleri

**Log monitoring:**
```bash
# Laravel logları
tail -f /var/www/nisoya/storage/logs/laravel-*.log | grep -E "ERROR|CRITICAL"

# Nginx access log (son istekler)
sudo tail -f /var/log/nginx/access.log | grep -E "500|502|503|504"

# Fail2ban (brute force koruması)
sudo fail2ban-client status sshd
```

## 13. Performans optimizasyonları (post-deploy)

```bash
# Görsel yükleme limitleri (/etc/php/8.3/fpm/php.ini) — uygulama görsel başına
# 4MB'a kadar izin veriyor (ListingRequest), stok php.ini varsayılanı (2M) bunun
# altında kaldığı için ayarlanmazsa "Görsel yüklenemedi." hatası verir.
upload_max_filesize=5M
post_max_size=40M            # 8 görsel × 5M
# Ardından: sudo systemctl reload php8.3-fpm
# nginx client_max_body_size da post_max_size'ı karşılamalı (bkz. nginx-nisoya.conf).

# PHP OPcache ayarı (/etc/php/8.3/fpm/php.ini)
opcache.enable=1
opcache.memory_consumption=256
opcache.max_accelerated_files=20000
opcache.validate_timestamps=0  # production'da kapalı

# MySQL slow query log
SET GLOBAL slow_query_log = 'ON';
SET GLOBAL long_query_time = 1;

# Redis monitoring
redis-cli INFO memory
redis-cli Info stats
```

## 14. Sorun giderme (Troubleshooting)

| Sorun | Çözüm |
|-------|-------|
| 500 Internal Server Error | `tail -50 storage/logs/laravel.log` |
| Service Worker (PWA) güncellenmiyor | `location = /sw.js` no-cache zaten ayarlı; gerekirse tarayıcı cache temizle |
| Görseller yüklenmiyor | `php artisan storage:link` + `chown -R www-data storage` |
| Queue çalışmıyor | `supervisorctl status` + `tail worker.log` |
| 419 CSRF hatası | APP_KEY aynı mı kontrol et, session driver çalışıyor mu |
| Tailwind v4 dark mode çalışmıyor | `npm run build` çalıştır, `app.css` build edilmiş mi kontrol et |
| EXIF haritası boş | `php artisan images:reverse-geocode` çalıştır, Nominatim rate limit (1.1 sn) |
| AdSense reklamları görünmüyor | `.env`'de `ADSENSE_ENABLED=true` + `ADSENSE_PUBLISHER_ID` ayarlı mı |

## Güvenlik kontrol listesi
- [ ] `APP_DEBUG=false`, `APP_ENV=production`
- [ ] Admin şifresi değiştirildi / yeni admin oluşturuldu
- [ ] UFW: yalnızca 22, 80, 443 açık
- [ ] SSH parola yerine anahtar (öneri), root login kapalı
- [ ] MySQL yalnızca 127.0.0.1
- [ ] Düzenli yedek doğrulandı
- [ ] HTTPS (Let's Encrypt) aktif
- [x] HSTS header aktif (2026-07-18; max-age=15768000, preload YOK — geri dönülebilir)
- [ ] Fail2ban çalışıyor

> Güvenlik başlıkları notu: aktif nginx config `sites-enabled/nisoya` (symlink
> DEĞİL, ayrı kopya — `sites-available` ile senkron değil). Başlık değişiklikleri
> AKTİF dosyada yapılmalı. Yedeği include edilen dizin İÇİNE koyma (`*` glob'una
> takılıp "duplicate listen" hatası verir) — `/root/` altına al.

### ⚠️ ELLE UYGULANMASI GEREKEN: Permissions-Policy (2026-08-13)

`deploy/nginx-nisoya.conf` düzeltildi ama **nginx yapılandırması otomatik
deploy edilmiyor** — sunucuda elle güncellenmeli.

Eski satır sitenin kendi özelliklerini kapatıyordu:

```
add_header Permissions-Policy "geolocation=(), microphone=(), camera=()" always;
```

Boş parantez "hiç kimseye izin yok" demek, kendi sayfamıza bile. Tarayıcıya
soruldu (`document.featurePolicy.allowsFeature`) ve üçü de `false` döndü.
Sonuç: **sesle ilan düğmesi görünüyor ama çalışmıyor**, acil panelindeki
konum tespiti hep yedeğe düşüyor.

Yeni satır (`sites-enabled/nisoya` içinde de değiştir):

```
add_header Permissions-Policy "geolocation=(self), microphone=(self), camera=()" always;
```

Sonra `sudo nginx -t && sudo systemctl reload nginx`. Doğrulama: tarayıcı
konsolunda `document.featurePolicy.allowsFeature('microphone')` → `true`.

`TarayiciIzinPolitikasiTest` bu kuralı depoda koruyor: kodda mikrofon/konum
kullanan bir yer varsa politika `self` vermeli, kullanılmayan yetenek ise
(kamera) kapalı kalmalı.
- [ ] .env dosyası .gitignore'da ve yedeklenmiyor

<!-- Otomatik deploy dogrulamasi: 2026-07-27T03:05:12Z -->
