<?php

namespace App\Models;

use App\Enums\EylemRiski;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Kâhya'nın yaptığı (ya da onay bekleyen) tek bir eylemin kaydı.
 *
 * Bu tablo hem DENETİM İZİ hem GERİ ALMA YOLUDUR. "Kâhya ne yaptı" sorusunun
 * cevabı burada; "geri al" düğmesinin dayandığı veri de burada.
 *
 * @property string $eylem
 * @property string $durum
 * @property array<string, mixed> $parametreler
 * @property string $onizleme
 * @property ?array<string, mixed> $geri_alma
 * @property ?string $sonuc
 * @property ?string $hata
 */
class KahyaEylemKaydi extends Model
{
    public const DURUM_BEKLEMEDE = 'beklemede';

    public const DURUM_UYGULANDI = 'uygulandi';

    public const DURUM_GERI_ALINDI = 'geri_alindi';

    public const DURUM_REDDEDILDI = 'reddedildi';

    public const DURUM_HATA = 'hata';

    protected $table = 'kahya_eylemleri';

    protected $fillable = [
        'eylem', 'durum', 'risk', 'parametreler', 'onizleme',
        'geri_alma', 'sonuc', 'hata', 'user_id', 'uygulandi_at', 'geri_alindi_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'parametreler' => 'array',
            'geri_alma' => 'array',
            'risk' => EylemRiski::class,
            'uygulandi_at' => 'datetime',
            'geri_alindi_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @param  Builder<self>  $query */
    public function scopeBeklemede(Builder $query): void
    {
        $query->where('durum', self::DURUM_BEKLEMEDE);
    }

    /**
     * Geri alınabilir mi?
     *
     * İki koşul birden: eylem UYGULANMIŞ olmalı ve geri alma izi YAZILMIŞ
     * olmalı. İz yoksa "geri aldım" demek yalan olurdu.
     */
    public function geriAlinabilirMi(): bool
    {
        return $this->durum === self::DURUM_UYGULANDI && ! empty($this->geri_alma);
    }

    public function durumEtiketi(): string
    {
        return match ($this->durum) {
            self::DURUM_BEKLEMEDE => 'Onay bekliyor',
            self::DURUM_UYGULANDI => 'Uygulandı',
            self::DURUM_GERI_ALINDI => 'Geri alındı',
            self::DURUM_REDDEDILDI => 'Reddedildi',
            self::DURUM_HATA => 'Hata',
            default => $this->durum,
        };
    }
}
