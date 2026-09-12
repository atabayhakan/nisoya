<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Concerns\RestrictsToAdmins;
use App\Filament\Support\MedyaAlani;
use App\Models\Category;
use App\Models\Country;
use App\Models\Listing;
use App\Services\Ai\GrowthMarketingAiAssistant;
use App\Support\Settings;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Cache;
use UnitEnum;

/**
 * SEO & GEO Ayarları (Eylül 2026 Standartları).
 *
 * Klasik arama motorları (Google, Bing) ve yapay zekâ arama botları (Claude Search,
 * SearchGPT, Perplexity, Gemini Overviews) için başlık, meta açıklama, sosyal paylaşım (OG),
 * indeksleme durumu ve standart /llms.txt yapılandırmasını yönetir.
 */
class SeoAyarlari extends Page
{
    use RestrictsToAdmins;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMagnifyingGlass;

    protected static string|UnitEnum|null $navigationGroup = 'Pazarlama & Büyüme';

    protected static ?string $navigationLabel = 'SEO';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.seo-ayarlari';

    public ?array $data = [];

    public function getTitle(): string
    {
        return 'SEO & Generative Engine Optimization (GEO) Ayarları';
    }

    public static function getNavigationBadge(): ?string
    {
        $robotsIndex = (Settings::get('seo.robots_index') ?? '1') === '1';

        return $robotsIndex ? 'Açık' : 'noindex';
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $robotsIndex = (Settings::get('seo.robots_index') ?? '1') === '1';

        return $robotsIndex ? 'success' : 'danger';
    }

