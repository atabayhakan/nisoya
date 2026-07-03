#!/usr/bin/env bash
# Nisoya — Günlük yedekleme scripti (offsite + local).
# /etc/cron.daily/nisoya-backup olarak çalışır.
# sudo chmod +x ile çalıştırılabilir yap.
set -euo pipefail

# === Yapılandırma (env ile override edilebilir) ===
BACKUP_DIR=${BACKUP_DIR:-/var/backups/nisoya}
RETENTION_DAYS=${RETENTION_DAYS:-14}
S3_BUCKET=${S3_BACKUP_BUCKET:-}
S3_ENABLED="false"
if [ -n "$S3_BUCKET" ] && command -v aws > /dev/null 2>&1; then
    S3_ENABLED="true"
fi
SLACK_WEBHOOK_URL=${SLACK_BACKUP_WEBHOOK_URL:-}
DISCORD_WEBHOOK_URL=${DISCORD_BACKUP_WEBHOOK_URL:-}
ENCRYPTION_KEY=${BACKUP_ENCRYPTION_KEY:-}
TS=$(date +%F-%H%M%S)
DAY=$(date +%F)
TS_FILE="${DAY}_${TS}"

mkdir -p "$BACKUP_DIR"

# === Adım 1: Veritabanı dump ===
echo "→ DB dump..."
DB_FILE="$BACKUP_DIR/db-$TS_FILE.sql.gz"
mysqldump nisoya 2>/dev/null | gzip > "$DB_FILE"
DB_SIZE=$(du -h "$DB_FILE" | cut -f1)
echo "  ✓ $DB_FILE ($DB_SIZE)"

# === Adım 2: Storage yedeği (yüklenen görseller) ===
echo "→ Storage yedeği..."
STORAGE_FILE="$BACKUP_DIR/storage-$TS_FILE.tar.gz"
tar czf "$STORAGE_FILE" -C /var/www/nisoya storage/app/public
STORAGE_SIZE=$(du -h "$STORAGE_FILE" | cut -f1)
echo "  ✓ $STORAGE_FILE ($STORAGE_SIZE)"

# === Adım 3: Activity log dışa aktarımı (son 90 gün) ===
echo "→ Activity log dışa aktarımı..."
ACTIVITY_FILE="$BACKUP_DIR/activity-$TS_FILE.json"
php /var/www/nisoya/artisan tinker --execute='echo \App\Models\Activity::query()->where("created_at", ">=", now()->subDays(90))->get()->toJson();' > "$ACTIVITY_FILE" 2>/dev/null || echo "[]" > "$ACTIVITY_FILE"
echo "  ✓ $ACTIVITY_FILE"

# === Adım 4: Şifreleme (opsiyonel) ===
ALL_FILES=("$DB_FILE" "$STORAGE_FILE" "$ACTIVITY_FILE")
if [ -n "$ENCRYPTION_KEY" ] && command -v gpg > /dev/null 2>&1; then
    echo "→ Yedekler şifreleniyor (gpg)..."
    for f in "${ALL_FILES[@]}"; do
        gpg --batch --yes --symmetric --cipher-algo AES256 --passphrase "$ENCRYPTION_KEY" "$f"
        rm -f "$f"  # Şifrelenmemiş orijinali sil
    done
    # Artık şifreli dosyalar (*.gpg)
    DB_FILE="${DB_FILE}.gpg"
    STORAGE_FILE="${STORAGE_FILE}.gpg"
    ACTIVITY_FILE="${ACTIVITY_FILE}.gpg"
    echo "  ✓ AES-256 ile şifrelendi (gpg)"
fi

# === Adım 5: Doğrulama (checksum) ===
echo "→ Checksum hesaplama..."
CHECKSUM_FILE="$BACKUP_DIR/checksum-$TS_FILE.sha256"
{
    sha256sum "$DB_FILE" "$STORAGE_FILE" "$ACTIVITY_FILE"
} > "$CHECKSUM_FILE"
echo "  ✓ $CHECKSUM_FILE"

