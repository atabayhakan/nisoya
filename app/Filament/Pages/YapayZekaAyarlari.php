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
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * Yapay zekâ yönetim ve kontrol merkezi.
 *
 * Sitedeki tüm yapay zekâ servislerini (fotoğrafla ilan, moderasyon, Kâhya asistanı,
 * doğal dil arama, çeviri ve dolandırıcılık tespiti) tek merkezden yönetir.
 *
 * Canlı model kayıt defteri ile entegre çalışır; anlık model taraması ve
 * günlük otomatik sağlık denetimi (`ai:modelleri-denetle`) sağlar.
 */
class YapayZekaAyarlari extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static string|UnitEnum|null $navigationGroup = 'Kâhya & Yapay Zekâ';

    protected static ?string $navigationLabel = 'Yapay Zeka';

    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.pages.yapay-zeka-ayarlari';

    public ?array $data = [];

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
        $savedModel = Settings::get('ai.model') ?: '';
        $isCustom = (Settings::get('ai.ozel_model_aktif') ?? '0') === '1';

        $this->form->fill([
            'yapay_zeka_aktif' => (Settings::get('ai.aktif') ?? '1') === '1',
            'saglayici' => Settings::get('ai.saglayici') ?: config('ai.default', 'openrouter'),
            'api_anahtari' => Settings::get('ai.api_anahtari') ?: '',
            'model' => $savedModel,
            'ozel_model_aktif' => $isCustom,
            'ozel_model' => $isCustom ? $savedModel : (Settings::get('ai.ozel_model') ?: ''),
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
                    ->description('Sitedeki tüm yapay zekâ işlemlerini besleyen temel model altyapısı ve API kimlik doğrulaması.')
                    ->columns(2)
                    ->schema([
                        Select::make('saglayici')
                            ->label('Yapay Zekâ Sağlayıcısı')
                            ->options([
                                'openrouter' => '🌐 OpenRouter (Tek uçtan yüzlerce model — önerilen)',
                                'nvidia' => '🟢 NVIDIA NIM (Llama 3.2 Vision, Nemotron vb.)',
                                'groq' => '⚡ Groq (Ultra Yüksek Hız — Llama 3.2 Vision vb.)',
                                'deepseek' => '🐋 DeepSeek (DeepSeek-V3 / R1 Muhakeme)',
                                'mistral' => '🌪️ Mistral AI (Pixtral Vision, Mistral Large)',
                                'openai' => '🤖 OpenAI (GPT-4o, GPT-4o Mini)',
                                'anthropic' => '🧠 Anthropic (Claude 3.5 Sonnet / Haiku)',
                                'gemini' => '🔷 Google Gemini (Gemini 2.0 Flash)',
                            ])
                            ->required()
                            ->live()
                            ->native(false)
                            ->helperText(fn (Get $get): string => match ($get('saglayici')) {
                                'openrouter' => 'OpenRouter tek API anahtarıyla OpenAI, Claude, Llama ve Gemini dahil yüzlerce modele erişim sağlar.',
                                'nvidia' => 'NVIDIA NIM kurumsal API ucu. Llama 3.2 Vision modelleri için optimize edilmiştir.',
                                'groq' => 'Groq LPU mimarisi. Saniyede 500+ token ultra hızlı çıkarım sağlar.',
                                'deepseek' => 'DeepSeek API ucu. Yüksek akıl yürütme ve ekonomik maliyet sunar.',
                                'mistral' => 'Mistral AI ucu. Pixtral vision ve Mistral Large modelleri.',
                                default => 'Resmî sağlayıcı API anahtarınız ile doğrudan bağlantı.',
                            }),

                        TextInput::make('api_anahtari')
                            ->label('Gizli API Anahtarı')
                            ->password()
                            ->revealable()
                            ->autocomplete('new-password')
                            ->helperText('Sağlayıcının konsolundan aldığınız gizli anahtar. Veritabanında güvenle saklanır, kimseyle paylaşılmaz.'),

                        Select::make('model')
                            ->label('Varsayılan Yapay Zekâ Modeli')
                            ->searchable()
                            ->preload()
                            ->live()
                            ->native(false)
                            ->hidden(fn (Get $get): bool => (bool) $get('ozel_model_aktif'))
                            ->options(function (Get $get): array {
                                $provider = (string) ($get('saglayici') ?: 'openrouter');
                                $current = (string) ($get('model') ?: '');

                                return app(AiModelRegistry::class)->getGroupedModels($provider, $current ?: null);
                            })
                            ->placeholder('Model arayın veya listeden seçin...')
                            ->helperText('Fotoğrafla hızlı ilan ve görsel moderasyonu için [Vision] etiketli modeller önerilir.'),

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
        $isCustom = ! empty($state['ozel_model_aktif']);
        $model = $isCustom && filled($state['ozel_model'] ?? null)
            ? trim((string) $state['ozel_model'])
            : trim((string) ($state['model'] ?? ''));

        Settings::setMany([
            'ai.aktif' => ! empty($state['yapay_zeka_aktif']) ? '1' : '0',
            'ai.saglayici' => $state['saglayici'] ?? '',
            'ai.api_anahtari' => $state['api_anahtari'] ?? '',
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
        ]);

        Notification::make()
            ->title('Yapay zekâ ayarları kaydedildi ✓')
            ->body('Değişiklikler canlı sitede anında geçerlidir. Sunucu yeniden başlatma veya önbellek temizleme gerekmez.')
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
        $name = $state['saglayici'] ?: config('ai.default', 'openrouter');

        $isCustom = ! empty($state['ozel_model_aktif']);
        $model = $isCustom && filled($state['ozel_model'] ?? null)
            ? trim((string) $state['ozel_model'])
            : trim((string) ($state['model'] ?? ''));

        $config = array_merge(config("ai.providers.{$name}", []), array_filter([
            'api_key' => $state['api_anahtari'] ?? null,
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
        $model = trim((string) Settings::get('ai.model')) ?: (string) config("ai.providers.{$provider}.model", 'openai/gpt-4o-mini');
        $isVision = str_contains(mb_strtolower($model), 'vision')
            || str_contains(mb_strtolower($model), '4o')
            || str_contains(mb_strtolower($model), 'gemini')
            || str_contains(mb_strtolower($model), 'pixtral')
            || str_contains(mb_strtolower($model), 'claude');
        $isConfigured = filled(Settings::get('ai.api_anahtari') ?: config("ai.providers.{$provider}.api_key"));
        $isActive = (Settings::get('ai.aktif') ?? '1') === '1';

        return [
            'provider' => $provider,
            'model' => $model,
            'is_vision' => $isVision,
            'is_configured' => $isConfigured,
            'is_active' => $isActive,
        ];
    }
}
