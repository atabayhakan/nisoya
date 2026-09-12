<?php

namespace App\Filament\Pages;

use App\Services\Ai\AiManager;
use App\Services\Ai\AiModelRegistry;
use App\Support\Settings;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;
use UnitEnum;

/**
 * Yapay zekâ yönetim ve kontrol merkezi.
 *
 * Sitedeki tüm yapay zekâ servislerini (fotoğrafla ilan, moderasyon, Kâhya asistanı,
 * doğal dil arama, çeviri ve dolandırıcılık tespiti) tek merkezden yönetir.
 *
 * Her sağlayıcı (OpenRouter, NVIDIA, Groq, DeepSeek, Mistral, OpenAI, Anthropic, Gemini)
 * için bağımsız API anahtarı ve model hafızası tutulur; sağlayıcı değiştirildiğinde
 * diğer sağlayıcıların anahtarları silinmez.
 */
class YapayZekaAyarlari extends Page
{
    public const PROVIDERS = [
        'openrouter' => [
            'name' => 'OpenRouter',
            'label' => '🌐 OpenRouter (Tek uçtan yüzlerce model — önerilen)',
            'short_desc' => 'Tek API ile yüzlerce model',
            'console_url' => 'https://openrouter.ai/keys',
            'default_model' => 'openai/gpt-4o-mini',
            'helper_text' => 'OpenRouter tek API anahtarıyla OpenAI, Claude, Llama ve Gemini dahil yüzlerce modele erişim sağlar.',
        ],
        'nvidia' => [
            'name' => 'NVIDIA NIM',
            'label' => '🟢 NVIDIA NIM (Llama 3.2 Vision, Nemotron vb.)',
            'short_desc' => 'NVIDIA kurumsal GPU çıkarımı',
            'console_url' => 'https://build.nvidia.com',
            'default_model' => 'meta/llama-3.2-11b-vision-instruct',
            'helper_text' => 'NVIDIA NIM kurumsal API ucu. Llama 3.2 Vision ve Nemotron modelleri için optimize edilmiştir.',
        ],
        'groq' => [
            'name' => 'Groq',
            'label' => '⚡ Groq (Ultra Yüksek Hız — Llama 3.2 Vision vb.)',
            'short_desc' => 'Ultra yüksek hızlı LPU mimarisi',
            'console_url' => 'https://console.groq.com/keys',
            'default_model' => 'llama-3.2-11b-vision-preview',
            'helper_text' => 'Groq LPU mimarisi. Saniyede 500+ token ultra hızlı çıkarım sağlar.',
        ],
        'deepseek' => [
            'name' => 'DeepSeek',
            'label' => '🐋 DeepSeek (DeepSeek-V3 / R1 Muhakeme)',
            'short_desc' => 'Ekonomik & yüksek akıl yürütme',
            'console_url' => 'https://platform.deepseek.com/api_keys',
            'default_model' => 'deepseek-chat',
            'helper_text' => 'DeepSeek API ucu. Yüksek akıl yürütme ve ekonomik maliyet sunar.',
        ],
        'mistral' => [
            'name' => 'Mistral AI',
            'label' => '🌪️ Mistral AI (Pixtral Vision, Mistral Large)',
            'short_desc' => 'Avrupa merkezli açık ağırlıklı AI',
            'console_url' => 'https://console.mistral.ai/api-keys',
            'default_model' => 'pixtral-12b-2409',
            'helper_text' => 'Mistral AI ucu. Pixtral vision ve Mistral Large modelleri.',
        ],
        'openai' => [
            'name' => 'OpenAI',
            'label' => '🤖 OpenAI (GPT-4o, GPT-4o Mini)',
            'short_desc' => 'Endüstri standardı multimodal AI',
            'console_url' => 'https://platform.openai.com/api-keys',
            'default_model' => 'gpt-4o-mini',
            'helper_text' => 'Resmî OpenAI API ucu. GPT-4o ve GPT-4o Mini multimodal modelleri.',
        ],
        'anthropic' => [
            'name' => 'Anthropic',
            'label' => '🧠 Anthropic (Claude 3.5 Sonnet / Haiku)',
            'short_desc' => 'İleri mantık & vision',
            'console_url' => 'https://console.anthropic.com/settings/keys',
            'default_model' => 'claude-3-5-haiku-latest',
            'helper_text' => 'Anthropic Claude API ucu. İleri seviye mantık, vizyon ve muhakeme.',
        ],
        'gemini' => [
            'name' => 'Google Gemini',
            'label' => '🔷 Google Gemini (Gemini 2.0 Flash)',
            'short_desc' => '1M+ context & multimodal zekâ',
            'console_url' => 'https://aistudio.google.com/apikey',
            'default_model' => 'gemini-2.0-flash',
            'helper_text' => 'Google AI Studio / Gemini API ucu. 1M+ bağlam penceresi ve yüksek hız.',
        ],
    ];

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static string|UnitEnum|null $navigationGroup = 'Kâhya & Yapay Zekâ';

