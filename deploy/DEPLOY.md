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
# .env içini doldur (DB_PASSWORD, APP_URL, MAIL_*)
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan key:generate
php artisan migrate --force
php artisan db:seed --class=Database\\Seeders\\CurrencySeeder
php artisan db:seed --class=Database\\Seeders\\CountrySeeder
php artisan db:seed --class=Database\\Seeders\\CategorySeeder
php artisan db:seed --class=Database\\Seeders\\AdminUserSeeder   # admin@nisoya.test — sonra şifre değiştir!
php artisan storage:link
php artisan config:cache && php artisan route:cache && php artisan view:cache
sudo chown -R www-data:www-data storage bootstrap/cache
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
sudo supervisorctl reread && sudo supervisorctl update && sudo supervisorctl start nisoya-worker:*
```

## 8. Zamanlayıcı (cron)
```bash
sudo crontab -u www-data -e
# Ekle:
* * * * * cd /var/www/nisoya && php artisan schedule:run >> /dev/null 2>&1
```

## 9. Yedekleme (günlük cron)
```bash
# DB dump + storage yedeği — /etc/cron.daily/nisoya-backup
mysqldump nisoya | gzip > /var/backups/nisoya-$(date +\%F).sql.gz
tar czf /var/backups/nisoya-storage-$(date +\%F).tar.gz /var/www/nisoya/storage/app/public
```

## 10. Sonraki deploylar
```bash
cd /var/www/nisoya && bash deploy/deploy.sh
```

## Güvenlik kontrol listesi
- [ ] `APP_DEBUG=false`, `APP_ENV=production`
- [ ] Admin şifresi değiştirildi / yeni admin oluşturuldu
- [ ] UFW: yalnızca 22, 80, 443 açık
- [ ] SSH parola yerine anahtar (öneri), root login kapalı
- [ ] MySQL yalnızca 127.0.0.1
- [ ] Düzenli yedek doğrulandı
