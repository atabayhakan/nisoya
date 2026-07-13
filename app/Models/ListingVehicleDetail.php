<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Vasıta ilanının 1:1 detay kaydı — emlaktaki ListingPropertyDetail
 * deseninin aynısı (bkz. docs/plans/2026-07-13-emlak-vasita-davetiye-tasarim.md).
 */
class ListingVehicleDetail extends Model
{
    public const FUELS = [
        'benzin' => 'Benzin',
        'dizel' => 'Dizel',
        'hibrit' => 'Hibrit',
        'elektrik' => 'Elektrik',
        'lpg' => 'LPG',
    ];

    public const TRANSMISSIONS = [
        'manuel' => 'Manuel',
        'otomatik' => 'Otomatik',
        'yari_otomatik' => 'Yarı otomatik',
    ];

    public const BODY_TYPES = [
        'sedan' => 'Sedan',
        'hatchback' => 'Hatchback',
        'station' => 'Station wagon',
        'suv' => 'SUV',
        'minivan' => 'Minivan',
        'panelvan' => 'Panelvan',
        'pickup' => 'Pick-up',
        'motosiklet' => 'Motosiklet',
        'diger' => 'Diğer',
    ];

    /** Diasporaya özgü rozetler (anahtar DB'de, etiket arayüzde). */
    public const BADGES = [
        'kesin_donus' => 'Kesin dönüş nedeniyle acele satılık',
        'havalimani_teslim' => 'Havalimanı teslimi var',
        'ilk_sahibi' => 'İlk sahibinden',
        'bakim_kayitli' => 'Bakım kayıtları tam',
        'kis_lastigi' => 'Kış lastiği dahil',
    ];

    protected $fillable = [
        'listing_id',
        'brand',
        'model',
        'year',
        'mileage_km',
        'fuel',
        'transmission',
        'body_type',
        'color',
        'min_rental_days',
        'deposit',
        'km_limit_per_day',
        'badges',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'mileage_km' => 'integer',
            'min_rental_days' => 'integer',
            'deposit' => 'decimal:2',
            'km_limit_per_day' => 'integer',
            'badges' => 'array',
        ];
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    /** Geçerli rozet anahtarlarıyla kesişim (bilinmeyen anahtarlar elenir). */
    public function badgeLabels(): array
    {
        return array_values(array_intersect_key(self::BADGES, array_flip($this->badges ?? [])));
    }

    public function fuelLabel(): ?string
    {
        return self::FUELS[$this->fuel] ?? null;
    }

    public function transmissionLabel(): ?string
    {
        return self::TRANSMISSIONS[$this->transmission] ?? null;
    }

    public function bodyTypeLabel(): ?string
    {
        return self::BODY_TYPES[$this->body_type] ?? null;
    }
}
