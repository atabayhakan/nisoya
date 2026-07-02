<?php

namespace App\Models;

use App\Enums\PageStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

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
        'meta_description',
        'show_in_footer',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'blocks' => 'array',
            'status' => PageStatus::class,
            'show_in_footer' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        // Footer/menü listesi değişince önbelleği temizle.
        static::saved(fn () => Cache::forget(self::NAV_CACHE_KEY));
        static::deleted(fn () => Cache::forget(self::NAV_CACHE_KEY));
    }

    /** @param  Builder<Page>  $query */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', PageStatus::Yayin->value);
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
