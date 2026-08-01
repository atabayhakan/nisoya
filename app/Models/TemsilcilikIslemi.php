<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
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
            'evraklar' => 'array',
            'dogrulanma_tarihi' => 'date',
        ];
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
