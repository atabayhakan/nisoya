<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\RestrictsToAdmins;
use App\Support\Settings;
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

    public string $aktifMod = 'eski'; // eski, yeni, obsidian, nordic

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
        $this->aktifMod = Settings::get('gorunum.tasarim_modu', 'eski');
        $this->primaryColor = Settings::get('gorunum.primary_color', '#059669');
        $this->fontFamily = Settings::get('gorunum.font_family', 'sans');
        $this->borderRadius = Settings::get('gorunum.border_radius', 'modern');
        $this->glassmorphism = Settings::get('gorunum.glassmorphism', '1') === '1';
        $this->smoothAnimations = Settings::get('gorunum.smooth_animations', '1') === '1';
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
                'gorunum.font_family' => 'inter',
                'gorunum.border_radius' => 'modern',
                'gorunum.glassmorphism' => '1',
                'gorunum.smooth_animations' => '1',
            ],
            'nordic' => [
                'gorunum.tasarim_modu' => 'nordic',
                'gorunum.primary_color' => '#0f172a',
                'gorunum.font_family' => 'outfit',
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
            'yeni' => '2. 2027 Vitrin & Neo-Craft',
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
