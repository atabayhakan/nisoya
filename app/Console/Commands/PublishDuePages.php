<?php

namespace App\Console\Commands;

use App\Enums\PageStatus;
use App\Models\Page;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * Zamanı gelen (ileri tarihli) sayfaların footer menü önbelleğini tazeler
 * (Faz 4 · zamanlanmış yayın).
 *
 * Sayfanın KENDİSİ zaten publish_at gelince otomatik görünür (PageController
 * doğrudan sorgu + scopePublished). Yalnızca footer menüsü "sonsuza" cache'li
 * (NAV_CACHE_KEY, yalnızca kayıt/silmede temizlenir) olduğundan, zamanı yeni
 * gelen bir footer sayfası bu komutla önbelleğe yansıtılır.
 */
class PublishDuePages extends Command
{
    protected $signature = 'content:publish-due';

    protected $description = 'Zamanı gelen ileri tarihli sayfaların footer menü önbelleğini tazeler';

    public function handle(): int
    {
        // Son 20 dk içinde görünür hale gelmiş, footer'da gösterilen bir sayfa
        // var mı? (Pencere > schedule aralığı → gecikmede bile kaçırmaz.)
        $justBecameVisible = Page::query()
            ->where('status', PageStatus::Yayin->value)
            ->where('show_in_footer', true)
            ->whereNotNull('publish_at')
            ->where('publish_at', '<=', now())
            ->where('publish_at', '>', now()->subMinutes(20))
            ->exists();

        if ($justBecameVisible) {
            Cache::forget(Page::NAV_CACHE_KEY);
            $this->info('Footer menü önbelleği tazelendi (zamanı gelen sayfa).');
        }

        return self::SUCCESS;
    }
}
