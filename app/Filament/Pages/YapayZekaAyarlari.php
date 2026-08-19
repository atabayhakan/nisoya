<?php

namespace App\Filament\Pages;

use App\Services\Ai\AiManager;
use App\Support\Settings;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
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
                    ->description('Fotoğrafla hızlı ilan özelliği için kullanılacak yapay zeka. Anahtarı girdiğinde özellik anında aktifleşir — sunucuya erişmene gerek yok.')
                    ->columns(2)
                    ->schema([
                        Select::make('saglayici')
                            ->label('Sağlayıcı')
                            ->options([
                                'openrouter' => 'OpenRouter (tek uçtan yüzlerce model — önerilen)',
                                'openai' => 'OpenAI',
                                'anthropic' => 'Anthropic (Claude)',
                                'gemini' => 'Google Gemini',
                            ])
                            ->required()
                            ->native(false)
                            ->helperText('OpenRouter tek API anahtarıyla birçok modele erişim verir.'),

                        TextInput::make('model')
                            ->label('Model')
                            ->placeholder('openai/gpt-4o-mini')
                            ->helperText('⚠️ MUTLAKA görüntü (vision) destekleyen bir model seç — özellik fotoğraf gönderir. Önerilen: openai/gpt-4o-mini (ucuz, güvenilir, doğrulandı) veya google/gemini-2.0-flash-001 (çok ucuz). "tencent/hy3:free" gibi metin-only modeller ÇALIŞMAZ. Boş bırakırsan varsayılan (openai/gpt-4o-mini) kullanılır.'),

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
            $hint = str_contains(mb_strtolower($error), 'image')
                ? ' → Bu model görüntü (vision) desteklemiyor. Görüntü destekleyen bir model seç (ör. openai/gpt-4o-mini, google/gemini-2.0-flash-001).'
                : '';

            Notification::make()
                ->title('Bağlantı kurulamadı')
                ->body($error.$hint)
                ->danger()
                ->persistent()
                ->send();
        }
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