    /**
     * @return array<int, Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('llmsTxtGoruntule')
                ->label('/llms.txt İncele')
                ->icon(Heroicon::OutlinedDocumentText)
                ->color('gray')
                ->url(url('/llms.txt'))
                ->openUrlInNewTab(),

            Action::make('aiSeoDenetimi')
                ->label('AI SEO & GEO Sağlık Denetimi')
                ->icon(Heroicon::OutlinedSparkles)
                ->color('primary')
                ->modalHeading('AI SEO & Generative Engine Optimization (GEO) Denetimi')
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Kapat')
                ->modalContent(function () {
                    $assistant = app(GrowthMarketingAiAssistant::class);
                    $settings = [
                        'default_title' => (string) Settings::get('seo.default_title'),
                        'default_description' => (string) Settings::get('seo.default_description'),
                        'og_image' => (string) Settings::get('seo.og_image'),
                        'robots_index' => (Settings::get('seo.robots_index') ?? '1') === '1',
                    ];
                    $stats = [
                        'total_listings' => Listing::query()->count(),
                        'total_categories' => Category::query()->where('is_active', true)->count(),
                        'total_countries' => Country::query()->where('is_active', true)->count(),
                        'has_llms_txt' => true,
                    ];

                    $audit = $assistant->auditSeoAndGeo($settings, $stats);

                    return view('filament.seo.denetim-modal', [
                        'audit' => $audit,
                    ]);
                }),
        ];
    }

    public function mount(): void
    {
        $this->form->fill([
            'default_title' => Settings::get('seo.default_title'),
            'default_description' => Settings::get('seo.default_description'),
            'og_image' => Settings::get('seo.og_image') ?: null,
            'robots_index' => (Settings::get('seo.robots_index') ?? '1') === '1',
            'llms_txt_custom' => Settings::get('seo.llms_txt_custom') ?: '',
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Arama Motoru & Paylaşım (Klasik SEO)')
                    ->description('Sayfaların varsayılan başlık ve açıklaması. İlan ve rehber sayfaları kendi başlıklarını öncelikli kullanır.')
                    ->schema([
                        TextInput::make('default_title')
                            ->label('Varsayılan başlık')
                            ->maxLength(70)
                            ->helperText('Tarayıcı sekmesinde ve arama sonuçlarında görünür. ~60 karakter idealdir.')
                            ->hintActions([
                                Action::make('aiOptimizeTitle')
                                    ->label('AI ile Başlığı Güçlendir')
                                    ->icon(Heroicon::OutlinedSparkles)
                                    ->tooltip('Yüksek tıklanma (CTR) ve diaspora aramalarına göre optimize et')
                                    ->action(function (callable $get, callable $set): void {
                                        $current = (string) ($get('default_title') ?: 'Nisoya');
                                        $assistant = app(GrowthMarketingAiAssistant::class);
                                        $res = $assistant->optimizeMetaTags('title', $current);
                                        $set('default_title', $res['suggested']);

                                        Notification::make()
                                            ->title('AI Başlığı Optimize Etti')
                                            ->body("{$res['reason']} ({$res['char_count']} karakter, Skor: %{$res['score']})")
                                            ->success()
                                            ->send();
                                    }),
                            ]),

                        Textarea::make('default_description')
                            ->label('Varsayılan açıklama')
                            ->rows(3)
                            ->maxLength(200)
                            ->helperText('Arama sonuçlarında başlığın altındaki metin. ~155 karakter idealdir.')
                            ->hintActions([
                                Action::make('aiOptimizeDesc')
                                    ->label('AI ile Açıklamayı Güçlendir')
                                    ->icon(Heroicon::OutlinedSparkles)
                                    ->tooltip('Yapay zekâ ile eyleme çağrı ve diaspora güveni ekle')
                                    ->action(function (callable $get, callable $set): void {
                                        $current = (string) ($get('default_description') ?: 'Nisoya ilan platformu');
                                        $assistant = app(GrowthMarketingAiAssistant::class);
                                        $res = $assistant->optimizeMetaTags('description', $current);
                                        $set('default_description', $res['suggested']);

                                        Notification::make()
                                            ->title('AI Açıklamayı Optimize Etti')
                                            ->body("{$res['reason']} ({$res['char_count']} karakter, Skor: %{$res['score']})")
                                            ->success()
                                            ->send();
                                    }),
                            ]),

                        MedyaAlani::make('og_image', 'seo_og')
                            ->label('Paylaşım görseli (OG)')
                            ->helperText('WhatsApp/Facebook/X’te link paylaşılınca görünen görsel. Otomatik 1200×630 yapılır. Boşsa varsayılan og.png kullanılır.'),
                    ]),

                Section::make('Görünürlük & İndeksleme')
                    ->schema([
                        Toggle::make('robots_index')
                            ->label('Arama motorlarında görünür')
                            ->helperText('Kapatırsan siteye "noindex" eklenir — Google gibi arama motorları ve AI botları siteyi listelememeye başlar. Site hazır değilken kullan.'),
                    ]),

                Section::make('Generative Engine Optimization (GEO) & /llms.txt Standardı')
                    ->description('Yapay zekâ arama motorlarının (Claude, SearchGPT, Perplexity, Gemini) platformu dizine eklemesi ve kullanıcılara doğrudan kaynak göstererek alıntılaması için standart /llms.txt içeriği.')
                    ->schema([
                        Textarea::make('llms_txt_custom')
                            ->label('Özel /llms.txt Markdown İçeriği')
                            ->rows(8)
                            ->placeholder('Boş bırakırsanız sistem platform kategorilerini ve diaspora ülkelerini otomatik derleyerek canlı sunar.')
                            ->helperText('Boş bırakırsanız dinamik olarak /llms.txt adresinde kategoriler, ülkeler ve vitrin bağlantıları yayınlanır. Özelleştirmek isterseniz buraya yazabilirsiniz.')
                            ->hintActions([
                                Action::make('aiDerle')
                                    ->label('Dinamik /llms.txt Derle')
                                    ->icon(Heroicon::OutlinedArrowPath)
                                    ->tooltip('Mevcut kategoriler ve ülkelerle standart markdown şablonu oluştur')
                                    ->action(function (callable $set): void {
                                        $assistant = app(GrowthMarketingAiAssistant::class);
                                        $set('llms_txt_custom', $assistant->generateLlmsTxt());

                                        Notification::make()
                                            ->title('/llms.txt Derlendi')
                                            ->body('Standart GEO markdown içeriği metin kutusuna aktarıldı.')
                                            ->info()
                                            ->send();
                                    }),
                            ]),
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
            'seo.llms_txt_custom' => $state['llms_txt_custom'] ?? '',
        ]);

        Cache::forget('geo_llms_txt');
        Cache::forget('geo_llms_full_txt');

        Notification::make()
            ->title('SEO & GEO ayarları kaydedildi')
            ->body('Değişiklik canlı sitede ve /llms.txt rotasında anında geçerli.')
            ->success()
            ->send();
    }
}
