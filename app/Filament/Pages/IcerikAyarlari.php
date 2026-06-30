<?php

namespace App\Filament\Pages;

use App\Support\Settings;
use BackedEnum;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class IcerikAyarlari extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = 'Site Yönetimi';

    protected static ?string $navigationLabel = 'İçerik (Metinler)';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.icerik-ayarlari';

    public ?array $data = [];

    public function getTitle(): string
    {
        return 'İçerik (Metinler)';
    }

    public function mount(): void
    {
        $values = [];

        foreach (array_keys(config('site_defaults.fields')) as $key) {
            $values[static::toField($key)] = Settings::get($key);
        }

        $this->form->fill($values);
    }

    public function form(Schema $schema): Schema
    {
        $groups = config('site_defaults.groups');
        $fields = config('site_defaults.fields');

        $sections = [];

        foreach ($groups as $groupKey => $groupLabel) {
            $components = [];

            foreach ($fields as $key => $meta) {
                if (($meta['group'] ?? 'genel') !== $groupKey) {
                    continue;
                }

                $name = static::toField($key);

                $component = ($meta['type'] ?? 'text') === 'textarea'
                    ? Textarea::make($name)->rows(3)
                    : TextInput::make($name);

                $components[] = $component
                    ->label($meta['label'] ?? $key)
                    ->maxLength(2000);
            }

            if ($components !== []) {
                $sections[] = Section::make($groupLabel)
                    ->columns(2)
                    ->schema($components)
                    ->collapsible();
            }
        }

        return $schema->components($sections)->statePath('data');
    }

    public function save(): void
    {
        $values = [];

        foreach ($this->form->getState() as $field => $value) {
            $values[static::fromField($field)] = $value;
        }

        Settings::setMany($values);

        Notification::make()
            ->title('İçerik kaydedildi')
            ->success()
            ->send();
    }

    /** Ayar anahtarındaki noktayı alan adı için güvenli ayraçla değiştirir. */
    protected static function toField(string $key): string
    {
        return str_replace('.', '__', $key);
    }

    /** Alan adını tekrar ayar anahtarına çevirir. */
    protected static function fromField(string $field): string
    {
        return str_replace('__', '.', $field);
    }
}
