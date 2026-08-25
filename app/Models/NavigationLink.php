<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Header'daki gezinme linklerini (İlanlar, İş İlanları vb.) admin panelinden
 * ekleyip/çıkarıp sıralayabilmeyi sağlar. Zone'un aksine (bkz. App\Models\Zone)
 * sabit bir koda bağlı değildir — admin serbestçe yeni link ekleyebilir/silebilir.
 */
class NavigationLink extends Model
{
    public const CACHE_KEY = 'navigation_links_active';

    /** Bu grup anahtarına sahip linkler header'da "Keşfet" mega menüsünde kart olarak gösterilir. */
    public const GROUP_KESFET = 'kesfet';

    /**
     * `url` alanına yazılabilecek özel bir DEĞER (gerçek bir rota DEĞİL) —
     * admin bu linki oluştururken sabit bir ülkeye (ör. `/de`) değil,
     * ziyaretçinin KENDİ ülkesine gitmesini istiyorsa bunu yazar.
     *
     * Gerçek olay (2026-08-25, canlıda ölçüldü): "Konsolosluk Rehberi" kartı
     * elle `/de` olarak eklenmişti — Kırgızistan'daki bir ziyaretçi bile
     * Almanya'ya düşüyordu. `navigation_links` tablosu `Cache::rememberForever`
     * ile TEK, paylaşılan bir listede tutulduğu için (bkz. activeCached())
     * ziyaretçiye özel bir URL doğrudan CACHE'E yazılamaz; bu sentinel,
     * çözümlemenin cache'İN DIŞINDA, istek başına (bkz.
     * AppServiceProvider'daki View Composer) yapılmasını sağlar — footer
     * linkinin zaten kullandığı `RehberYuzeyi::girisNoktasiUlkeKodu()` ile
     * BİREBİR aynı K1 önceliği (üye ikameti > GeoIP).
     */
    public const REHBER_GIRIS_SENTINEL = '/rehber-giris';

    protected $fillable = [
        'label',
        'url',
        'group_key',
        'icon',
        'description',
        'sort_order',
        'is_active',
        'opens_new_tab',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'opens_new_tab' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(self::CACHE_KEY));
        static::deleted(fn () => Cache::forget(self::CACHE_KEY));
    }

    /**
     * Eloquent modelleri doğrudan cache'lenirse Laravel 13'te ikinci okumada
     * __PHP_Incomplete_Class'a döner (serializable_classes => false, bkz.
     * App\Models\Zone::allByKey()). Bu yüzden ham attribute dizileri
     * cache'lenir, model burada rehydrate edilir.
     *
     * @return Collection<int, self>
     */
    public static function activeCached(): Collection
    {
        $rows = Cache::rememberForever(self::CACHE_KEY, fn () => self::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map->getAttributes()
            ->all());

        return collect($rows)->map(function (array $attributes) {
            $link = new self;
            $link->exists = true;
            $link->setRawAttributes($attributes, true);

            return $link;
        });
    }
}
