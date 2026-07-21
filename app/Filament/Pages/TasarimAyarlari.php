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
 * Site genelinde "1. Tasarım" (mevcut) ile "2. Tasarım" (2027 vizyon
 * pilotu — bkz. Nisoya 2027 Tasarım Vizyonu) arasında anında geçiş.
 * Mekanizma marka rengi seçicisiyle aynı desen: bir ayar (gorunum.tasarim_modu)
 * okunup components/tasarim-theme.blade.php'de CSS değişkeni override'ına
 * dönüşüyor — hiçbir Blade dosyasındaki class'a dokunmadan tüm site değişir.
 */
class TasarimAyarlari extends Page
{
    use RestrictsToAdmins;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSwatch;

    protected static string|UnitEnum|null $navigationGroup = 'İçerik & Tasarım (CMS)';

    protected static ?string $navigationLabel = 'Tasarım Modu';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.tasarim-ayarlari';

    public string $aktifMod = 'eski';

    public function getTitle(): string
    {
        return 'Tasarım Modu';
    }

    public function mount(): void
    {
        $this->aktifMod = Settings::get('gorunum.tasarim_modu', 'eski');
    }

    public function secModu(string $mod): void
    {
        if (! in_array($mod, ['eski', 'yeni'], true)) {
            return;
        }

        Settings::setMany(['gorunum.tasarim_modu' => $mod]);
        $this->aktifMod = $mod;

        Notification::make()
            ->title($mod === 'yeni' ? '2. Tasarım (Yeni) etkinleştirildi' : '1. Tasarım (Eski) etkinleştirildi')
            ->body('Değişiklik canlı sitede anında görünür.')
            ->success()
            ->send();
    }
}
