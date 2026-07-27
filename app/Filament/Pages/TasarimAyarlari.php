<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\RestrictsToAdmins;
use App\Support\Settings;
use App\Support\Tema;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * 2027 Ultra Tasarım Komuta Merkezi — 4 imza preset, canlı simülatör,
 * tipografi engine, köşe yuvarlatma ve cam efekti (Glassmorphism) kontrolleri.
 */
class TasarimAyarlari extends Page
{
    use RestrictsToAdmins;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSwatch;

    protected static string|UnitEnum|null $navigationGroup = 'İçerik & Tasarım (CMS)';

    protected static ?string $navigationLabel = 'Tasarım Modu';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.tasarim-ayarlari';

    public string $aktifTema = 'klasik'; // klasik, vitrin — view ağacını seçer (bkz. App\Support\Tema)

    public string $aktifMod = 'eski'; // eski, yeni, obsidian, nordic — yalnız klasik temada hükümlü

    public string $primaryColor = '#059669';

    public string $fontFamily = 'sans'; // sans, serif, inter, outfit

    public string $borderRadius = 'modern'; // sharp, soft, modern, pill

    public bool $glassmorphism = true;

    public bool $smoothAnimations = true;

    public function getTitle(): string
    {
        return '2027 Tasarım Komuta Merkezi';
    }

    public function mount(): void
    {
        $this->hydrateFromSettings();
    }

    /** Bileşen durumunu kalıcı ayarlardan (tek doğruluk kaynağı) yükler. */
    private function hydrateFromSettings(): void
    {
        $this->aktifTema = Settings::get('gorunum.tema', 'klasik');
        $this->aktifMod = Settings::get('gorunum.tasarim_modu', 'eski');
        $this->primaryColor = Settings::get('gorunum.primary_color', '#059669');
        $this->fontFamily = Settings::get('gorunum.font_family', 'sans');
        $this->borderRadius = Settings::get('gorunum.border_radius', 'modern');
        $this->glassmorphism = Settings::get('gorunum.glassmorphism', '1') === '1';
        $this->smoothAnimations = Settings::get('gorunum.smooth_animations', '1') === '1';
    }

    /**
     * Tema geçişi (Vitrin projesi): view ağacını değiştirir, deploy gerekmez.
     * Geri dönüş de tek tık — klasik dosyalara hiç dokunulmadığı için
     * `klasik` seçildiğinde site birebir eski haline döner. tasarim_modu
     * preset'leri yalnız klasik temada hükümlüdür (App\Support\Tema).
     */
    public function secTema(string $tema): void
    {
        if (! in_array($tema, Tema::TEMALAR, true)) {
            return;
        }

        Settings::setMany(['gorunum.tema' => $tema]);

        // Adminin kendi bayat ?tema_onizleme oturum bayrağı kalıcı ayarın
        // ÖNÜNE geçer (Tema::aktif sırası) — temizlenmezse bildirim "klasiğe
        // dönüldü" derken admin hâlâ önizlemedeki temayı görürdü (P0 inceleme
        // bulgusu #2). Panel ve site aynı oturumu paylaşır.
        session()->forget('tema_onizleme');

        $this->hydrateFromSettings();

        Notification::make()
            ->title($tema === 'vitrin' ? 'Vitrin teması etkinleştirildi' : 'Klasik temaya dönüldü')
            ->body($tema === 'vitrin'
                ? 'Vitrin karşılığı henüz hazırlanmamış sayfalar klasik görünümle sunulmaya devam eder.'
                : 'Site birebir klasik görünümüne döndü; Tasarım Modu preset\'leri yeniden hükümlü.')
            ->success()
            ->send();
    }

    public function secPreset(string $preset): void
    {
        $presets = [
            'eski' => [
                'gorunum.tasarim_modu' => 'eski',
                'gorunum.primary_color' => '#059669',
                'gorunum.font_family' => 'sans',
                'gorunum.border_radius' => 'soft',
                'gorunum.glassmorphism' => '0',
                'gorunum.smooth_animations' => '1',
            ],
            'yeni' => [
                'gorunum.tasarim_modu' => 'yeni',
                'gorunum.primary_color' => '#0f5c42',
                'gorunum.font_family' => 'serif',
                'gorunum.border_radius' => 'modern',
                'gorunum.glassmorphism' => '1',
                'gorunum.smooth_animations' => '1',
            ],
            'obsidian' => [
                'gorunum.tasarim_modu' => 'obsidian',
                'gorunum.primary_color' => '#10b981',
                // Eskiden 'inter' yazıyordu; o aile hiçbir yerden yüklenmediği
                // için bu hazır ayarı uygulayan sahip sessizce sistem sans'ına
                // düşüyordu. Artık gerçekten yüklü olan aile yazılır.
                'gorunum.font_family' => 'sans',
                'gorunum.border_radius' => 'modern',
                'gorunum.glassmorphism' => '1',
                'gorunum.smooth_animations' => '1',
            ],
            'nordic' => [
                'gorunum.tasarim_modu' => 'nordic',
                'gorunum.primary_color' => '#0f172a',
                // Eskiden 'outfit' yazıyordu — bkz. obsidian'daki not.
                'gorunum.font_family' => 'sans',
                'gorunum.border_radius' => 'pill',
                'gorunum.glassmorphism' => '0',
                'gorunum.smooth_animations' => '1',
            ],
        ];

        if (! isset($presets[$preset])) {
            return;
        }

        Settings::setMany($presets[$preset]);

        $this->hydrateFromSettings();

        $names = [
            'eski' => '1. Zümrüt Klasik',
            // "Vitrin" adı artık tema ekseninde (secTema) yaşıyor — preset'in
            // eski "2027 Vitrin & Neo-Craft" etiketi karışıklık yaratmasın
            // diye yeniden adlandırıldı.
            'yeni' => '2. Neo-Craft 2027',
            'obsidian' => '3. Midnight Obsidian',
            'nordic' => '4. Nordik Minimal',
        ];

        Notification::make()
            ->title("{$names[$preset]} teması etkinleştirildi")
            ->body('Tasarım ayarları canlı sitede anında güncellendi.')
            ->success()
            ->send();
    }

    public function kaydetCustom(): void
    {
        Settings::setMany([
            'gorunum.tasarim_modu' => $this->aktifMod,
            'gorunum.primary_color' => $this->primaryColor,
            'gorunum.font_family' => $this->fontFamily,
            'gorunum.border_radius' => $this->borderRadius,
            'gorunum.glassmorphism' => $this->glassmorphism ? '1' : '0',
            'gorunum.smooth_animations' => $this->smoothAnimations ? '1' : '0',
        ]);

        Notification::make()
            ->title('Özel Tasarım Parametreleri Kaydedildi')
            ->body('Canlı sitedeki stil ve tipografi değişkenleri güncellendi.')
            ->success()
            ->send();
    }

    public function sifirla(): void
    {
        $this->secPreset('eski');
    }
}