    protected static ?string $navigationLabel = 'Yapay Zeka';

    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.pages.yapay-zeka-ayarlari';

    public ?array $data = [];

    /**
     * Her sağlayıcı için bağımsız in-memory API anahtarları.
     *
     * @var array<string, string>
     */
    public array $api_anahtarlari = [];

    /**
     * Her sağlayıcı için bağımsız in-memory model seçimleri.
     *
     * @var array<string, string>
     */
    public array $modeller = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public function getTitle(): string
    {
        return 'Yapay Zekâ Kontrol Merkezi';
    }

    public function mount(): void
    {
        // Tüm sağlayıcıların kayıtlı anahtar ve modellerini hafızaya yükle
        foreach (self::PROVIDERS as $providerKey => $meta) {
            $key = (string) (Settings::get("ai.keys.{$providerKey}") ?? '');
            if ($key === '' && $providerKey === 'openrouter') {
                $key = (string) (Settings::get('ai.api_anahtari') ?: config('ai.providers.openrouter.api_key', ''));
            } elseif ($key === '') {
                $key = (string) config("ai.providers.{$providerKey}.api_key", '');
            }
            $this->api_anahtarlari[$providerKey] = $key;

            $model = (string) (Settings::get("ai.models.{$providerKey}") ?? '');
            if ($model === '' && $providerKey === 'openrouter') {
                $model = (string) (Settings::get('ai.model') ?: config('ai.providers.openrouter.model', $meta['default_model']));
            } elseif ($model === '') {
                $model = (string) config("ai.providers.{$providerKey}.model", $meta['default_model']);
            }
            $this->modeller[$providerKey] = $model;
        }

        $activeProvider = (string) (Settings::get('ai.saglayici') ?: config('ai.default', 'openrouter'));
        if (! isset(self::PROVIDERS[$activeProvider])) {
            $activeProvider = 'openrouter';
        }

        $activeKey = $this->api_anahtarlari[$activeProvider] ?? '';
        $activeModel = $this->modeller[$activeProvider] ?? self::PROVIDERS[$activeProvider]['default_model'];
        $isCustom = (Settings::get('ai.ozel_model_aktif') ?? '0') === '1';

        $this->form->fill([
            'yapay_zeka_aktif' => (Settings::get('ai.aktif') ?? '1') === '1',
            'saglayici' => $activeProvider,
            'api_anahtari' => $activeKey,
            'model' => $activeModel,
            'ozel_model_aktif' => $isCustom,
            'ozel_model' => $isCustom ? $activeModel : (Settings::get('ai.ozel_model') ?: ''),
            'hizli_ilan_aktif' => (Settings::get('ai.hizli_ilan_aktif') ?? '1') === '1',
            'moderasyon_aktif' => (Settings::get('ai.moderasyon_aktif') ?? '1') === '1',
            'temsili_gorsel_aktif' => (Settings::get('ai.temsili_gorsel_aktif') ?? '1') === '1',
            'nisoya_ai_arama_aktif' => (Settings::get('ai.nisoya_ai_arama_aktif') ?? '1') === '1',
            'dogal_dil_arama_aktif' => (Settings::get('ai.dogal_dil_arama_aktif') ?? '1') === '1',
            'metin_moderasyon_aktif' => (Settings::get('ai.metin_moderasyon_aktif') ?? '1') === '1',
            'ilan_cevirisi_aktif' => (Settings::get('ai.ilan_cevirisi_aktif') ?? '1') === '1',
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Acil Durum Şalteri (Master Killswitch)')
                    ->description('Tüm site genelindeki yapay zekâ motorunu tek tıkla durdurma gücü. Kapatıldığında tüm AI özellikleri gizlenir, harici API çağrıları sıfırlanır; site standart geleneksel ilan yapısıyla hatasız çalışmaya devam eder.')
                    ->schema([
                        Toggle::make('yapay_zeka_aktif')
                            ->label('Yapay Zekâ Motoru Aktif')
                            ->helperText('Açık: Tüm AI servisleri aşağıdaki yetenek matrisine göre çalışır. Kapalı: Tüm harici AI istekleri anında kesilir.'),
                    ]),

                Section::make('Sağlayıcı ve Akıllı Model Mimarisi')
                    ->description('Sitedeki tüm yapay zekâ işlemlerini besleyen temel model altyapısı ve API kimlik doğrulaması. Her sağlayıcının anahtarı bağımsız saklanır.')
                    ->columns(2)
                    ->schema([
                        Select::make('saglayici')
                            ->label('Yapay Zekâ Sağlayıcısı')
                            ->options(collect(self::PROVIDERS)->mapWithKeys(fn ($v, $k) => [$k => $v['label']])->all())
                            ->required()
                            ->live()
                            ->native(false)
                            ->afterStateUpdated(function ($state, $old, Set $set, Get $get): void {
                                // 1. Önceki sağlayıcının formdaki değerlerini hafızaya al
                                if ($old && is_string($old) && isset(self::PROVIDERS[$old])) {
                                    $this->api_anahtarlari[$old] = (string) $get('api_anahtari');
                                    $this->modeller[$old] = (string) $get('model');
                                }

                                $newProvider = (string) ($state ?: 'openrouter');
                                if (! isset(self::PROVIDERS[$newProvider])) {
                                    $newProvider = 'openrouter';
                                }

                                // 2. Yeni seçilen sağlayıcının bağımsız anahtar ve modelini getir
                                $targetKey = $this->api_anahtarlari[$newProvider]
                                    ?? (string) (Settings::get("ai.keys.{$newProvider}") ?: '');
                                $targetModel = $this->modeller[$newProvider]
                                    ?? (string) (Settings::get("ai.models.{$newProvider}") ?: self::PROVIDERS[$newProvider]['default_model']);

                                // 3. Form alanlarını yeni sağlayıcıya göre güncelle
                                $set('api_anahtari', $targetKey);
                                $set('model', $targetModel);
                                $set('ozel_model_aktif', false);
                                $set('ozel_model', '');
                            })
                            ->helperText(fn (Get $get): string => self::PROVIDERS[$get('saglayici')]['helper_text'] ?? 'Resmî sağlayıcı API anahtarınız ile doğrudan bağlantı.'),

                        TextInput::make('api_anahtari')
                            ->label(fn (Get $get): string => (self::PROVIDERS[$get('saglayici')]['name'] ?? 'Sağlayıcı').' API Anahtarı')
                            ->password()
                            ->revealable()
                            ->autocomplete('new-password')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, Get $get): void {
                                $p = (string) ($get('saglayici') ?: 'openrouter');
                                $this->api_anahtarlari[$p] = (string) $state;
                            })
                            ->placeholder(fn (Get $get): string => match ($get('saglayici')) {
                                'openrouter' => 'sk-or-v1-...',
                                'nvidia' => 'nvapi-...',
                                'groq' => 'gsk_...',
                                'deepseek' => 'sk-...',
                                'mistral' => '...',
                                'openai' => 'sk-proj-...',
                                'anthropic' => 'sk-ant-...',
                                'gemini' => 'AIzaSy...',
                                default => 'API Anahtarınızı yapıştırın...',
                            })
                            ->helperText(function (Get $get): HtmlString {
                                $p = (string) ($get('saglayici') ?: 'openrouter');
                                $url = self::PROVIDERS[$p]['console_url'] ?? 'https://openrouter.ai/keys';
                                $name = self::PROVIDERS[$p]['name'] ?? 'Sağlayıcı';

                                return new HtmlString("Sağlayıcı konsolundan anahtar alın: <a href=\"{$url}\" target=\"_blank\" rel=\"noopener\" class=\"font-medium text-primary-600 dark:text-primary-400 hover:underline inline-flex items-center gap-1\">{$name} Konsolu ↗</a> (Diğer sağlayıcıların anahtarları silinmez, her sağlayıcı için ayrı saklanır).");
                            }),

                        Select::make('model')
                            ->label('Varsayılan Yapay Zekâ Modeli')
                            ->searchable()
                            ->preload()
                            ->live()
                            ->native(false)
                            ->afterStateUpdated(function ($state, Get $get): void {
                                $p = (string) ($get('saglayici') ?: 'openrouter');
                                $this->modeller[$p] = (string) $state;
                            })
                            ->hidden(fn (Get $get): bool => (bool) $get('ozel_model_aktif'))
                            ->options(function (Get $get): array {
                                $provider = (string) ($get('saglayici') ?: 'openrouter');
                                $current = (string) ($get('model') ?: '');

                                return app(AiModelRegistry::class)->getGroupedModels($provider, $current ?: null);
                            })
                            ->placeholder('Model arayın veya listeden seçin...')
                            ->helperText('Fotoğraflı ilan ve görsel moderasyonu için [Vision] etiketli modeller önerilir. Ücretsiz modeller listenin en başında yer alır.'),

                        TextInput::make('ozel_model')
                            ->label('Özel Model Kimliği (ID)')
                            ->visible(fn (Get $get): bool => (bool) $get('ozel_model_aktif'))
                            ->placeholder('ör. meta-llama/llama-3.2-11b-vision-instruct')
                            ->helperText('Sağlayıcınızın konsolundaki tam model kodunu eksiksiz yazın.')
                            ->required(fn (Get $get): bool => (bool) $get('ozel_model_aktif')),

                        Toggle::make('ozel_model_aktif')
                            ->label('Listede olmayan özel bir model kimliği yazmak istiyorum')
                            ->live()
                            ->columnSpanFull()
                            ->helperText('Açıldığında serbest model kodu girebileceğiniz metin kutusu aktif olur.'),
                    ]),

                Section::make('📸 Görsel & İlan Zekâsı')
                    ->description('İlan fotoğraflarının taranması, otomatik taslak üretimi ve görsel güvenlik denetimleri.')
                    ->columns(3)
                    ->schema([
                        Toggle::make('hizli_ilan_aktif')
                            ->label('Fotoğrafla Hızlı İlan')
                            ->helperText('Kullanıcı ürün/araç fotoğrafı yüklediğinde başlık, kategori, fiyat ve özellikleri anında doldurur.'),

                        Toggle::make('moderasyon_aktif')
                            ->label('Görsel Moderasyonu')
                            ->helperText('İlan ve sohbet görsellerinde müstehcenlik, şiddet ve dolandırıcılık tespiti yapar.'),

                        Toggle::make('temsili_gorsel_aktif')
                            ->label('Temsilî Görsel Üretimi')
                            ->helperText('2+ gündür fotoğrafsız kalan aktif ilanlara AI ile temsilî kapak görseli üretir.'),
                    ]),

                Section::make('🔍 Arama, Keşif & Dil Zekâsı')
                    ->description('Ziyaretçilerin arama deneyimini zenginleştiren ve güvenliği sağlayan yapay zekâ katmanları.')
                    ->columns(2)
                    ->schema([
                        Toggle::make('nisoya_ai_arama_aktif')
                            ->label('Anasayfa "Nisoya AI ile ara" Çubuğu')
                            ->helperText('Arama kutusu üzerinde çıkan, doğal dil sorularını ilgili ilan ve rehberlere yönlendiren soru çubuğu.'),

                        Toggle::make('dogal_dil_arama_aktif')
                            ->label('Doğal Dil Arama Filtreleme')
                            ->helperText('Aramaya yazılan serbest ifadeleri ("berlin uygun kira", "temiz golf") kategori ve şehir filtrelerine çevirir.'),

                        Toggle::make('metin_moderasyon_aktif')
                            ->label('İlan Metni Dolandırıcılık Tespiti')
                            ->helperText('İlan başlık ve açıklamalarındaki şüpheli IBAN, kapora ve dolandırıcılık desenlerini ön-eler.'),

                        Toggle::make('ilan_cevirisi_aktif')
                            ->label('Çok Dilli İlan Yerelleştirme')
                            ->helperText('İlanları yerel ülke dillerine çevirerek Google arama motoru trafiğini artırır.'),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();
        $currentProvider = (string) ($state['saglayici'] ?? 'openrouter');
        if (! isset(self::PROVIDERS[$currentProvider])) {
            $currentProvider = 'openrouter';
        }

        $isCustom = ! empty($state['ozel_model_aktif']);
        $model = $isCustom && filled($state['ozel_model'] ?? null)
            ? trim((string) $state['ozel_model'])
            : trim((string) ($state['model'] ?? ''));

        // Aktif sağlayıcının formdaki değerlerini hafızaya yaz
        $currentKey = (string) ($state['api_anahtari'] ?? '');
        $this->api_anahtarlari[$currentProvider] = $currentKey;
        $this->modeller[$currentProvider] = $model;

        $settingsToSave = [
            'ai.aktif' => ! empty($state['yapay_zeka_aktif']) ? '1' : '0',
            'ai.saglayici' => $currentProvider,
            'ai.api_anahtari' => $currentKey,
            'ai.model' => $model,
            'ai.ozel_model_aktif' => $isCustom ? '1' : '0',
            'ai.ozel_model' => $state['ozel_model'] ?? '',
            'ai.hizli_ilan_aktif' => ! empty($state['hizli_ilan_aktif']) ? '1' : '0',
            'ai.moderasyon_aktif' => ! empty($state['moderasyon_aktif']) ? '1' : '0',
            'ai.temsili_gorsel_aktif' => ! empty($state['temsili_gorsel_aktif']) ? '1' : '0',
            'ai.nisoya_ai_arama_aktif' => ! empty($state['nisoya_ai_arama_aktif']) ? '1' : '0',
            'ai.dogal_dil_arama_aktif' => ! empty($state['dogal_dil_arama_aktif']) ? '1' : '0',
            'ai.metin_moderasyon_aktif' => ! empty($state['metin_moderasyon_aktif']) ? '1' : '0',
            'ai.ilan_cevirisi_aktif' => ! empty($state['ilan_cevirisi_aktif']) ? '1' : '0',
        ];

        // Her sağlayıcının anahtarını ve modelini bağımsız kaydet
        foreach (self::PROVIDERS as $p => $info) {
            if (array_key_exists($p, $this->api_anahtarlari)) {
                $settingsToSave["ai.keys.{$p}"] = $this->api_anahtarlari[$p];
            }
            if (array_key_exists($p, $this->modeller)) {
                $settingsToSave["ai.models.{$p}"] = $this->modeller[$p];
            }
        }

        Settings::setMany($settingsToSave);

        // Çalışma zamanı yapılandırmasını anında güncelle
        config(['ai.default' => $currentProvider]);
        foreach (self::PROVIDERS as $p => $info) {
            if (! empty($this->api_anahtarlari[$p])) {
                config(["ai.providers.{$p}.api_key" => $this->api_anahtarlari[$p]]);
                config(["ai.providers.{$p}.key" => $this->api_anahtarlari[$p]]);
            }
            if (! empty($this->modeller[$p])) {
                config(["ai.providers.{$p}.model" => $this->modeller[$p]]);
            }
        }

        $providerName = self::PROVIDERS[$currentProvider]['name'];

        Notification::make()
            ->title('Yapay zekâ ayarları kaydedildi ✓')
            ->body("Aktif sağlayıcı: {$providerName}. Tüm sağlayıcı anahtarları bağımsız olarak veritabanında güvenle saklandı.")
            ->success()
            ->send();
    }

    /** Sağlayıcıdan en güncel modelleri anında yeniden çeker ve listeyi tazeler. */
    public function modelleriGuncelle(): void
    {
        $state = $this->form->getState();
        $provider = (string) ($state['saglayici'] ?? config('ai.default', 'openrouter'));

        $registry = app(AiModelRegistry::class);
        $models = $registry->getAvailableModels($provider, forceRefresh: true);
        $count = count($models);

        Notification::make()
            ->title('Model Kataloğu Güncellendi ✓')
            ->body("[{$provider}] için {$count} adet çalışan güncel model başarıyla çekildi ve arama listesine eklendi.")
            ->success()
            ->send();
    }

    /** Sağlayıcı ve model bağlantısını canlı olarak test eder. */
    public function testEt(): void
    {
        $state = $this->form->getState();
        $name = (string) ($state['saglayici'] ?: config('ai.default', 'openrouter'));

        $isCustom = ! empty($state['ozel_model_aktif']);
        $model = $isCustom && filled($state['ozel_model'] ?? null)
            ? trim((string) $state['ozel_model'])
            : trim((string) ($state['model'] ?? ''));

        $key = (string) ($state['api_anahtari'] ?? ($this->api_anahtarlari[$name] ?? ''));

        $config = array_merge(config("ai.providers.{$name}", []), array_filter([
            'api_key' => $key ?: null,
            'model' => $model ?: null,
        ]));

        $provider = app(AiManager::class)->make($name, $config);

        if (! $provider->isConfigured()) {
            Notification::make()
                ->title('API Anahtarı Eksik')
                ->body('Bağlantıyı test etmeden önce lütfen API anahtarınızı girin.')
                ->warning()
                ->send();

            return;
        }

        $activeModel = $config['model'] ?? '';
        $isVision = str_contains(mb_strtolower($activeModel), 'vision')
            || str_contains(mb_strtolower($activeModel), '4o')
            || str_contains(mb_strtolower($activeModel), 'gemini')
            || str_contains(mb_strtolower($activeModel), 'pixtral')
            || str_contains(mb_strtolower($activeModel), 'claude');

        $startTime = microtime(true);

        if ($isVision) {
            $result = $provider->analyzeImage(
                $this->testImageBase64(),
                'image/jpeg',
                'Bu bir bağlantı testidir. Sadece şu JSON nesnesini döndür: {"ok": true}',
            );
        } else {
            $result = $provider->analyzeText(
                'Bu bir bağlantı testidir. Sadece şu JSON nesnesini döndür: {"ok": true}',
            );
        }

        $latencyMs = (int) round((microtime(true) - $startTime) * 1000);

        if ($result !== null) {
            $modality = $isVision ? 'Görüntü + Metin (Vision Destekli)' : 'Salt Metin (Vision Yok)';

            Notification::make()
                ->title('Bağlantı Başarılı ✓')
                ->body("{$provider->name()} ({$activeModel}) başarıyla yanıt verdi.\n• Yanıt Süresi: {$latencyMs}ms\n• Yetenek: {$modality}")
                ->success()
                ->send();
        } else {
            $error = $provider->lastError() ?? 'Sağlayıcı yanıt vermedi.';
            $lower = mb_strtolower($error);
            $hint = '';

            if (str_contains($lower, 'training violation') || str_contains($lower, 'data policy') || str_contains($lower, 'guardrail')) {
                $hint = "\n\n💡 İpucu: OpenRouter gizlilik filtreleriniz (openrouter.ai/settings/privacy) bu modelin sağlayıcısını kısıtlıyor. Listeden 'openai/gpt-4o-mini' veya 'google/gemini-2.0-flash-001' seçebilirsiniz.";
            } elseif (str_contains($lower, 'not a valid model id')) {
                $hint = "\n\n💡 İpucu: Model kimliği geçersiz. Lütfen açılır listedeki modellerden birini seçin.";
            } elseif (str_contains($lower, 'image') || str_contains($lower, 'vision')) {
                $hint = "\n\n💡 İpucu: Bu model görüntü (vision) desteklemiyor. Fotoğrafla ilan için [Vision] etiketli bir model seçin.";
            }

            Notification::make()
                ->title('Bağlantı Kurulamadı')
                ->body($error.$hint)
                ->danger()
                ->persistent()
                ->send();
        }
    }

    /** Test için küçük ama geçerli bir JPEG üretir. */
    private function testImageBase64(): string
    {
        $im = imagecreatetruecolor(256, 256);
        imagefilledrectangle($im, 0, 0, 255, 255, (int) imagecolorallocate($im, 51, 102, 204));
        imagefilledrectangle($im, 60, 60, 196, 196, (int) imagecolorallocate($im, 240, 200, 40));

        ob_start();
        imagejpeg($im, null, 80);
        $data = (string) ob_get_clean();
        imagedestroy($im);

        return base64_encode($data);
    }

    /** Sayfa başlığında canlı durum bilgilerini gösterir. */
    public function getAktifDurumProperty(): array
    {
        $provider = trim((string) Settings::get('ai.saglayici')) ?: (string) config('ai.default', 'openrouter');
        if (! isset(self::PROVIDERS[$provider])) {
            $provider = 'openrouter';
        }

        $model = trim((string) Settings::get('ai.model'))
            ?: (string) (Settings::get("ai.models.{$provider}")
            ?: config("ai.providers.{$provider}.model", self::PROVIDERS[$provider]['default_model']));

        $isVision = str_contains(mb_strtolower($model), 'vision')
            || str_contains(mb_strtolower($model), '4o')
            || str_contains(mb_strtolower($model), 'gemini')
            || str_contains(mb_strtolower($model), 'pixtral')
            || str_contains(mb_strtolower($model), 'claude');

        $activeKey = Settings::get("ai.keys.{$provider}")
            ?: ($provider === 'openrouter' ? Settings::get('ai.api_anahtari') : null)
            ?: config("ai.providers.{$provider}.api_key");

        $isConfigured = filled($activeKey);
        $isActive = (Settings::get('ai.aktif') ?? '1') === '1';

        $configuredCount = 0;
        $providerStatuses = [];
        foreach (self::PROVIDERS as $key => $info) {
            $hasKey = filled(Settings::get("ai.keys.{$key}") ?: ($key === 'openrouter' ? Settings::get('ai.api_anahtari') : config("ai.providers.{$key}.api_key")));
            if ($hasKey) {
                $configuredCount++;
            }
            $providerStatuses[$key] = [
                'name' => $info['name'],
                'has_key' => $hasKey,
                'is_active' => $key === $provider,
            ];
        }

        return [
            'provider' => $provider,
            'provider_name' => self::PROVIDERS[$provider]['name'],
            'model' => $model,
            'is_vision' => $isVision,
            'is_configured' => $isConfigured,
            'is_active' => $isActive,
            'configured_count' => $configuredCount,
            'total_providers' => count(self::PROVIDERS),
            'provider_statuses' => $providerStatuses,
        ];
    }
}
