<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Kâhya'nın görev defterinin bir kaydı — gerekçe migration'da.
 *
 * @property string $baslik
 * @property string $hedef
 * @property string $durum
 * @property ?array<int, array{metin: string, durum: string}> $adimlar
 * @property ?array<int, array{t: string, not: string}> $ilerleme_notlari
 * @property ?Carbon $son_islem_at
 */
class KahyaGorevi extends Model
{
    public const DURUM_ACIK = 'acik';

    public const DURUM_TAMAMLANDI = 'tamamlandi';

    public const DURUM_IPTAL = 'iptal';

    protected $table = 'kahya_gorevleri';

    protected $fillable = ['baslik', 'hedef', 'durum', 'adimlar', 'ilerleme_notlari', 'son_islem_at'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'adimlar' => 'array',
            'ilerleme_notlari' => 'array',
            'son_islem_at' => 'datetime',
        ];
    }

    /** @return HasMany<BekleyenHamle, $this> */
    public function hamleler(): HasMany
    {
        return $this->hasMany(BekleyenHamle::class);
    }

    /** @param  Builder<self>  $sorgu */
    public function scopeAcik(Builder $sorgu): void
    {
        $sorgu->where('durum', self::DURUM_ACIK);
    }

    /** Sıradaki bekleyen adımın metni — plan bittiyse null. */
    public function siradakiAdim(): ?string
    {
        foreach ($this->adimlar ?? [] as $adim) {
            if (($adim['durum'] ?? '') === 'bekliyor') {
                return $adim['metin'];
            }
        }

        return null;
    }

    /** @return array{yapildi: int, toplam: int} */
    public function ilerleme(): array
    {
        $adimlar = $this->adimlar ?? [];

        return [
            'yapildi' => count(array_filter($adimlar, fn (array $a): bool => ($a['durum'] ?? '') === 'yapildi')),
            'toplam' => count($adimlar),
        ];
    }
}
