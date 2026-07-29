<?php

namespace App\Filament\Pages;

use App\Support\Settings;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * Kâhya ayarları — rapor alıcısı ve saati.
 *
 * DB'ye yazılır, `AppServiceProvider::mergeKahyaConfig()` ile `config('kahya.*')`
 * runtime'da override edilir. Değişiklik ANINDA geçerli olur; config:cache ya
 * da SSH gerekmez (YapayZekaAyarlari ile aynı desen).
 *
 * SAAT LİSTESİ SINIRLI: serbest metin yerine seçim, çünkü `dailyAt()` geçersiz
 * bir değerde sessizce çalışmaz — zamanlayıcı hatası da sessiz bir hatadır ve
 * bu sistemin bütün amacı sessiz hataları görünür kılmak.
 *
 * 04:00 ÖNCESİ SEÇENEK YOK: 03:30 medya temizliği ve 04:00 yedek bu saatlerde
 * koşuyor; rapor onlardan önce çalışırsa "son yedek" durumunu yanlış gösterir.
 */
class KahyaAyarlari extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|UnitEnum|null $navigationGroup = 'Sistem & Araçlar';

    protected static ?string $navigationLabel = 'Kâhya Ayarları';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.kahya-ayarlari';

    /** @var array<string, mixed> */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public function getTitle(): string
    {
        return 'Kâhya Ayarları';
    }

    public function mount(): void
    {
        $this->form->fill([
            'alici' => Settings::get('kahya.alici') ?: '',
            'rapor_saati' => Settings::get('kahya.rapor_saati') ?: config('kahya.rapor_saati', '07:30'),
            'sohbet_modeli' => Settings::get('kahya.sohbet_modeli') ?: '',
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Günlük rapor')
                    ->description('Kâhya her sabah sitenin durumunu özetleyip e-posta gönderir: ne oldu, seni bekleyen işler, ne bozuk, ne eksik.')
                    ->schema([
                        TextInput::make('alici')
                            ->label('Raporun gideceği e-posta')
                            ->email()
                            ->placeholder(Settings::get('iletisim.eposta') ?: 'iletisim adresine gönderilir')
                            ->helperText('Boş bırakırsan İletişim sayfasındaki adrese gider.')
                            ->columnSpanFull(),

                        Select::make('rapor_saati')
                            ->label('Gönderim saati (sunucu saati, UTC)')
                            ->options([
                                '05:00' => '05:00',
                                '06:00' => '06:00',
                                '07:00' => '07:00',
                                '07:30' => '07:30 (önerilen)',
                                '08:00' => '08:00',
                                '09:00' => '09:00',
                                '12:00' => '12:00',
                                '20:00' => '20:00',
                            ])
                            ->required()
                            ->helperText('04:00\'ten önce seçenek yok: yedekleme 04:00\'te koşuyor ve rapor "son yedek" durumunu doğru göstermeli.')
                            ->columnSpanFull(),
                    ]),

                Section::make('Sohbet')
                    ->description('Kâhya ile Konuş sayfasında eylem seçimini yapan model. '
                        .'Boş bırakılırsa Yapay Zekâ Ayarları\'ndaki varsayılan model kullanılır.')
                    ->schema([
                        TextInput::make('sohbet_modeli')
                            ->label('Sohbet modeli (isteğe bağlı)')
                            ->placeholder('örn. anthropic/claude-sonnet-4.5')
                            // Doğru işi seçmek yanlış işi seçmekten çok daha
                            // ucuz: hata bedeli canlıda ödenir. Bu yüzden buraya
                            // varsayılandan güçlü bir model yazmak mantıklı.
                            ->helperText('Sağlayıcı Yapay Zekâ Ayarları\'ndaki sağlayıcıdır; burası yalnız model adını değiştirir. '
                                .'Eylem seçimi için varsayılandan güçlü bir model önerilir.')
                            ->maxLength(120)
                            ->columnSpanFull(),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();

        Settings::setMany([
            'kahya.alici' => trim((string) ($state['alici'] ?? '')),
            'kahya.rapor_saati' => $state['rapor_saati'] ?? '07:30',
            'kahya.sohbet_modeli' => trim((string) ($state['sohbet_modeli'] ?? '')),
        ]);

        Notification::make()
            ->title('Kâhya ayarları kaydedildi')
            ->body('Değişiklik anında geçerli. Denemek için Kâhya sayfasındaki "Şimdi rapor gönder" düğmesini kullanabilirsin.')
            ->success()
            ->send();
    }
}
