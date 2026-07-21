<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\RestrictsToAdmins;
use App\Filament\Resources\NavigationLinks\NavigationLinkResource;
use App\Filament\Resources\Pages\PageResource;
use App\Filament\Resources\Zones\ZoneResource;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * El Kitabı (Faz 1 · G10) — panelin "kendini anlatan" rehberi.
 *
 * Amaç: geliştirici olmadan da sahibin "şunu değiştirmek için nereye gideceğini"
 * tek bakışta bulması. Sık ihtiyaçları ilgili panel sayfasına bağlar ve "sen
 * yokken" acil durum adımlarını (yedekten dön, erişimi kurtar) özetler.
 */
class ElKitabi extends Page
{
    use RestrictsToAdmins;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static string|UnitEnum|null $navigationGroup = 'Sistem';

    protected static ?string $navigationLabel = 'El Kitabı';

    protected static ?int $navigationSort = 0; // Sistem grubunun en üstünde

    protected string $view = 'filament.pages.el-kitabi';

    public function getTitle(): string
    {
        return 'El Kitabı';
    }

    /**
     * Rehber bölümleri: her kart bir panel sayfasına bağlanır.
     *
     * @return array<int,array{baslik:string,kartlar:array<int,array{baslik:string,aciklama:string,url:string,ikon:string}>}>
     */
    public function sections(): array
    {
        return [
            [
                'baslik' => 'Görünüm ve içerik',
                'kartlar' => [
                    ['baslik' => 'İçerik ve metinler', 'aciklama' => 'Anasayfa, hero, footer, iletişim ve bağış metinlerini düzenle.', 'url' => IcerikAyarlari::getUrl(), 'ikon' => 'heroicon-o-document-text'],
                    ['baslik' => 'Tasarım', 'aciklama' => 'Logo, favicon, marka rengi ve tasarım modu.', 'url' => TasarimAyarlari::getUrl(), 'ikon' => 'heroicon-o-swatch'],
                    ['baslik' => 'Menü', 'aciklama' => 'Üst menüye bağlantı ekle, çıkar, sırala.', 'url' => NavigationLinkResource::getUrl(), 'ikon' => 'heroicon-o-bars-3'],
                    ['baslik' => 'Sayfalar', 'aciklama' => 'Yeni sayfa oluştur (KVKK, hakkımızda, kampanya).', 'url' => PageResource::getUrl(), 'ikon' => 'heroicon-o-document-plus'],
                    ['baslik' => 'Reklam alanları', 'aciklama' => 'Sitedeki reklam/içerik yerleşimlerini yönet.', 'url' => ZoneResource::getUrl(), 'ikon' => 'heroicon-o-rectangle-group'],
                ],
            ],
            [
                'baslik' => 'Entegrasyonlar',
                'kartlar' => [
                    ['baslik' => 'Yapay zeka', 'aciklama' => 'AI aç/kapa, sağlayıcı ve anahtar. Sağlayıcı çökerse tek düğmeyle kapat.', 'url' => YapayZekaAyarlari::getUrl(), 'ikon' => 'heroicon-o-sparkles'],
                    ['baslik' => 'E-posta (SMTP)', 'aciklama' => 'E-posta sağlayıcısını değiştir ve test e-postası gönder.', 'url' => MailAyarlari::getUrl(), 'ikon' => 'heroicon-o-envelope'],
                ],
            ],
            [
                'baslik' => 'Güvenlik ağı — sen yokken',
                'kartlar' => [
                    ['baslik' => 'Yedekleme', 'aciklama' => 'Tam yedek al / indir. Günlük otomatik yedek açık.', 'url' => Yedekleme::getUrl(), 'ikon' => 'heroicon-o-circle-stack'],
                    ['baslik' => 'Kurtarma Kiti', 'aciklama' => 'Kurtarma kodları + ikinci yönetici ile panele kilitlenme.', 'url' => KurtarmaKiti::getUrl(), 'ikon' => 'heroicon-o-lifebuoy'],
                ],
            ],
        ];
    }

    /**
     * "Sen yokken" acil durum adımları (bağlantı değil, yönerge).
     *
     * @return array<int,array{baslik:string,adim:string}>
     */
    public function emergency(): array
    {
        return [
            ['baslik' => 'Parolamı unuttum, e-posta da çalışmıyor', 'adim' => 'Kurtarma Kiti’nden önceden oluşturduğun bir kurtarma koduyla /hesap-kurtar sayfasından parolanı e-postasız sıfırla.'],
            ['baslik' => 'Panele hiç giremiyorum (parola + 2FA + kod kayıp)', 'adim' => 'Sunucuya erişimin varsa son çare: php artisan admin:recover eposta@ornek.com — parolayı sıfırlar, hesabı Yönetici + Aktif yapar.'],
            ['baslik' => 'Sunucuda bir şey bozuldu, siteyi geri getirmem lazım', 'adim' => 'Yedekleme sayfasından en güncel yedeği indir; içindeki veritabanı dökümünü ve media/ klasörünü sunucuya geri yükle (adımlar Yedekleme sayfasında yazılı).'],
        ];
    }
}
