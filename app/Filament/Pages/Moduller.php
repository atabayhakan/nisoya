<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\RestrictsToAdmins;
use App\Support\Modules;
use App\Support\Settings;
use BackedEnum;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * Modüller (Faz 2 · G4) — dikey modülleri (emlak/vasıta/davetiye/iş ilanları)
 * tek tıkla aç/kapa. Kapalı modülün public sayfaları 404 döner, yeni içerik
 * oluşturulamaz, footer/sitemap girişleri gizlenir (bkz. App\Support\Modules).
 *
 * Menü DB-tabanlı (NavigationLinks) olduğundan kapalı modülün menü linkini
 * sahip ayrıca Menü sayfasından kaldırır — burada hatırlatılır.
 */
class Moduller extends Page
{
    use RestrictsToAdmins;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static string|UnitEnum|null $navigationGroup = 'Sistem & Araçlar';

    protected static ?string $navigationLabel = 'Modüller';

    protected static ?int $navigationSort = 6;

    protected string $view = 'filament.pages.moduller';

    public ?array $data = [];

    public function getTitle(): string
    {
        return 'Modüller';
    }

    public function mount(): void
    {
        $state = [];
        foreach (Modules::KEYS as $key) {
            $state[$key] = Modules::enabled($key);
        }

        $this->form->fill($state);
    }

    public function form(Schema $schema): Schema
    {
        $toggles = [];
        foreach (Modules::KEYS as $key) {
            $toggles[] = Toggle::make($key)
                ->label(Modules::LABELS[$key])
                ->helperText('Kapatınca ilgili sayfalar ziyaretçilere "sayfa bulunamadı" (404) gösterir ve yeni içerik eklenemez.');
        }

        return $schema
            ->components([
                Section::make('Dikey modüller')
                    ->description('Kapattığın modül sitede görünmez olur; verilerin silinmez (tekrar açınca geri gelir). Menü bağlantısını ayrıca "Menü" sayfasından kaldırmayı unutma.')
                    ->columns(2)
                    ->schema($toggles),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();

        $values = [];
        foreach (Modules::KEYS as $key) {
            $values["modul.{$key}"] = ! empty($state[$key]) ? '1' : '0';
        }

        Settings::setMany($values);

        Notification::make()
            ->title('Modüller güncellendi')
            ->body('Değişiklik canlı sitede anında geçerli.')
            ->success()
            ->send();
    }
}
