<?php

namespace App\Filament\Widgets;

use App\Enums\UserRole;
use App\Filament\Resources\ContactMessages\ContactMessageResource;
use App\Filament\Resources\FeatureRequests\FeatureRequestResource;
use App\Filament\Resources\JobFeatureRequests\JobFeatureRequestResource;
use App\Filament\Resources\Reports\ReportResource;
use App\Filament\Resources\Stories\StoryResource;
use App\Filament\Resources\Users\UserResource;
use App\Models\ContactMessage;
use App\Models\FeatureRequest;
use App\Models\JobFeatureRequest;
use App\Models\Report;
use App\Models\Story;
use App\Models\User;
use Filament\Widgets\Widget;

/**
 * "Kim girdi + bugün ne yapmam gerekiyor?" kartı (Vitrin Faz P4b).
 *
 * Handoff'un 07 panosunda bir "doğrulama kuyruğu" vardı ama bu depoda
 * doğrulama kuyruğu diye bir şey YOK (users.is_verified düz bir boolean,
 * bekleyen kayıt üretmiyor) — o yüzden kurgusal kuyruk yerine GERÇEKTEN
 * bekleyen işler gösterilir: sahibin sabah açtığında aksiyon alacağı liste.
 *
 * Sıfır olan kuyruk satırı hiç basılmaz; hepsi sıfırsa kart "her şey temiz"
 * durumuna düşer.
 *
 * ---------------------------------------------------------------------------
 * KİMLİK NEDEN BURAYA TAŞINDI (2026-08-06)
 *
 * Pano iki ayrı "merhaba" gösteriyordu: burada "Günaydın, Hakan", aşağıda
 * vendor'ın `AccountWidget`'i ile "Hoş geldin, Hakan Atabay". İKİSİ DE
 * E-POSTAYI GÖSTERMİYORDU.
 *
 * İkinci yönetici tanımlanana kadar bu yalnız tekrardı; tanımlandıktan sonra
 * gerçek bir soru oldu: **hangi hesapla bakıyorum?** İki yöneticinin adı
 * karışabilir, e-posta karışmaz. Kimlik tek bir yere — göz zaten oraya
 * düştüğü için ilk karta — alındı, `AccountWidget` panodan kaldırıldı.
 *
 * Gösterilen her alan GERÇEK ve o ana ait: ad, e-posta, rol etiketi, 2FA
 * durumu ve tanımlı yönetici sayısı. Hiçbiri sabit yazılmadı.
 */
class BekleyenIslerWidget extends Widget
{
    protected string $view = 'filament.widgets.bekleyen-isler';

    /** Panonun ilk kartı — sıra merdiveni {@see AdminPanelProvider} içinde. */
    protected static ?int $sort = 10;

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

    public function getKullanici(): ?User
    {
        $kullanici = auth()->user();

        return $kullanici instanceof User ? $kullanici : null;
    }

    /**
     * Şu an panele bakan hesabın kimliği.
     *
     * `yoneticiSayisi` YALNIZ ADMİNE verilir ve yalnız birden çoksa: tek
     * yönetici varken "1 yönetici tanımlı" yazmak bilgi değil gürültüdür,
     * bu satırın var olma sebebi "hangi yönetici" sorusunu anlamlı kılmak.
     *
     * @return array{ad: string, eposta: string, rol: string, ikiFaktor: bool, yoneticiSayisi: ?int, yoneticilerUrl: ?string}|null
     */
    public function getKimlik(): ?array
    {
        $kullanici = $this->getKullanici();

        if ($kullanici === null) {
            return null;
        }

        $yoneticiSayisi = null;

        if ($kullanici->isAdmin()) {
            $sayi = User::query()->where('role', UserRole::Admin)->count();
            $yoneticiSayisi = $sayi > 1 ? $sayi : null;
        }

        return [
            'ad' => (string) $kullanici->name,
            'eposta' => (string) $kullanici->email,
            'rol' => $kullanici->role->getLabel(),
            'ikiFaktor' => $kullanici->hasTwoFactorEnabled(),
            'yoneticiSayisi' => $yoneticiSayisi,
            'yoneticilerUrl' => $yoneticiSayisi !== null ? UserResource::getUrl() : null,
        ];
    }
}
