<?php

namespace App\Models;

use App\Enums\PageStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * @property Carbon|null $publish_at datetime cast (bkz. casts())
 */
class Page extends Model
{
    public const NAV_CACHE_KEY = 'nav_pages';

    /** Sayfa slug'ı olarak kullanılamayacak (mevcut rotalarla çakışan) kelimeler. */
    public const RESERVED_SLUGS = [
        'ilanlar', 'ilan', 'harita', 'uye', 'nasil-calisir', 'iletisim',
        'giris', 'kayit', 'cikis', 'sifremi-unuttum', 'sifre-sifirla', 'eposta-dogrula',
        'panel', 'yonetim', 'favori', 'sitemap.xml', 'robots.txt', 'offline',
        'cerez-politikasi', 'cerez-tercihleri', 'kullanim-kosullari',
    ];

    protected $fillable = [
        'title',
        'slug',
        'blocks',
        'status',
        'publish_at',
        'meta_description',
        'show_in_footer',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'blocks' => 'array',
            'status' => PageStatus::class,
            'publish_at' => 'datetime',
            'show_in_footer' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        // Footer/menü listesi değişince önbelleği temizle.
        static::saved(fn () => Cache::forget(self::NAV_CACHE_KEY));
        static::deleted(fn () => Cache::forget(self::NAV_CACHE_KEY));
    }

    /**
     * Ziyaretçilere görünen sayfalar: durumu "Yayında" VE (ileri tarih yoksa
     * veya zamanı gelmişse). Böylece zamanlanmış sayfalar publish_at gelene
     * kadar gizli kalır (Faz 4 · zamanlanmış yayın).
     *
     * @param  Builder<Page>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', PageStatus::Yayin->value)
            ->where(function (Builder $q) {
                $q->whereNull('publish_at')->orWhere('publish_at', '<=', now());
            });
    }

    /**
     * Yayına alınmış ama zamanı henüz gelmemiş (ileri tarihli) sayfalar.
     *
     * @param  Builder<Page>  $query
     */
    public function scopeScheduled(Builder $query): void
    {
        $query->where('status', PageStatus::Yayin->value)
            ->whereNotNull('publish_at')
            ->where('publish_at', '>', now());
    }

    /** Bu sayfa ileri tarihli yayına mı ayarlı (henüz görünmüyor)? */
    public function isScheduled(): bool
    {
        return $this->status === PageStatus::Yayin
            && $this->publish_at !== null
            && $this->publish_at->isFuture();
    }

    /**
     * Footer menüsünde gösterilecek yayındaki sayfalar (cache'li).
     * Not: Eloquent modeli değil, düz dizi cache'lenir (serileştirme güvenliği);
     * blade'de $sayfa->slug / ->title erişimi için nesneye çevrilir.
     *
     * @return Collection<int, object>
     */
    public static function navPages(): Collection
    {
        $items = Cache::rememberForever(self::NAV_CACHE_KEY, fn () => static::query()
            ->published()
            ->where('show_in_footer', true)
            ->orderBy('sort_order')
            ->get(['title', 'slug'])
            ->map(fn (Page $page) => ['title' => $page->title, 'slug' => $page->slug])
            ->all());

        return collect($items)->map(fn (array $item) => (object) $item);
    }
}
