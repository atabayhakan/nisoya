<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\RestrictsToAdmins;
use App\Services\Ai\CmsAiAssistant;
use App\Support\Settings;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * Duyuru Bandı (Faz 2 · G8) — site üstünde tek satır şerit (kampanya, bakım
 * duyurusu vb.). Panelden aç/kapa + metin + bağlantı + renk. Kapalı veya metin
 * boşsa hiç render edilmez (bkz. components/announcement-bar.blade.php).
 * Değişiklik canlı sitede anında geçerli.
 */
class DuyuruBandi extends Page
{
    use RestrictsToAdmins;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;

    protected static string|UnitEnum|null $navigationGroup = 'İçerik & Tasarım (CMS)';

    protected static ?string $navigationLabel = 'Duyuru Bandı';

    protected static ?int $navigationSort = 4;

    protected string $view = 'filament.pages.duyuru-bandi';

    public ?array $data = [];

    public function getTitle(): string
    {
        return 'Duyuru Bandı';
    }

    public function mount(): void
    {
        $this->form->fill([
            'aktif' => (Settings::get('duyuru.aktif') ?? '0') === '1',
            'metin' => Settings::get('duyuru.metin') ?? '',
            'link' => Settings::get('duyuru.link') ?? '',
            'link_metni' => Settings::get('duyuru.link_metni') ?? '',
            'renk' => Settings::get('duyuru.renk') ?: 'marka',
            'kapatilabilir' => (Settings::get('duyuru.kapatilabilir') ?? '1') === '1',
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('aiDuyuruUret')
                ->label('AI ile Duyuru Oluştur')
                ->icon(Heroicon::OutlinedSparkles)
                ->color('primary')
                ->modalHeading('Yapay Zeka ile Duyuru Metni Oluştur')
                ->modalDescription('Kampanya, tatil indirimi, kargo duyurusu veya acil bildirim için konuyu belirtin.')
                ->form([
                    TextInput::make('konu')
                        ->label('Duyuru Konusu veya Amacı')
                        ->placeholder('Örn: Almanya ve Hollanda için 100€ üzeri ücretsiz kargo')
                        ->required(),
                ])
                ->action(function (array $data, CmsAiAssistant $assistant): void {
                    $sonuc = $assistant->generateAnnouncement($data['konu']);
                    $this->form->fill([
                        ...$this->form->getState(),
                        'aktif' => true,
                        'metin' => $sonuc['metin'] ?? '',
                        'link' => $sonuc['link'] ?? '',
                        'link_metni' => $sonuc['link_metni'] ?? '',
                        'renk' => $sonuc['renk'] ?? 'marka',
                    ]);
                    Notification::make()
                        ->title('Duyuru metni üretildi')
                        ->body('Form alanları dolduruldu. Değişiklikleri inceleyip Kaydet butonuna basabilirsiniz.')
                        ->success()
                        ->send();
                }),
        ];
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Duyuru')
                    ->description('Sitenin en üstünde herkese görünen tek satır şerit. Bakım, kampanya veya önemli bir duyuru için kullan. Kapalıyken veya metin boşken hiç görünmez.')
                    ->columns(2)
                    ->schema([
                        Toggle::make('aktif')
                            ->label('Duyuru bandı açık')
                            ->live()
                            ->columnSpanFull(),

                        Textarea::make('metin')
                            ->label('Duyuru metni')
                            ->rows(2)
                            ->maxLength(300)
                            ->live(onBlur: true)
                            ->placeholder('Örn: 15 Ağustos’ta kısa bir bakım çalışması yapılacaktır.')
                            ->columnSpanFull(),

                        TextInput::make('link')
                            ->label('Bağlantı (opsiyonel)')
                            ->url()
                            ->live(onBlur: true)
                            ->placeholder('https://…'),

                        TextInput::make('link_metni')
                            ->label('Bağlantı metni (opsiyonel)')
                            ->live(onBlur: true)
                            ->placeholder('Detay'),

                        Select::make('renk')
                            ->label('Renk')
                            ->options([
                                'marka' => 'Marka (yeşil)',
                                'uyari' => 'Uyarı (amber)',
                                'onemli' => 'Önemli (kırmızı)',
                            ])
                            ->live()
                            ->native(false),

                        Toggle::make('kapatilabilir')
                            ->label('Ziyaretçi kapatabilsin')
                            ->helperText('Açıksa ziyaretçi × ile kapatır; metni değiştirince tekrar görünür.'),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();

        Settings::setMany([
            'duyuru.aktif' => ! empty($state['aktif']) ? '1' : '0',
            'duyuru.metin' => $state['metin'] ?? '',
            'duyuru.link' => $state['link'] ?? '',
            'duyuru.link_metni' => $state['link_metni'] ?? '',
            'duyuru.renk' => $state['renk'] ?? 'marka',
            'duyuru.kapatilabilir' => ! empty($state['kapatilabilir']) ? '1' : '0',
        ]);

        Notification::make()
            ->title('Duyuru bandı kaydedildi')
            ->body('Değişiklik canlı sitede anında geçerli.')
            ->success()
            ->send();
    }
}
