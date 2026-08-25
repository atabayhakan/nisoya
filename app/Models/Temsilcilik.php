<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * Bir ülkedeki Türk dış temsilciliği (büyükelçilik/başkonsolosluk).
 *
 * URL'deki ikinci segment bu modelin slug'ıdır: /de/koeln → DE + "koeln".
 * Şehirle karıştırılmamalı — Köln Başkonsolosluğu bir kurumdur ve görev
 * bölgesi şehirden geniştir (tasarım K2).
 */
class Temsilcilik extends Model
{
    public const TUR_BUYUKELCILIK = 'buyukelcilik';

    public const TUR_BASKONSOLOSLUK = 'baskonsolosluk';

    protected $table = 'temsilcilikler';

    protected $fillable = [
        'country_code', 'ad', 'slug', 'tur', 'sehir', 'adres',
        'latitude', 'longitude', 'resmi_url', 'yonlendirme_notu', 'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    /** @return BelongsTo<Country, $this> */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_code', 'code');
    }

    /** @return HasMany<TemsilcilikIslemi, $this> */
    public function islemler(): HasMany
    {
        return $this->hasMany(TemsilcilikIslemi::class, 'temsilcilik_id');
    }

    /** @param Builder<Temsilcilik> $query */
    public function scopeAktif(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function turEtiketi(): string
    {
        return $this->tur === self::TUR_BUYUKELCILIK ? 'Büyükelçilik' : 'Başkonsolosluk';
    }

    /**
     * Google'dan daha güncel/yaygın bilinen YEREL harita uygulaması —
     * yalnız bunun GERÇEKTEN doğru olduğu bilinen ülkelerde (2026-08-25
     * doğrulandı: 2gis.{az,kz,kg,uz,ru} çalışıyor, koordinat sırası
     * BOYLAM,ENLEM). Listelenmeyen her ülkede yalnız Google Haritalar
     * yeterli kabul edildi — "her ülkeye bir yerel alternatif" iddiası
     * doğrulanmadan eklenmedi (TM: 2gis.tm alan adı bile yok).
     *
     * @var array<string, array{ad: string, domain: string}>
     */
    private const YEREL_HARITA_UYGULAMALARI = [
        'AZ' => ['ad' => '2GIS', 'domain' => '2gis.az'],
        'KZ' => ['ad' => '2GIS', 'domain' => '2gis.kz'],
        'KG' => ['ad' => '2GIS', 'domain' => '2gis.kg'],
        'UZ' => ['ad' => '2GIS', 'domain' => '2gis.uz'],
        'RU' => ['ad' => '2GIS', 'domain' => '2gis.ru'],
    ];

    /**
     * Google Haritalar (koordinat varsa her zaman) + ülkede doğrulanmış bir
     * yerel alternatif varsa o da. Koordinat yoksa BOŞ döner — kırık bir
     * "haritada aç" sözü vermek yerine düğme hiç basılmaz (bkz. çağıran
     * taraftaki `@if` kontrolü).
     *
     * @return Collection<int, array{ad: string, url: string}>
     */
    public function haritaBaglantilari(): Collection
    {
        if ($this->latitude === null || $this->longitude === null) {
            return collect();
        }

        $lat = (float) $this->latitude;
        $lng = (float) $this->longitude;

        $baglantilar = collect([
            ['ad' => 'Google Haritalar', 'url' => "https://maps.google.com/?q={$lat},{$lng}"],
        ]);

        $yerel = self::YEREL_HARITA_UYGULAMALARI[$this->country_code] ?? null;

        if ($yerel !== null) {
            // 2GIS boylam,enlem sırası bekliyor — Google'ın (enlem,boylam)
            // TERSİ. Karıştırılırsa pin yanlış kıtaya düşer.
            $baglantilar->push(['ad' => $yerel['ad'], 'url' => "https://{$yerel['domain']}/geo/{$lng},{$lat}"]);
        }

        return $baglantilar;
    }
}
