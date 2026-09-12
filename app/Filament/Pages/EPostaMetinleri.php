<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\RestrictsToAdmins;
use App\Services\Ai\SystemToolsAiAssistant;
use App\Support\MailTemplates;
use App\Support\Settings;
use BackedEnum;
use Filament\Actions\Action;
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

    protected static string|UnitEnum|null $navigationGroup = 'Sistem & Araçlar';

    protected static ?string $navigationLabel = 'E-posta Metinleri';

    protected static ?int $navigationSort = 6;

    protected string $view = 'filament.pages.e-posta-metinleri';

    public ?array $data = [];

    /**
     * @return array<int, Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('aiSablonIpuclari')
                ->label('AI Şablon İpuçları')
                ->icon(Heroicon::OutlinedSparkles)
                ->color('primary')
                ->modalHeading('E-posta Şablonları & Yer-Tutucular')
                ->modalDescription('Yurtdışındaki Türk topluluğuna gönderilen bildirimlerde güven ve açık dil esastır. Metinleri düzenlerken süslü parantez içindeki {ad}, {gonderen}, {arama} gibi yer-tutucuları korumayı unutmayın. İlgili satırların yanındaki "AI İyileştir" butonuna tıklayarak akıllı öneriler alabilirsiniz.')
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Anladım'),
        ];
    }

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
                    ->columnSpanFull()
                    ->hintActions([
                        Action::make('aiOptimize_'.$key.'_'.$part)
                            ->label('AI İyileştir')
                            ->icon(Heroicon::OutlinedSparkles)
                            ->tooltip('Yapay zekâ ile diasporaya uygun profesyonel dille optimize et')
                            ->action(function (callable $get, callable $set) use ($key, $part, $tpl): void {
                                $current = (string) ($get($key.'__'.$part) ?: $tpl['parts'][$part]);
                                $assistant = app(SystemToolsAiAssistant::class);
                                $res = $assistant->optimizeEmailTemplate($key, $part, $current);
                                $set($key.'__'.$part, $res['optimized_text']);
                                Notification::make()
                                    ->title('AI Metni Optimize Etti')
                                    ->body($res['explanation'])
                                    ->success()
                                    ->send();
                            }),
                        Action::make('reset_'.$key.'_'.$part)
                            ->label('Varsayılan')
                            ->icon(Heroicon::OutlinedArrowPath)
                            ->color('gray')
                            ->tooltip('Varsayılan orijinal metne geri döndür')
                            ->action(function (callable $set) use ($key, $part): void {
                                $set($key.'__'.$part, '');
                                Notification::make()
                                    ->title('Varsayılana sıfırlandı')
                                    ->info()
                                    ->send();
                            }),
                    ]);
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
