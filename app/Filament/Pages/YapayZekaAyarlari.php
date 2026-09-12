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
 * Yapay zeka sağlayıcı ayarları (kamera-önce hızlı ilan görüntü analizi).
 * Sağlayıcı + API anahtarı + model buradan girilir; DB'ye yazılır ve
 * AppServiceProvider::mergeRuntimeConfig() ile config('ai.*') runtime'da
 * override edilir — DEĞİŞİKLİK ANINDA GEÇERLİ olur, config:cache/SSH gerekmez.
 *
 * Sağlayıcı katmanı için bkz. App\Contracts\AiProvider + config/ai.php.
 */
class YapayZekaAyarlari extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static string|UnitEnum|null $navigationGroup = 'Kâhya & Yapay Zekâ';

    protected static ?string $navigationLabel = 'Yapay Zeka';

    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.pages.yapay-zeka-ayarlari';

    public ?array $data = [];

    /** AI sağlayıcı API anahtarı burada görünür/düzenlenir — yalnızca Admin.
     *  Moderatör bu sayfaya (menüde ve doğrudan URL ile) erişemez. */
    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public function getTitle(): string
    {
        return 'Yapay Zeka Ayarları';
    }

    public function mount(): void
    {
        $this->form->fill([
            'yapay_zeka_aktif' => (Settings::get('ai.aktif') ?? '1') === '1',
            'saglayici' => Settings::get('ai.saglayici') ?: config('ai.default', 'openrouter'),
            'api_anahtari' => Settings::get('ai.api_anahtari') ?: '',
            'model' => Settings::get('ai.model') ?: '',
            'hizli_ilan_aktif' => (Settings::get('ai.hizli_ilan_aktif') ?? '1') === '1',
            'moderasyon_aktif' => (Settings::get('ai.moderasyon_aktif') ?? '1') === '1',
            'nisoya_ai_arama_aktif' => (Settings::get('ai.nisoya_ai_arama_aktif') ?? '1') === '1',
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Ana anahtar')
                    ->description('Yapay zekayı tamamen kapatmak için tek düğme. Kapatırsan — sağlayıcı/anahtar girili ve aşağıdaki özellikler açık olsa bile — hem fotoğrafla hızlı ilan hem görsel moderasyonu devre dışı kalır. Sağlayıcı çökerse veya maliyeti durdurmak istersen bunu kullan.')
                    ->schema([
                        Toggle::make('yapay_zeka_aktif')
                            ->label('Yapay zeka açık')
                            ->helperText('Kapalıyken site tamamen çalışır; yalnızca AI destekli özellikler gizlenir (ilanlar elle doldurulur, moderasyon insana kalır).'),
                    ]),

                Section::make('Sağlayıcı ve Anahtar')
                    ->description('Sitedeki tüm yapay zekâ özellikleri (Fotoğrafla Hızlı İlan, Görsel Moderasyonu, Nisoya AI Arama ve Kâhya Asistanı) için temel sağlayıcı ve varsayılan model.')
                    ->columns(2)
                    ->schema([
                        Select::make('saglayici')
                            ->label('Sağlayıcı')
                            ->options([
                                'openrouter' => 'OpenRouter (tek uçtan yüzlerce model — önerilen)',
                                'nvidia' => 'NVIDIA NIM (Llama 3.2 Vision, Nemotron vb.)',
                                'groq' => 'Groq (Ultra Hızlı Çıkarım — Llama 3.2 Vision vb.)',
                                'deepseek' => 'DeepSeek (V3 / R1)',
                                'mistral' => 'Mistral AI (Pixtral Vision, Mistral Large)',
                                'openai' => 'OpenAI',
                                'anthropic' => 'Anthropic (Claude)',
                                'gemini' => 'Google Gemini',
                            ])
                            ->required()
                            ->live()
                            ->native(false)
                            ->helperText('OpenRouter tek anahtarla yüzlerce model sunar; NVIDIA, Groq ve DeepSeek ise kurumsal hız ve açık modeller sunar.'),

                        TextInput::make('model')
                            ->label('Varsayılan Model')
                            ->placeholder(fn (Get $get): string => match ($get('saglayici') ?? 'openrouter') {
                                'nvidia' => 'meta/llama-3.2-11b-vision-instruct',
                                'groq' => 'llama-3.2-11b-vision-preview',
                                'deepseek' => 'deepseek-chat',
                                'mistral' => 'pixtral-12b-2409',
                                'openai' => 'gpt-4o-mini',
                                'anthropic' => 'claude-haiku-4-5',
                                'gemini' => 'gemini-2.0-flash',
                                default => 'openai/gpt-4o-mini',
                            })
                            ->datalist(function (Get $get): array {
                                $provider = (string) ($get('saglayici') ?: 'openrouter');

                                return array_keys(app(AiModelRegistry::class)->getAvailableModels($provider));
                            })
                            ->helperText(function (Get $get): string {
                                $provider = (string) ($get('saglayici') ?: 'openrouter');
                                $count = count(app(AiModelRegistry::class)->getAvailableModels($provider));

                                return "Fotoğrafla ilan ve genel AI işlemleri için kullanılır ({$count} model kayıtlı). Modelin görüntü (vision) desteklemesi gerekir. Boş bırakılırsa varsayılan model kullanılır.";
                            }),

                        TextInput::make('api_anahtari')
                            ->label('API anahtarı')
                            ->password()
                            ->revealable()
                            ->autocomplete('new-password')
                            ->helperText('Sağlayıcının panelinden aldığın gizli anahtar. Sunucuda güvenle saklanır; kimseye gösterilmez.')
                            ->columnSpanFull(),

                        Toggle::make('hizli_ilan_aktif')
                            ->label('Fotoğrafla hızlı ilan özelliği açık')
                            ->helperText('Kapatırsan anahtar girili olsa bile özellik gizlenir.')
                            ->columnSpanFull(),

                        Toggle::make('moderasyon_aktif')
                            ->label('Görsel moderasyonu açık (uygunsuz içerik ön-elemesi)')
                            ->helperText('İlan görselleri ve sohbet fotoğrafları aynı AI ile otomatik taranır. Uygunsuz bulunan ilan görselleri SİLİNMEZ — ilan incelemeye alınır (Onay bekliyor); sohbette uygunsuz fotoğraf gönderilemez. Nihai karar her zaman admin panelinden (Görseller) verilir.')
                            ->columnSpanFull(),

                        Toggle::make('nisoya_ai_arama_aktif')
                            ->label('Anasayfa "Nisoya AI ile ara" çubuğu açık')
                            ->helperText('Anasayfadaki arama kutusunun üstünde çıkan yapay zeka destekli soru çubuğu. Sitenin en görünür AI yüzeyi — maliyet sıçrarsa deploy beklemeden buradan kapat.')
                            ->columnSpanFull(),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();

        Settings::setMany([
            'ai.aktif' => ! empty($state['yapay_zeka_aktif']) ? '1' : '0',
            'ai.saglayici' => $state['saglayici'] ?? '',
            'ai.api_anahtari' => $state['api_anahtari'] ?? '',
            'ai.model' => $state['model'] ?? '',
            'ai.hizli_ilan_aktif' => ! empty($state['hizli_ilan_aktif']) ? '1' : '0',
            'ai.moderasyon_aktif' => ! empty($state['moderasyon_aktif']) ? '1' : '0',
            'ai.nisoya_ai_arama_aktif' => ! empty($state['nisoya_ai_arama_aktif']) ? '1' : '0',
        ]);

        Notification::make()
            ->title('Yapay zeka ayarları kaydedildi')
            ->body('Değişiklik canlı sitede anında geçerli — fotoğrafla hızlı ilan özelliği güncellendi.')
            ->success()
            ->send();
    }

    /** Anahtarın gerçekten çalıştığını doğrulamak için sağlayıcıya minik bir çağrı yapar. */
    public function testEt(): void
    {
        // Formdaki (henüz kaydedilmemiş olabilir) değerlerle sağlayıcıyı kur —
        // temel config'in üzerine form değerlerini yaz.
        $state = $this->form->getState();
        $name = $state['saglayici'] ?: config('ai.default', 'openrouter');
        $config = array_merge(config("ai.providers.{$name}", []), array_filter([
            'api_key' => $state['api_anahtari'] ?? null,
            'model' => ($state['model'] ?? '') ?: null,
        ]));

        $provider = app(AiManager::class)->make($name, $config);

        if (! $provider->isConfigured()) {
            Notification::make()
                ->title('Anahtar girilmemiş')
                ->body('Önce API anahtarını girip kaydet.')
                ->warning()
                ->send();

            return;
        }

        // Gerçek (küçük ama geçerli) bir görselle test — 1×1 gibi minik
        // görselleri vision modelleri "desteklenmeyen görsel" diye reddediyor.
        $result = $provider->analyzeImage(
            $this->testImageBase64(),
            'image/jpeg',
            'Bu bir bağlantı testidir. Sadece şu JSON nesnesini döndür: {"ok": true}',
        );

        if ($result !== null) {
            Notification::make()
                ->title('Bağlantı başarılı ✓')
                ->body($provider->name().' yanıt verdi. Ayarlar çalışıyor.')
                ->success()
                ->send();
        } else {
            $error = $provider->lastError() ?? 'Sağlayıcı yanıt vermedi.';
            $lower = mb_strtolower($error);
            $hint = '';

            if (str_contains($lower, 'training violation') || str_contains($lower, 'data policy') || str_contains($lower, 'guardrail')) {
                $hint = "\n\n💡 İpucu: OpenRouter gizlilik ayarlarınız (https://openrouter.ai/settings/privacy) bu modelin sağlayıcısını kısıtlıyor veya model vision desteklemiyor. Varsayılan Model kutusunu boş bırakın (openai/gpt-4o-mini kullanılır) ya da 'google/gemini-2.0-flash-001' seçin.";
            } elseif (str_contains($lower, 'not a valid model id')) {
                $hint = "\n\n💡 İpucu: Girilen model adı geçersiz. Kutuyu boş bırakın veya açılır listedeki modellerden birini (ör. openai/gpt-4o-mini) seçin.";
            } elseif (str_contains($lower, 'image') || str_contains($lower, 'vision')) {
                $hint = ' → Bu model görüntü (vision) desteklemiyor. Görüntü destekleyen bir model seç (ör. openai/gpt-4o-mini, google/gemini-2.0-flash-001).';
            }

            Notification::make()
                ->title('Bağlantı kurulamadı')
                ->body($error.$hint)
                ->danger()
                ->persistent()
                ->send();
        }
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
            ->title('Model listesi güncellendi')
            ->body("[{$provider}] için {$count} adet çalışan güncel model çekildi ve listeye eklendi.")
            ->success()
            ->send();
    }

    /** Test için küçük ama geçerli bir JPEG üretir (vision modelleri minik görseli reddeder). */
    private function testImageBase64(): string
    {
        $im = imagecreatetruecolor(256, 256);
        imagefilledrectangle($im, 0, 0, 255, 255, imagecolorallocate($im, 51, 102, 204));
        imagefilledrectangle($im, 60, 60, 196, 196, imagecolorallocate($im, 240, 200, 40));

        ob_start();
        imagejpeg($im, null, 80);
        $data = (string) ob_get_clean();
        imagedestroy($im);

        return base64_encode($data);
    }
}
