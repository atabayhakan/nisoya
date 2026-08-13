<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Bir temsilcilikteki tek bir işlemin rehber içeriği — sitenin yüzü.
 *
 * TASLAK-ÖNCE (K7): seeder ve yeni kayıtlar taslak doğar; sahip resmî
 * kaynaktan doğrulayıp yayına alır. Doğrulanmamış içerik yayınlanmaz,
 * çünkü yanlış evrak listesi kullanıcıyı konsolosluk kapısından geri
 * çevirtir ve "güven" markasını tersine çevirir.
 *
 * @property Carbon|null $dogrulanma_tarihi
 * @property list<array{ad: string, not: string|null}> $evraklar
 */
class TemsilcilikIslemi extends Model
{
    public const STATUS_TASLAK = 'taslak';

    public const STATUS_YAYIN = 'yayin';

    /** Bu kadar gündür doğrulanmamış yayındaki kayıt "bayat" sayılır (K7). */
    public const BAYATLIK_GUN = 90;

    protected $table = 'temsilcilik_islemleri';

    protected $fillable = [
        'temsilcilik_id', 'islem_turu_id', 'evraklar', 'sure_metni',
        'ucret_metni', 'resmi_kaynak_url', 'notlar', 'dogrulanma_tarihi', 'status',
    ];

    protected function casts(): array
    {
        return [
            'dogrulanma_tarihi' => 'date',
        ];
    }

    /**
     * `evraklar` HER ZAMAN tek şekilde okunur: list<array{ad, not}>.
     *
     * -----------------------------------------------------------------------
     * CANLIDA BULUNDU (2026-08-13) — Berlin/Pasaport sayfasında "Gerekli
     * evraklar" başlığının altında SEKİZ BOŞ MADDE vardı: yalnız ✔ işaretleri,
     * hiç metin yok. Rehberin en değerli kısmı buydu.
     *
     * Sebep: aynı sütunda İKİ farklı şekil dolaşıyordu.
     *   - Yönetim paneli ve seeder taslakları  → ['ad' => …, 'not' => …]
     *   - JSON içe aktarımıyla gelen DOĞRULANMIŞ içerik → düz metin dizisi
     *
     * Görünüm yalnız birincisini biliyordu ve `?? ''` ile ikincisini SESSİZCE
     * boşa çeviriyordu (PHP'de metin üzerinde ['ad'] erişimi isset=false).
     * Yani kırık olan, tam da yayına alınmış gerçek içerikti.
     *
     * Düzeltme OKUMA anında: hem yayındaki kayıtlar üretim verisine
     * dokunmadan düzelir, hem yönetim panelindeki Repeater doğru şekli görür,
     * hem de bundan sonra hangi kaynaktan gelirse gelsin tek şekil geçerli
     * olur.
     */
    protected function evraklar(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value): array => self::evraklariDuzenle(
                is_array($cozulen = json_decode((string) $value, true)) ? $cozulen : []
            ),
            set: fn (mixed $value): string => (string) json_encode(
                self::evraklariDuzenle(is_array($value) ? $value : []),
                JSON_UNESCAPED_UNICODE
            ),
        );
    }

    /**
     * Düz metin de, dizi de kabul eder; hepsini ['ad','not'] şekline getirir.
     * Adı boş kalan madde ATILIR — boş bir ✔ satırı, satır olmamasından kötü.
     *
     * @param  array<int|string, mixed>  $ham
     * @return list<array{ad: string, not: string|null}>
     */
    public static function evraklariDuzenle(array $ham): array
    {
        $sonuc = [];

        foreach ($ham as $madde) {
            if (is_string($madde)) {
                $ad = trim($madde);
                $not = null;
            } elseif (is_array($madde)) {
                $ad = trim((string) ($madde['ad'] ?? ''));
                $not = trim((string) ($madde['not'] ?? '')) ?: null;
            } else {
                continue;
            }

            if ($ad === '') {
                continue;
            }

            $sonuc[] = ['ad' => $ad, 'not' => $not];
        }

        return $sonuc;
    }

    /** @return BelongsTo<Temsilcilik, $this> */
    public function temsilcilik(): BelongsTo
    {
        return $this->belongsTo(Temsilcilik::class, 'temsilcilik_id');
    }

    /** @return BelongsTo<IslemTuru, $this> */
    public function islemTuru(): BelongsTo
    {
        return $this->belongsTo(IslemTuru::class, 'islem_turu_id');
    }

    /** @return HasMany<RehberGeriBildirimi, $this> */
    public function geriBildirimler(): HasMany
    {
        return $this->hasMany(RehberGeriBildirimi::class, 'temsilcilik_islemi_id');
    }

    /** @param Builder<TemsilcilikIslemi> $query */
    public function scopeYayinda(Builder $query): void
    {
        $query->where('status', self::STATUS_YAYIN);
    }

    /**
     * Yayında olup doğrulaması eskimiş kayıtlar — Kâhya'nın günlük
     * raporundaki "bayat rehber" kuyruğunun kaynağı.
     *
     * @param  Builder<TemsilcilikIslemi>  $query
     */
    public function scopeBayat(Builder $query): void
    {
        $query->yayinda()->where(function (Builder $q) {
            $q->whereNull('dogrulanma_tarihi')
                ->orWhere('dogrulanma_tarihi', '<', now()->subDays(self::BAYATLIK_GUN));
        });
    }

    public function yayindaMi(): bool
    {
        return $this->status === self::STATUS_YAYIN;
    }
}
