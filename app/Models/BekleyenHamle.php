<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Dış-eylem onay kuyruğundaki bir hamle kartı — gerekçe migration'da.
 *
 * @property ?int $kahya_gorevi_id
 * @property string $baslik
 * @property string $gerekce
 * @property string $icerik
 * @property string $tur
 * @property ?string $alici_eposta
 * @property string $durum
 * @property ?string $karar_notu
 * @property ?Carbon $karar_at
 * @property ?Carbon $gonderildi_at
 * @property ?string $gonderim_hata
 * @property ?string $cikis_jetonu
 */
class BekleyenHamle extends Model
{
    public const DURUM_BEKLEMEDE = 'beklemede';

    public const DURUM_ONAYLANDI = 'onaylandi';

    public const DURUM_REDDEDILDI = 'reddedildi';

    protected $table = 'bekleyen_hamleler';

    protected $fillable = [
        'kahya_gorevi_id', 'baslik', 'gerekce', 'icerik', 'tur', 'alici_eposta',
        'durum', 'karar_notu', 'karar_at', 'gonderildi_at', 'gonderim_hata',
        'cikis_jetonu',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['karar_at' => 'datetime', 'gonderildi_at' => 'datetime'];
    }

    /** @return BelongsTo<KahyaGorevi, $this> */
    public function gorev(): BelongsTo
    {
        return $this->belongsTo(KahyaGorevi::class, 'kahya_gorevi_id');
    }

    /** @param  Builder<self>  $sorgu */
    public function scopeBeklemede(Builder $sorgu): void
    {
        $sorgu->where('durum', self::DURUM_BEKLEMEDE);
    }

    /**
     * Sahibin kararını işler. Karar bir kez verilir — kararı değiştirmek
     * yeni bir hamle kartı ister (denetim izi net kalsın).
     */
    public function kararVer(string $durum, ?string $not = null): void
    {
        if ($this->durum !== self::DURUM_BEKLEMEDE) {
            throw new \RuntimeException('Bu hamle için karar zaten verilmiş.');
        }

        $this->update([
            'durum' => $durum,
            'karar_notu' => $not,
            'karar_at' => now(),
        ]);
    }
}
