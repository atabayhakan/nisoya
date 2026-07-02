#!/usr/bin/env bash
# Nisoya — Günlük yedekleme scripti.
# /etc/cron.daily/nisoya-backup olarak çalışır.
# sudo chmod +x ile çalıştırılabilir yap.
set -euo pipefail

BACKUP_DIR=${BACKUP_DIR:-/var/backups/nisoya}
RETENTION_DAYS=${RETENTION_DAYS:-14}
TS=$(date +%F-%H%M%S)
DAY=$(date +%F)
TS_FILE="${DAY}_${TS}"

mkdir -p "$BACKUP_DIR"

# 1. Veritabanı
echo "→ DB dump..."
DB_FILE="$BACKUP_DIR/db-$TS_FILE.sql.gz"
mysqldump nisoya 2>/dev/null | gzip > "$DB_FILE"
echo "  ✓ $DB_FILE ($(du -h "$DB_FILE" | cut -f1))"

# 2. Storage (yüklenen görseller)
echo "→ Storage yedeği..."
STORAGE_FILE="$BACKUP_DIR/storage-$TS_FILE.tar.gz"
tar czf "$STORAGE_FILE" -C /var/www/nisoya storage/app/public
echo "  ✓ $STORAGE_FILE ($(du -h "$STORAGE_FILE" | cut -f1))"

# 3. Activity log (son 90 gün)
echo "→ Activity log dışa aktarımı..."
ACTIVITY_FILE="$BACKUP_DIR/activity-$TS_FILE.json"
php /var/www/nisoya/artisan tinker --execute='echo \App\Models\Activity::query()->where("created_at", ">=", now()->subDays(90))->get()->toJson();' > "$ACTIVITY_FILE" 2>/dev/null || echo "[]" > "$ACTIVITY_FILE"
echo "  ✓ $ACTIVITY_FILE"

# 4. Site ayarları (DB dump'ın parçası olarak zaten var)

# 5. Eski yedekleri temizle
echo "→ Eski yedekler temizleniyor (>${RETENTION_DAYS} gün)..."
find "$BACKUP_DIR" -type f -mtime +${RETENTION_DAYS} -delete
echo "  ✓ Temizlendi"

# 6. Disk kullanımı
echo "→ Disk kullanımı:"
df -h "$BACKUP_DIR" | tail -1

# 7. Toplam boyut
TOTAL=$(du -sh "$BACKUP_DIR" | cut -f1)
echo "→ Toplam yedek boyutu: $TOTAL"

echo "✓ Yedekleme tamamlandı: $(date)"