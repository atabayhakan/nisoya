<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\RestrictsToAdmins;
use App\Support\HomeSections;
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
 * Anasayfa Bölümleri (Faz 2 · G5) — anasayfada hangi içerik bölümünün
 * görüneceğini panelden aç/kapa. Kapatılan bölüm anasayfada hiç render edilmez;
 * içerik/veri silinmez (tekrar açınca geri gelir — bkz. App\Support\HomeSections).
 *
 * Bölüm SIRASI bu sürümde sabit; sürükle-sırala ayrı bir adıma bırakıldı.
 */
class AnasayfaBolumleri extends Page
{
    use RestrictsToAdmins;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWindow;

    protected static string|UnitEnum|null $navigationGroup = 'Site Yönetimi';

    protected static ?string $navigationLabel = 'Anasayfa Bölümleri';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.anasayfa-bolumleri';

    public ?array $data = [];

    public function getTitle(): string
    {
        return 'Anasayfa Bölümleri';
    }

    public function mount(): void
    {
        $state = [];
        foreach (array_keys(HomeSections::SECTIONS) as $key) {
            $state[$key] = HomeSections::visible($key);
        }

        $this->form->fill($state);
    }

    public function form(Schema $schema): Schema
    {
        $toggles = [];
        foreach (HomeSections::SECTIONS as $key => $label) {
            $toggles[] = Toggle::make($key)->label($label);
        }

        return $schema
            ->components([
                Section::make('Görünen bölümler')
                    ->description('Kapattığın bölüm anasayfada görünmez olur; verilerin silinmez. Hero (üst alan), Nisoya Nabzı ve reklam alanları buradan bağımsızdır (kendi ayarları var).')
                    ->columns(2)
                    ->schema($toggles),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();

        $values = [];
        foreach (array_keys(HomeSections::SECTIONS) as $key) {
            $values["home.section.{$key}"] = ! empty($state[$key]) ? '1' : '0';
        }

        Settings::setMany($values);

        Notification::make()
            ->title('Anasayfa bölümleri güncellendi')
            ->body('Değişiklik canlı sitede anında geçerli.')
            ->success()
            ->send();
    }
}
