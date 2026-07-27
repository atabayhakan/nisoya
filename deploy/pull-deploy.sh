#!/usr/bin/env bash
# Nisoya — PULL-TABANLI otomatik deploy yoklayıcısı.
#
# NEDEN: GitHub Actions'ın push-tabanlı deploy'u (SSH ile sunucuya bağlanma)
# 2026-07-27'de aralıklı `dial tcp :22 i/o timeout` almaya başladı — son 8
# denemenin 6'sı düştü. Sunucu tarafı temizdi (UFW OpenSSH herkese açık,
# sshd dinliyor, fail2ban yok, hPanel'de kural yok); tcpdump ile doğrulandı:
# başarısız denemelerde GitHub'ın SYN paketi sunucuya HİÇ ULAŞMIYOR, başarılı
# denemede ulaşıyor. Yani engel Hostinger'ın ağ katmanında ve GELEN bağlantıyı
# aralıklı olarak vuruyor.
#
# ÇÖZÜM: yönü ters çevir. Sunucu GitHub'ı kendisi yoklar (GİDEN bağlantı —
# bu yön sorunsuz), değişiklik varsa deploy eder.
#
# Teşhis:
#   systemctl list-timers nisoya-autodeploy.timer
#   journalctl -u nisoya-autodeploy -n 80 --no-pager
#
# BU DOSYA KENDİNİ GÜNCELLEMEZ. Değiştirdikten sonra kurulu kopyayı elle
# tazele (bilinçli: root olarak çalışan bir betiği depodan otomatik kopyalamak
# gereksiz bir yetki-yükseltme yüzeyi açar):
#   install -m 755 -o root -g root /var/www/nisoya/deploy/pull-deploy.sh \
#           /usr/local/sbin/nisoya-autodeploy
set -uo pipefail

REPO=/var/www/nisoya
BRANCH=main
STATE_DIR=/var/lib/nisoya
STATE_OK="$STATE_DIR/last-deployed"       # başarıyla deploy edilen son SHA
STATE_FAIL="$STATE_DIR/failed-attempts"   # "<sha> <deneme>"
STATE_FETCH="$STATE_DIR/fetch-failures"   # arka arkaya fetch hatası sayısı
MAX_FAIL=3                                # aynı commit bu kadar denenir, sonra durur
FETCH_WARN=10                             # ~20 dk boyunca fetch tutmazsa yüksek sesle uyar

log() { printf '[%s] %s\n' "$(date '+%F %T')" "$*"; }

# Yalnızca rakam içeren state değerleri bash aritmetiğine girebilir.
sayi() { case "${1:-}" in ''|*[!0-9]*) echo 0 ;; *) echo "$1" ;; esac; }

# Site bakım modunda mı? (Laravel 11+ maintenance.php, eski sürüm 'down')
bakimda_mi() {
    [ -f "$REPO/storage/framework/maintenance.php" ] || [ -f "$REPO/storage/framework/down" ]
}

yayina_al() { (cd "$REPO" && php artisan up) >/dev/null 2>&1 || true; }

if ! mkdir -p "$STATE_DIR"; then
    log "HATA: $STATE_DIR oluşturulamadı — deploy yapılamıyor."
    exit 1
fi

cd "$REPO" || { log "HATA: $REPO bulunamadı."; exit 1; }

# --- Kilit ---
# Kilit dosyasının açılamaması ile "başkası tutuyor" durumu AYRI ele alınmalı;
# aksi halde her hata "başka deploy sürüyor" diye yorumlanıp deploy sessizce
# sonsuza dek durur (systemd yeşil görünürken).
# NOT: `exec 9>dosya` başarısız olursa etkileşimsiz kabuk ANINDA çıkar —
# `if ! exec ...` ile hatayı yakalayamayız. Bu yüzden önce touch ile dosyanın
# açılabildiğini doğruluyoruz.
if ! touch "$STATE_DIR/deploy.lock" 2>/dev/null; then
    log "HATA: kilit dosyası oluşturulamadı ($STATE_DIR/deploy.lock)."
    exit 1
fi
exec 9>"$STATE_DIR/deploy.lock"
if ! flock -n 9; then
    log "Başka bir deploy sürüyor — bu tur atlanıyor."
    exit 0
fi

# --- Kurtarma ağı ---
# Kilidi ALDIYSAK tanım gereği başka deploy sürmüyor demektir. Buna rağmen
# site bakım modundaysa, önceki tur yarıda ölmüş (SIGKILL / OOM / reboot —
# bu durumlarda hiçbir trap çalışmaz) ve site 503'te kilitli kalmıştır.
if bakimda_mi; then
    log "UYARI: deploy sürmüyor ama site bakım modunda (önceki tur yarıda ölmüş) — yayına alınıyor."
    yayina_al
fi

# --- 1) GitHub'ı yokla (giden bağlantı) ---
FETCH_ERR=$(git fetch --quiet origin "$BRANCH" 2>&1)
if [ $? -ne 0 ]; then
    N=$(sayi "$(cat "$STATE_FETCH" 2>/dev/null)")
    N=$((N + 1))
    echo "$N" > "$STATE_FETCH"
    if [ "$N" -ge "$FETCH_WARN" ]; then
        # Kalıcı arıza (bozuk deploy key, host key değişimi, DNS) sessiz
        # kalmamalı — aksi halde "yeni commit yok" durumundan ayırt edilemez.
        log "HATA: git fetch arka arkaya $N kez başarısız — deploy DURMUŞ durumda. Son hata: ${FETCH_ERR:-bilinmiyor}"
    else
        log "UYARI: git fetch başarısız ($N. kez, geçici olabilir): ${FETCH_ERR:-bilinmiyor}"
    fi
    exit 0
