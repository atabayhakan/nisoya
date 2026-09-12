<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Bir yaşam konusunun (`YasamKonusu`) tek bir ülkedeki içeriği. Ülke
 * Rehberi'ndeki `TemsilcilikIslemi`nin karşılığı — aynı taslak/yayın ve
 * bayatlık (K7) desenini taşır.
 *
 * `icerik` markdown DEĞİL, DÜZ (iç içe olmayan) blok listesi:
 * `[{"tip": "baslik|paragraf|madde", "metin": "..."}, ...]`. Ardışık "madde"
 * blokları Blade'de tek `<ul>`'a toplanır. Bu depoda gövde içerik hiçbir
 * yerde serbest markdown olarak saklanmıyor (bkz. migration yorumu).
 *
 * @property int $id
 * @property int $yasam_konusu_id
 * @property string $country_code
 * @property array<int, array{tip: string, metin: string}>|null $icerik
 * @property string|null $kaynak_url
 * @property string|null $kaynak_aciklama
 * @property string $status
 * @property string $yazan_tur
 * @property Carbon|null $dogrulanma_tarihi
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read YasamKonusu|null $konu
 * @property-read Country|null $country
 */
class YasamKonuIcerigi extends Model
{
    protected $table = 'yasam_konu_icerikleri';

    public const STATUS_TASLAK = 'taslak';

    public const STATUS_YAYIN = 'yayinda';

    public const YAZAN_AI = 'ai';

    public const YAZAN_TOPLULUK = 'topluluk';

    public const YAZAN_SAHIP = 'sahip';

    /** Ülke Rehberi'ndeki TemsilcilikIslemi::BAYATLIK_GUN ile aynı eşik. */
    public const BAYATLIK_GUN = 90;

    protected $fillable = [
        'yasam_konusu_id', 'country_code', 'icerik', 'kaynak_url',
        'kaynak_aciklama', 'dogrulanma_tarihi', 'status', 'yazan_tur',
    ];

    protected function casts(): array
    {
        return [
            'icerik' => 'array',
            'dogrulanma_tarihi' => 'date',
        ];
    }

    /** @return BelongsTo<YasamKonusu, $this> */
    public function konu(): BelongsTo
    {
        return $this->belongsTo(YasamKonusu::class, 'yasam_konusu_id');
    }

    /** @return BelongsTo<Country, $this> */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_code', 'code');
    }

    /** @return HasMany<YasamKonuOnerisi, $this> */
    public function oneriler(): HasMany
    {
        return $this->hasMany(YasamKonuOnerisi::class, 'yasam_konu_icerigi_id');
    }

    /** @param Builder<YasamKonuIcerigi> $query */
    public function scopeYayinda(Builder $query): void
    {
        $query->where('status', self::STATUS_YAYIN);
    }

    /**
     * Yayında ama doğrulanmamış ya da doğrulaması BAYATLIK_GUN'dan eski.
     * Ülke Rehberi'ndeki TemsilcilikIslemi::scopeBayat() ile birebir aynı
     * kural — Kâhya'nın BekleyenIsler'ı aynı desenle tüketir.
     *
     * @param  Builder<YasamKonuIcerigi>  $query
     */
    public function scopeBayat(Builder $query): void
    {
        $query->yayinda()
            ->where(function (Builder $q) {
                $q->whereNull('dogrulanma_tarihi')
                    ->orWhere('dogrulanma_tarihi', '<', now()->subDays(self::BAYATLIK_GUN));
            });
    }

    public function yayindaMi(): bool
    {
        return $this->status === self::STATUS_YAYIN;
    }
}