# === Adım 6: S3 offsite upload (opsiyonel) ===
S3_ERROR=""
if [ "$S3_ENABLED" = "true" ]; then
    echo "→ S3 offsite upload (s3://$S3_BUCKET/nisoya-backups/$DAY/)..."
    S3_PREFIX="s3://$S3_BUCKET/nisoya-backups/$DAY/"

    for f in "$DB_FILE" "$STORAGE_FILE" "$ACTIVITY_FILE" "$CHECKSUM_FILE"; do
        if aws s3 cp "$f" "$S3_PREFIX$(basename "$f")" --storage-class STANDARD_IA 2>&1; then
            echo "  ✓ $(basename "$f") → S3"
        else
            S3_ERROR="Bazı S3 upload'ları başarısız oldu!"
            echo "  ✗ $(basename "$f") → S3 başarısız"
        fi
    done
else
    echo "→ S3 offsite upload atlandı (S3_BACKUP_BUCKET env boş veya AWS CLI yok)"
fi

# === Adım 7: Eski yedekleri temizle ===
echo "→ Eski yedekler temizleniyor (>${RETENTION_DAYS} gün)..."
find "$BACKUP_DIR" -type f -mtime +${RETENTION_DAYS} -delete
echo "  ✓ Temizlendi"

# === Adım 8: Disk kullanımı ===
echo "→ Disk kullanımı:"
df -h "$BACKUP_DIR" | tail -1
TOTAL=$(du -sh "$BACKUP_DIR" | cut -f1)
echo "→ Toplam yedek boyutu: $TOTAL"

# === Adım 9: Bildirim (Slack/Discord) ===
NOTIFY_MESSAGE="✅ Nisoya backup tamamlandı
📅 Tarih: $TS_FILE
💾 DB: $DB_SIZE | Storage: $STORAGE_SIZE
📊 Toplam: $TOTAL
$(if [ "$S3_ENABLED" = "true" ]; then echo "☁️ S3: s3://$S3_BUCKET/"; fi)
$(if [ -n "$S3_ERROR" ]; then echo "⚠️ S3 Hatası: $S3_ERROR"; fi)"

if [ -n "$SLACK_WEBHOOK_URL" ]; then
    echo "→ Slack bildirimi gönderiliyor..."
    curl -s -X POST -H 'Content-Type: application/json' \
        -d "{\"text\":\"$NOTIFY_MESSAGE\"}" \
        "$SLACK_WEBHOOK_URL" > /dev/null || echo "  ✗ Slack gönderimi başarısız"
fi

if [ -n "$DISCORD_WEBHOOK_URL" ]; then
    echo "→ Discord bildirimi gönderiliyor..."
    curl -s -X POST -H 'Content-Type: application/json' \
        -d "{\"content\":\"$NOTIFY_MESSAGE\"}" \
        "$DISCORD_WEBHOOK_URL" > /dev/null || echo "  ✗ Discord gönderimi başarısız"
fi

# === Adım 10: S3'teki eski yedekleri temizle ===
if [ "$S3_ENABLED" = "true" ]; then
    echo "→ S3 eski yedekler temizleniyor..."
    CUTOFF=$(date -d "$RETENTION_DAYS days ago" +%F 2>/dev/null || date -v -${RETENTION_DAYS}d +%F 2>/dev/null)
    if [ -n "$CUTOFF" ]; then
        aws s3 ls "s3://$S3_BUCKET/nisoya-backups/" 2>/dev/null | awk '{print $2}' | while read -r prefix; do
            prefix_date=$(echo "$prefix" | tr -d '/')
            if [[ "$prefix_date" < "$CUTOFF" ]]; then
                aws s3 rm --recursive "s3://$S3_BUCKET/nisoya-backups/$prefix" 2>/dev/null
            fi
        done
    fi
fi

# === Adım 11: Doğrulama özeti ===
echo ""
echo "================================"
echo "Yedekleme özeti:"
echo "  DB:      $DB_FILE"
echo "  Storage: $STORAGE_FILE"
echo "  Log:     $ACTIVITY_FILE"
echo "  Sum:     $CHECKSUM_FILE"
if [ "$S3_ENABLED" = "true" ]; then
    echo "  S3:      s3://$S3_BUCKET/nisoya-backups/$DAY/"
fi
echo "================================"
echo "✓ Yedekleme tamamlandı: $(date)"