fi
rm -f "$STATE_FETCH"

if ! TARGET=$(git rev-parse "origin/$BRANCH" 2>/dev/null); then
    log "HATA: origin/$BRANCH çözümlenemedi."
    exit 1
fi

LAST_OK=$(cat "$STATE_OK" 2>/dev/null || echo "")

# İlk çalıştırma: state yoksa mevcut HEAD'i "deploy edilmiş" say ki kurulum
# anında gereksiz bir deploy tetiklenmesin.
if [ -z "$LAST_OK" ]; then
    LAST_OK=$(git rev-parse HEAD 2>/dev/null) || { log "HATA: HEAD çözümlenemedi."; exit 1; }
    echo "$LAST_OK" > "$STATE_OK" || { log "HATA: $STATE_OK yazılamadı."; exit 1; }
    log "İlk çalıştırma — mevcut sürüm ($LAST_OK) referans alındı."
fi

[ "$TARGET" = "$LAST_OK" ] && exit 0   # değişiklik yok (sessiz)

# --- 2) Aynı commit tekrar tekrar düşüyorsa dur ---
FAIL_SHA=$(awk 'NR==1{print $1}' "$STATE_FAIL" 2>/dev/null || echo "")
FAIL_N=$(sayi "$(awk 'NR==1{print $2}' "$STATE_FAIL" 2>/dev/null)")

if [ "$FAIL_SHA" = "$TARGET" ] && [ "$FAIL_N" -ge "$MAX_FAIL" ]; then
    log "DURDURULDU: $TARGET commit'i $FAIL_N kez deploy edilemedi; yeni deneme yapılmıyor."
    log "  Hatayı görmek için: cd $REPO && bash deploy/deploy.sh"
    log "  Düzeltip yeni commit atınca otomatik deploy kendiliğinden devam eder."
    exit 1
fi

# --- 3) Kodu güncelle ve deploy et ---
log "Yeni sürüm bulundu: $LAST_OK → $TARGET"

# reset --hard: sunucudaki yerel kirlilik (ör. npm install'ın değiştirdiği
# package-lock.json) güncellemeyi bloklamasın. .env/vendor/node_modules/
# storage/public/build gitignore'lu olduğu için dokunulmaz.
if ! git reset --hard "origin/$BRANCH" >/dev/null 2>&1; then
    log "HATA: git reset başarısız."
    echo "$TARGET $((FAIL_N + 1))" > "$STATE_FAIL"
    exit 1
fi

# WRITE-AHEAD: denemeyi deploy.sh ÇAĞRILMADAN ÖNCE diske yaz. Süreç
# öldürülürse (systemd TimeoutStartSec, OOM, reboot) başarısızlığı yazan
# satıra hiç ulaşılamaz; sayaç önceden yazılmazsa MAX_FAIL freni asla
# devreye girmez ve aynı bozuk commit sonsuza dek denenir.
ATTEMPT=$((FAIL_N + 1))
echo "$TARGET $ATTEMPT" > "$STATE_FAIL"

# Sinyalle ölürsek siteyi yayında bırak (SIGKILL'i hiçbir trap yakalayamaz —
# onu yukarıdaki kurtarma ağı bir sonraki turda temizler).
trap 'trap - INT TERM HUP; log "SİNYAL alındı — site yayına alınıyor."; yayina_al; exit 143' INT TERM HUP

# NOT: deploy.sh bilinçli olarak kod güncellendikten SONRA çağrılır — script
# kendini güncellerse bash çalışan sürümü bozar (kendini-değiştiren script).
# NISOYA_DEPLOY_LOCK: kilidi zaten biz tutuyoruz, deploy.sh tekrar almasın.
if NISOYA_DEPLOY_LOCK=1 bash deploy/deploy.sh; then
    echo "$TARGET" > "$STATE_OK"
    rm -f "$STATE_FAIL"
    log "✓ Deploy başarılı: $TARGET"
    exit 0
fi

RC=$?
log "✗ Deploy BAŞARISIZ (çıkış kodu $RC, deneme $ATTEMPT/$MAX_FAIL)."

# --- 4) Son denemede de düştüyse: çalışan sürüme geri dön ---
# Kod ağacı artık BOZUK commit'te ve deploy.sh'ın trap'i siteyi o kodla
# yayına almış durumda. Sınırlı (tek seferlik) otomatik geri alma, siteyi
# bilinen çalışan sürüme döndürür.
if [ "$ATTEMPT" -ge "$MAX_FAIL" ]; then
    log "GERİ ALINIYOR → $LAST_OK (son çalışan sürüm)"
    if git reset --hard "$LAST_OK" >/dev/null 2>&1 && NISOYA_DEPLOY_LOCK=1 bash deploy/deploy.sh; then
        log "✓ Geri alma başarılı — site $LAST_OK sürümünde çalışıyor. $TARGET commit'i düzeltilmeli."
    else
        log "✗ GERİ ALMA DA BAŞARISIZ — ELLE MÜDAHALE GEREKİYOR: cd $REPO && bash deploy/deploy.sh"
        bakimda_mi && yayina_al
    fi
fi

exit 1
