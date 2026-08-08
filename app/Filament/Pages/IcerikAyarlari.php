<?php

namespace App\Filament\Pages;

use App\Filament\Support\MedyaAlani;
use App\Support\Settings;
use BackedEnum;
use Filament\Forms\Components\Select;
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

    protected static string|UnitEnum|null $navigationGroup = 'İçerik & Tasarım (CMS)';

    protected static ?string $navigationLabel = 'İçerik (Metinler)';

    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.pages.icerik-ayarlari';

    public ?array $data = [];

    /** Bu sayfada ham HTML/JS enjeksiyon alanları var (header/footer özel kod,
     *  AdSense/Analytics kodu) — sayfaya olduğu gibi basılır. Yalnızca Admin
     *  erişebilir; moderatör menüde göremez ve doğrudan URL ile açamaz. */
    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

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
                $type = $meta['type'] ?? 'text';

                $component = match ($type) {
                    'code' => Textarea::make($name)
                        ->rows(8)
                        ->maxLength(20000)
                        ->extraInputAttributes(['class' => 'font-mono text-xs', 'spellcheck' => 'false'])
                        ->helperText('⚠️ Buraya yapıştırılan kod sayfaya olduğu gibi eklenir (HTML/JavaScript). Yalnızca güvendiğin kaynaklardan (ör. Google AdSense/Analytics panelinden) kopyaladığın kodu ekle.')
                        ->columnSpanFull(),
                    'textarea' => Textarea::make($name)->rows(3)->maxLength(2000),
                    'select' => Select::make($name)->options($meta['options'] ?? []),
                    // Boru hattına bağlı. `marka_logo` slotu `sigdir` kipinde ve
                    // saydamlığı korur — logo kırpılırsa marka bozulur, koyu
                    // temada beyaz kutu içinde kalırsa da öyle.
                    'image' => MedyaAlani::make($name, 'marka_logo'),
                    default => TextInput::make($name)->maxLength(2000),
                };

                $components[] = $component->label($meta['label'] ?? $key);
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
