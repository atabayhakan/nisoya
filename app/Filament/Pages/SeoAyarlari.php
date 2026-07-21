<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\RestrictsToAdmins;
use App\Support\Settings;
use BackedEnum;
use Filament\Forms\Components\FileUpload;
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
 * SEO ayarları (Faz 3 · G7) — arama motoru ve sosyal paylaşım varsayılanları:
 * başlık, açıklama, paylaşım (OG) görseli ve arama-motoru görünürlüğü (noindex).
 * Değerler layout'ta meta etiketlerine yazılır; sayfa kendi başlık/açıklamasını
 * verirse o önceliklidir (bkz. components/layouts/app.blade.php).
 */
class SeoAyarlari extends Page
{
    use RestrictsToAdmins;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMagnifyingGlass;

    protected static string|UnitEnum|null $navigationGroup = 'Site Yönetimi';

    protected static ?string $navigationLabel = 'SEO';

    protected static ?int $navigationSort = 8;

    protected string $view = 'filament.pages.seo-ayarlari';

    public ?array $data = [];

    public function getTitle(): string
    {
        return 'SEO Ayarları';
    }

    public function mount(): void
    {
        $this->form->fill([
            'default_title' => Settings::get('seo.default_title'),
            'default_description' => Settings::get('seo.default_description'),
            'og_image' => Settings::get('seo.og_image') ?: null,
            'robots_index' => (Settings::get('seo.robots_index') ?? '1') === '1',
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Arama motoru & paylaşım')
                    ->description('Sayfaların varsayılan başlık ve açıklaması. İlan/sayfa gibi kendi başlığı olanlar bundan etkilenmez.')
                    ->schema([
                        TextInput::make('default_title')
                            ->label('Varsayılan başlık')
                            ->maxLength(70)
                            ->helperText('Tarayıcı sekmesinde ve arama sonuçlarında görünür. ~60 karakter idealdir.'),

                        Textarea::make('default_description')
                            ->label('Varsayılan açıklama')
                            ->rows(3)
                            ->maxLength(200)
                            ->helperText('Arama sonuçlarında başlığın altındaki metin. ~155 karakter idealdir.'),

                        FileUpload::make('og_image')
                            ->label('Paylaşım görseli (OG)')
                            ->image()
                            ->disk('public')
                            ->directory('seo')
                            ->maxSize(2048)
                            ->imageEditor()
                            ->helperText('WhatsApp/Facebook/X’te link paylaşılınca görünen görsel. 1200×630 önerilir. Boşsa varsayılan og.png kullanılır.'),
                    ]),

                Section::make('Görünürlük')
                    ->schema([
                        Toggle::make('robots_index')
                            ->label('Arama motorlarında görünür')
                            ->helperText('Kapatırsan siteye "noindex" eklenir — Google gibi arama motorları siteyi listelememeye başlar. Site hazır değilken kullan.'),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();

        Settings::setMany([
            'seo.default_title' => $state['default_title'] ?? '',
            'seo.default_description' => $state['default_description'] ?? '',
            'seo.og_image' => $state['og_image'] ?? '',
            'seo.robots_index' => ! empty($state['robots_index']) ? '1' : '0',
        ]);

        Notification::make()
            ->title('SEO ayarları kaydedildi')
            ->body('Değişiklik canlı sitede anında geçerli.')
            ->success()
            ->send();
    }
}
