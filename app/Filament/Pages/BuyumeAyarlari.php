<?php

namespace App\Filament\Pages;

use App\Services\Growth\Discovery\GooglePlacesDiscoverySource;
use App\Support\Settings;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * Büyüme Ajanı ayarları — Google Places anahtarı buradan girilir. Anahtar
 * DB'ye (site_settings) yazılır ve AppServiceProvider::mergeGrowthConfig() ile
 * config('growth.*') runtime'da override edilir → gerçek keşif ANINDA aktif,
 * config:cache/SSH gerekmez (Yapay Zeka ayarlarıyla aynı desen). Yalnızca Admin.
 */
class BuyumeAyarlari extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGlobeAlt;

    protected static string|UnitEnum|null $navigationGroup = 'Sistem & Araçlar';

    protected static ?string $navigationLabel = 'Büyüme Ajanı';

    protected static ?int $navigationSort = 8;

    protected string $view = 'filament.pages.buyume-ayarlari';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public function getTitle(): string
    {
        return 'Büyüme Ajanı Ayarları';
    }

    public function mount(): void
    {
        $this->form->fill([
            'google_places_api_key' => Settings::get('growth.google_places_api_key') ?: '',
        ]);
    }

    public function form(Schema $schema): Schema
    {
        $allowlist = implode(', ', array_map('strtoupper', (array) config('growth.sending_allowlist', [])));

        return $schema
            ->components([
                Section::make('Google Places (keşif kaynağı)')
                    ->description('Anahtar girilince ajan GERÇEK Google Places verisiyle Türk işletme keşfeder. Boş bırakırsan güvenli demo (fixture) kaynağı kullanılır — hiçbir dış çağrı yapılmaz. Anahtarı Google Cloud Console → Keys & Credentials\'tan alırsın; "Places API (New)" etkin ve faturalandırma açık olmalı.')
                    ->schema([
                        TextInput::make('google_places_api_key')
                            ->label('Google Places API anahtarı')
                            ->password()
                            ->revealable()
                            ->autocomplete('new-password')
                            ->helperText('⚠️ Maliyet: Places arama çağrı başına ücretlidir; Google Cloud\'da bütçe/kota limiti koy. Anahtar sunucuda güvenle saklanır, kimseye gösterilmez.')
                            ->columnSpanFull(),
                    ]),

                Section::make('Gönderim bölgesi (bilgi)')
                    ->description('Keşif her ülkede yapılabilir; ticari e-posta yalnızca izinli bölgelere gider. AB-27, Türkiye ve Rusya her zaman engellidir (hukuki). Bu liste GROWTH_SENDING_ALLOWLIST ile ayarlanır.')
                    ->schema([
                        TextInput::make('allowlist_bilgi')
                            ->label('Gönderim izinli ülkeler (salt-okunur)')
                            ->default($allowlist ?: '—')
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpanFull(),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();

        Settings::setMany([
            'growth.google_places_api_key' => $state['google_places_api_key'] ?? '',
        ]);

        Notification::make()
            ->title('Büyüme Ajanı ayarları kaydedildi')
            ->body(filled($state['google_places_api_key'] ?? null)
                ? 'Gerçek Google Places keşfi anında aktif.'
                : 'Anahtar boş — güvenli demo (fixture) kaynağı kullanılacak.')
            ->success()
            ->send();
    }

    /** Girilen anahtarla gerçek bir Places araması yapıp çalıştığını doğrular. */
    public function testEt(): void
    {
        $state = $this->form->getState();
        $key = $state['google_places_api_key'] ?? '';

        if (blank($key)) {
            Notification::make()
                ->title('Anahtar girilmemiş')
                ->body('Önce API anahtarını gir.')
                ->warning()
                ->send();

            return;
        }

        $result = (new GooglePlacesDiscoverySource($key))->probe();

        if ($result['ok']) {
            Notification::make()
                ->title('Bağlantı başarılı ✓')
                ->body($result['message'])
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('Bağlantı kurulamadı')
                ->body($result['message'].' → "Places API (New)" etkin mi, faturalandırma açık mı ve anahtar IP kısıtlaması (sunucu 72.62.115.3) doğru mu kontrol et.')
                ->danger()
                ->persistent()
                ->send();
        }
    }
}
