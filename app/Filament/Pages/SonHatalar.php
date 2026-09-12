<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\RestrictsToAdmins;
use App\Services\Ai\SystemToolsAiAssistant;
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

    protected static ?int $navigationSort = 4;

    public static function getNavigationBadge(): ?string
    {
        $count = count(app(HataKayitlari::class)->sonHatalar(25));

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    protected string $view = 'filament.pages.son-hatalar';

    /** @var array{hata: array{zaman: string, seviye: string, sinif: string, mesaj: string, yer: string|null}, teshis: array{severity: string, root_cause: string, impact: string, solution_steps: list<string>, prevention_advice: string}}|null */
    public ?array $seciliTeshis = null;

    public function getTitle(): string
    {
        return 'Son Hatalar';
    }

    public function aiTeshis(int $index): void
    {
        $hatalar = $this->hatalar();
        $hata = $hatalar[$index] ?? null;
        if (! $hata) {
            return;
        }

        $assistant = app(SystemToolsAiAssistant::class);
        $this->seciliTeshis = [
            'hata' => $hata,
            'teshis' => $assistant->diagnoseSystemError(
                $hata['mesaj'],
                $hata['sinif'],
                $hata['yer']
            ),
        ];
    }

    public function teshisKapat(): void
    {
        $this->seciliTeshis = null;
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
