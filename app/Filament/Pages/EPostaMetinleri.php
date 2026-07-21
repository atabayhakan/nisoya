<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\RestrictsToAdmins;
use App\Support\MailTemplates;
use App\Support\Settings;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Arr;
use UnitEnum;

/**
 * E-posta Metinleri (Faz 3 · G6) — en çok görünen maillerin konu/gövde
 * parçalarını panelden düzenle. Form registry'den (MailTemplates::TEMPLATES)
 * otomatik üretilir; boş bırakılan alan varsayılan metni kullanır. Yer-tutucular
 * ({ad}, {gonderen} vb.) gönderim anında gerçek değerle değişir.
 */
class EPostaMetinleri extends Page
{
    use RestrictsToAdmins;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelopeOpen;

    protected static string|UnitEnum|null $navigationGroup = 'Site Yönetimi';

    protected static ?string $navigationLabel = 'E-posta Metinleri';

    protected static ?int $navigationSort = 7;

    protected string $view = 'filament.pages.e-posta-metinleri';

    public ?array $data = [];

    public function getTitle(): string
    {
        return 'E-posta Metinleri';
    }

    public function mount(): void
    {
        $state = [];
        foreach (MailTemplates::TEMPLATES as $key => $tpl) {
            foreach (array_keys(MailTemplates::PART_LABELS) as $part) {
                $state[$key.'__'.$part] = Settings::get("mail_template.{$key}.{$part}") ?? '';
            }
        }

        $this->form->fill($state);
    }

    public function form(Schema $schema): Schema
    {
        $sections = [];

        foreach (MailTemplates::TEMPLATES as $key => $tpl) {
            $fields = [];
            foreach (MailTemplates::PART_LABELS as $part => $partLabel) {
                $fields[] = TextInput::make($key.'__'.$part)
                    ->label($partLabel)
                    ->placeholder($tpl['parts'][$part]) // boşsa bu varsayılan kullanılır
                    ->maxLength(500)
                    ->columnSpanFull();
            }

            $placeholderHint = collect($tpl['placeholders'])
                ->map(fn (string $desc, string $ph): string => $ph.' = '.$desc)
                ->implode(' · ');

            $sections[] = Section::make($tpl['label'])
                ->description('Boş bırakılan alan varsayılan metni kullanır. Yer-tutucular: '.$placeholderHint)
                ->schema($fields)
                ->collapsible();
        }

        return $schema->components($sections)->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();

        $values = [];
        foreach (MailTemplates::TEMPLATES as $key => $tpl) {
            foreach (array_keys(MailTemplates::PART_LABELS) as $part) {
                $values["mail_template.{$key}.{$part}"] = Arr::get($state, $key.'__'.$part, '') ?? '';
            }
        }

        Settings::setMany($values);

        Notification::make()
            ->title('E-posta metinleri kaydedildi')
            ->body('Değişiklik bundan sonra gönderilecek e-postalarda geçerli.')
            ->success()
            ->send();
    }
}
