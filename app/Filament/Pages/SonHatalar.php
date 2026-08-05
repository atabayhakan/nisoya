<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\RestrictsToAdmins;
use App\Support\HataKayitlari;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * Son Hatalar — sunucuya girmeden hata görmek.
 *
 * ---------------------------------------------------------------------------
 * NEDEN VAR
 *
 * 2026-08-05'te El Kitabı canlıda 500 verdi ve sebebi bulmak için sahibe ÜÇ
 * KEZ sunucuda komut çalıştırttım (Claude'un SSH erişimi yok). Hatanın kendisi
 * tek satırlıktı; onu GÖRMEK yarım saat aldı.
 *
 * Ayrıca hata sayfasında "kaydedildi" yazıyor — bu ekran o cümlenin arkasını
 * doldurur: kayıt gerçekten tutuluyorsa buradan görünür, tutulmuyorsa ekran
 * bunu açıkça söyler.
 *
 * Yalnızca Admin (RestrictsToAdmins): log satırları kullanıcı verisi
 * içerebilir.
 */
class SonHatalar extends Page
{
    use RestrictsToAdmins;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedExclamationTriangle;

    protected static string|UnitEnum|null $navigationGroup = 'Sistem & Araçlar';

    protected static ?string $navigationLabel = 'Son Hatalar';

    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.pages.son-hatalar';

    public function getTitle(): string
    {
        return 'Son Hatalar';
    }

    /**
     * Kayıt tutuluyor mu?
     *
     * `false`, "hata yok" DEĞİL "kayıt tutulmuyor olabilir" demektir — ikisi
     * apayrı ve karıştırmak olmayan bir güvence verir.
     */
    public function kayitTutuluyorMu(): bool
    {
        return $this->kayitlar()->kayitTutuluyorMu();
    }

    /**
     * @return list<array{zaman: string, seviye: string, sinif: string, mesaj: string, yer: string|null}>
     */
    public function hatalar(): array
    {
        return $this->kayitlar()->sonHatalar();
    }

    /**
     * Okunan log dosyalarının adları (nerede aradığımızı göstermek için).
     *
     * @return list<string>
     */
    public function dosyaAdlari(): array
    {
        return array_map('basename', $this->kayitlar()->dosyalar());
    }

    private function kayitlar(): HataKayitlari
    {
        return app(HataKayitlari::class);
    }
}
