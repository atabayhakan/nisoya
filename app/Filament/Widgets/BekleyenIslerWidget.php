<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\ContactMessages\ContactMessageResource;
use App\Filament\Resources\FeatureRequests\FeatureRequestResource;
use App\Filament\Resources\JobFeatureRequests\JobFeatureRequestResource;
use App\Filament\Resources\Reports\ReportResource;
use App\Filament\Resources\Stories\StoryResource;
use App\Models\ContactMessage;
use App\Models\FeatureRequest;
use App\Models\JobFeatureRequest;
use App\Models\Report;
use App\Models\Story;
use Filament\Widgets\Widget;

/**
 * "Bugün ne yapmam gerekiyor?" kartı (Vitrin Faz P4b).
 *
 * Handoff'un 07 panosunda bir "doğrulama kuyruğu" vardı ama bu depoda
 * doğrulama kuyruğu diye bir şey YOK (users.is_verified düz bir boolean,
 * bekleyen kayıt üretmiyor) — o yüzden kurgusal kuyruk yerine GERÇEKTEN
 * bekleyen işler gösterilir: sahibin sabah açtığında aksiyon alacağı liste.
 *
 * Sıfır olan kuyruk satırı hiç basılmaz; hepsi sıfırsa kart "her şey temiz"
 * durumuna düşer.
 */
class BekleyenIslerWidget extends Widget
{
    protected string $view = 'filament.widgets.bekleyen-isler';

    protected static ?int $sort = -4;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    /**
     * @return array<int,array{etiket: string, adet: int, url: string, renk: string, ikon: string}>
     */
    public function getKuyruklar(): array
    {
        $ham = [
            [
                'etiket' => 'Yeni iletişim mesajı',
                'adet' => ContactMessage::query()->where('status', 'yeni')->count(),
                'url' => ContactMessageResource::getUrl(),
                'renk' => 'danger',
                'ikon' => 'heroicon-o-envelope',
            ],
            [
                'etiket' => 'Açık şikayet',
                'adet' => Report::query()->where('status', 'acik')->count(),
                'url' => ReportResource::getUrl(),
                'renk' => 'danger',
                'ikon' => 'heroicon-o-flag',
            ],
            [
                'etiket' => 'Onay bekleyen anı',
                'adet' => Story::query()->where('status', 'beklemede')->count(),
                'url' => StoryResource::getUrl(),
                'renk' => 'warning',
                'ikon' => 'heroicon-o-photo',
            ],
            [
                'etiket' => 'Öne çıkarma talebi',
                'adet' => FeatureRequest::query()->where('status', 'beklemede')->count(),
                'url' => FeatureRequestResource::getUrl(),
                'renk' => 'warning',
                'ikon' => 'heroicon-o-star',
            ],
            [
                'etiket' => 'İş ilanı öne çıkarma talebi',
                'adet' => JobFeatureRequest::query()->where('status', 'beklemede')->count(),
                'url' => JobFeatureRequestResource::getUrl(),
                'renk' => 'warning',
                'ikon' => 'heroicon-o-briefcase',
            ],
        ];

        // Sıfır olan kuyruk gösterilmez — pano "yapılacak iş" listesidir,
        // boş satır gürültüdür.
        return array_values(array_filter($ham, fn (array $k) => $k['adet'] > 0));
    }

    public function getSelamlama(): string
    {
        $saat = (int) now()->format('H');
        $ad = (string) auth()->user()?->name;
        $ilkAd = trim(explode(' ', $ad)[0] ?? '');

        $selam = match (true) {
            $saat < 6 => 'İyi geceler',
            $saat < 12 => 'Günaydın',
            $saat < 18 => 'İyi günler',
            default => 'İyi akşamlar',
        };

        return $ilkAd !== '' ? "{$selam}, {$ilkAd}" : $selam;
    }
}
