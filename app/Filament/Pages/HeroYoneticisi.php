<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\RestrictsToAdmins;
use App\Support\Hero;
use App\Support\Settings;
use App\Support\Tema;
use BackedEnum;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
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
 * Hero Yöneticisi (Vitrin — Faz P3): ana sayfanın üst bloğunun düzenini,
 * metinlerini, arka planını ve hangi alt blokların görüneceğini panelden
 * yönetir. Değerler `hero.*` site_settings anahtarlarında tutulur
 * (bkz. App\Support\Hero — ayrı tablo yerine bu seçimin gerekçesi orada).
 *
 * VİTRİN'E ÖZGÜDÜR: klasik tema hero metinlerini İçerik sayfasındaki
 * `home.hero_*` alanlarından almaya devam eder. Boş bırakılan alanlar
 * klasik değere düştüğü için bu sayfa hiç açılmadan da vitrin çalışır.
 */
class HeroYoneticisi extends Page
{
    use RestrictsToAdmins;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static string|UnitEnum|null $navigationGroup = 'İçerik & Tasarım (CMS)';

    protected static ?string $navigationLabel = 'Hero Yöneticisi';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.hero-yoneticisi';

    public ?array $data = [];

    public function getTitle(): string
    {
        return 'Hero Yöneticisi';
    }

    /** Blade'de "Vitrin aktif değil" uyarısını göstermek için. */
    public function vitrinAktifMi(): bool
    {
        return Settings::get('gorunum.tema', 'klasik') === 'vitrin';
    }

    public function mount(): void
    {
        $this->form->fill([
            'duzen' => Hero::duzen(),
            'rozet' => Settings::get('hero.rozet') ?: null,
            'baslik' => Settings::get('hero.baslik') ?: null,
            'vurgu' => Settings::get('hero.vurgu') ?: null,
            'alt_baslik' => Settings::get('hero.alt_baslik') ?: null,
            'cta1_etiket' => Settings::get('hero.cta1_etiket') ?: null,
            'cta1_url' => Settings::get('hero.cta1_url') ?: null,
            'cta2_aktif' => Settings::get('hero.cta2_aktif', '0') === '1',
            'cta2_etiket' => Settings::get('hero.cta2_etiket') ?: null,
            'cta2_url' => Settings::get('hero.cta2_url') ?: null,
            'arkaplan_tipi' => Hero::arkaplanTipi(),
            'gorsel_masaustu' => Settings::get('hero.gorsel_masaustu') ?: null,
            'gorsel_mobil' => Settings::get('hero.gorsel_mobil') ?: null,
            'video_url' => Settings::get('hero.video_url') ?: null,
            'overlay' => (string) Hero::overlay(),
            'odak' => Settings::get('hero.odak', 'merkez'),
            'bloklar' => Hero::blokFormDurumu(),
            'kampanya_aktif' => Settings::get('hero.kampanya_aktif', '0') === '1',
            'kampanya_ad' => Settings::get('hero.kampanya_ad') ?: null,
            'kampanya_baslangic' => Settings::get('hero.kampanya_baslangic') ?: null,
            'kampanya_bitis' => Settings::get('hero.kampanya_bitis') ?: null,
            'kampanya_rozet' => Settings::get('hero.kampanya_rozet') ?: null,
            'kampanya_baslik' => Settings::get('hero.kampanya_baslik') ?: null,
            'kampanya_vurgu' => Settings::get('hero.kampanya_vurgu') ?: null,
            'kampanya_alt_baslik' => Settings::get('hero.kampanya_alt_baslik') ?: null,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Düzen seçimi görsel kartlarla yapılır (Blade'deki secDuzen),
                // ama state şemanın içinde durur ki tek save() yolundan geçsin
                // ve fillForm ile test edilebilsin.
                Hidden::make('duzen'),

                Section::make('İçerik')
                    ->description('Boş bıraktığın alanlar İçerik sayfasındaki klasik hero metinlerine düşer.')
                    ->schema([
                        TextInput::make('rozet')
                            ->label('Rozet metni')
                            ->maxLength(60)
                            ->helperText('Başlığın üstündeki küçük çip. Ör: 🌍 Yurt dışındaki Türkler için'),

                        TextInput::make('baslik')
                            ->label('Başlık — 1. satır')
                            ->maxLength(70),

                        TextInput::make('vurgu')
                            ->label('Başlık — vurgulu satır')
                            ->maxLength(70)
                            ->helperText('Marka renginde görünür; asıl kancayı buraya koy.'),

                        Textarea::make('alt_baslik')
                            ->label('Alt başlık')
                            ->rows(2)
                            ->maxLength(140)
                            ->helperText('En fazla 140 karakter — mobilde ilk ekranda kalması için kısa tut.'),
                    ]),

                Section::make('Butonlar')
                    ->description('Kapalı buton sayfaya hiç basılmaz.')
                    ->schema([
                        TextInput::make('cta1_etiket')->label('Birincil buton — etiket')->maxLength(30),
                        TextInput::make('cta1_url')->label('Birincil buton — bağlantı')->helperText('Ör: /panel/ilan/yeni'),
                        Toggle::make('cta2_aktif')->label('İkincil butonu göster')->live(),
                        TextInput::make('cta2_etiket')->label('İkincil buton — etiket')->maxLength(30)->visible(fn ($get) => (bool) $get('cta2_aktif')),
                        TextInput::make('cta2_url')->label('İkincil buton — bağlantı')->visible(fn ($get) => (bool) $get('cta2_aktif')),
                    ]),

                Section::make('Arka plan')
                    ->description('Görsel/video seçilmezse hero mevcut degrade zeminle görünür.')
                    ->schema([
                        Select::make('arkaplan_tipi')
                            ->label('Arka plan tipi')
                            ->options(['yok' => 'Yok (degrade)', 'gorsel' => 'Görsel', 'video' => 'Video'])
                            ->default('yok')
                            ->live(),

                        FileUpload::make('gorsel_masaustu')
                            ->label('Görsel — masaüstü')
                            ->image()
                            ->disk('public')
                            ->directory('hero')
                            ->maxSize(4096)
                            ->imageEditor()
                            ->helperText('2400×1200 önerilir (webp).')
                            ->visible(fn ($get) => $get('arkaplan_tipi') === 'gorsel'),

                        FileUpload::make('gorsel_mobil')
                            ->label('Görsel — mobil (opsiyonel)')
                            ->image()
                            ->disk('public')
                            ->directory('hero')
                            ->maxSize(4096)
                            ->imageEditor()
                            ->helperText('Boşsa masaüstü görseli kullanılır. 1080×1080 önerilir.')
                            ->visible(fn ($get) => $get('arkaplan_tipi') === 'gorsel'),

                        TextInput::make('video_url')
                            ->label('Video bağlantısı')
                            ->helperText('mp4/webm dosyasının tam adresi. Sessiz ve döngülü oynatılır.')
                            ->visible(fn ($get) => $get('arkaplan_tipi') === 'video'),

                        TextInput::make('overlay')
                            ->label('Karartma yüzdesi')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->suffix('%')
                            ->helperText('Görsel/video üzerindeki metnin okunabilmesi için koyuluk. 0 = karartma yok.')
                            ->visible(fn ($get) => in_array($get('arkaplan_tipi'), ['gorsel', 'video'], true)),

                        Select::make('odak')
                            ->label('Görsel odak noktası')
                            ->options([
                                'sol-ust' => 'Sol üst', 'merkez-ust' => 'Üst', 'sag-ust' => 'Sağ üst',
                                'sol-merkez' => 'Sol', 'merkez' => 'Merkez', 'sag-merkez' => 'Sağ',
                                'sol-alt' => 'Sol alt', 'merkez-alt' => 'Alt', 'sag-alt' => 'Sağ alt',
                            ])
                            ->helperText('Görsel kırpılırken hangi bölge korunsun.')
                            ->visible(fn ($get) => $get('arkaplan_tipi') === 'gorsel'),
                    ]),

                Section::make('Zamanlanmış kampanya')
                    ->description('Belirlediğin tarih aralığında hero metinleri kampanya sürümüyle değişir; bitişte kendiliğinden normale döner (zamanlanmış işe gerek yok). Boş bıraktığın kampanya alanı normal değerini korur.')
                    ->collapsed(fn ($get) => ! (bool) $get('kampanya_aktif'))
                    ->schema([
                        Toggle::make('kampanya_aktif')
                            ->label('Kampanyayı etkinleştir')
                            ->helperText('Kapalıyken tarihler dolu olsa bile kampanya çalışmaz.')
                            ->live(),

                        TextInput::make('kampanya_ad')
                            ->label('Kampanya adı')
                            ->maxLength(60)
                            ->helperText('Yalnız panelde görünür — hangi kampanya olduğunu hatırlaman için. Ör: Kurban Bayramı')
                            ->visible(fn ($get) => (bool) $get('kampanya_aktif')),

                        DateTimePicker::make('kampanya_baslangic')
                            ->label('Başlangıç')
                            ->seconds(false)
                            ->helperText('Boş bırakılırsa hemen başlar.')
                            ->visible(fn ($get) => (bool) $get('kampanya_aktif')),

                        DateTimePicker::make('kampanya_bitis')
                            ->label('Bitiş')
                            ->seconds(false)
                            ->after('kampanya_baslangic')
                            ->helperText('Boş bırakılırsa elle kapatana kadar sürer.')
                            ->visible(fn ($get) => (bool) $get('kampanya_aktif')),

                        TextInput::make('kampanya_rozet')->label('Kampanya rozeti')->maxLength(60)
                            ->visible(fn ($get) => (bool) $get('kampanya_aktif')),
                        TextInput::make('kampanya_baslik')->label('Kampanya başlığı — 1. satır')->maxLength(70)
                            ->visible(fn ($get) => (bool) $get('kampanya_aktif')),
                        TextInput::make('kampanya_vurgu')->label('Kampanya başlığı — vurgulu satır')->maxLength(70)
                            ->visible(fn ($get) => (bool) $get('kampanya_aktif')),
                        Textarea::make('kampanya_alt_baslik')->label('Kampanya alt başlığı')->rows(2)->maxLength(140)
                            ->visible(fn ($get) => (bool) $get('kampanya_aktif')),
                    ]),

                Section::make('Bloklar')
                    ->description('Sürükleyerek sırala, anahtarla kapat. Kapanan blok sayfaya hiç basılmaz.')
                    ->schema([
                        Repeater::make('bloklar')
                            ->hiddenLabel()
                            ->schema([
                                Select::make('anahtar')
                                    ->label('Blok')
                                    ->options(collect(Hero::BLOKLAR)->map(fn (array $b) => $b[0])->all())
                                    ->disabled()
                                    ->dehydrated(),
                                Toggle::make('acik')->label('Göster'),
                            ])
                            ->columns(2)
                            ->reorderable()
                            ->addable(false)
                            ->deletable(false)
                            ->itemLabel(fn (array $state): ?string => Hero::BLOKLAR[$state['anahtar'] ?? ''][0] ?? null),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();

        $bloklar = collect($state['bloklar'] ?? [])
            ->filter(fn ($b) => is_array($b) && array_key_exists($b['anahtar'] ?? '', Hero::BLOKLAR))
            ->map(fn ($b) => ['anahtar' => $b['anahtar'], 'acik' => ! empty($b['acik'])])
            ->values()
            ->all();

        Settings::setMany([
            'hero.duzen' => in_array($state['duzen'] ?? '', Hero::DUZENLER, true) ? $state['duzen'] : 'bento',
            'hero.rozet' => $state['rozet'] ?? '',
            'hero.baslik' => $state['baslik'] ?? '',
            'hero.vurgu' => $state['vurgu'] ?? '',
            'hero.alt_baslik' => $state['alt_baslik'] ?? '',
            'hero.cta1_etiket' => $state['cta1_etiket'] ?? '',
            'hero.cta1_url' => $state['cta1_url'] ?? '',
            'hero.cta2_aktif' => ! empty($state['cta2_aktif']) ? '1' : '0',
            'hero.cta2_etiket' => $state['cta2_etiket'] ?? '',
            'hero.cta2_url' => $state['cta2_url'] ?? '',
            'hero.arkaplan_tipi' => $state['arkaplan_tipi'] ?? 'yok',
            'hero.gorsel_masaustu' => $state['gorsel_masaustu'] ?? '',
            'hero.gorsel_mobil' => $state['gorsel_mobil'] ?? '',
            'hero.video_url' => $state['video_url'] ?? '',
            'hero.overlay' => (string) max(0, min(100, (int) ($state['overlay'] ?? 58))),
            'hero.odak' => array_key_exists($state['odak'] ?? '', Hero::ODAKLAR) ? $state['odak'] : 'merkez',
            'hero.bloklar' => $bloklar === [] ? '' : json_encode($bloklar, JSON_UNESCAPED_UNICODE),
            'hero.kampanya_aktif' => ! empty($state['kampanya_aktif']) ? '1' : '0',
            'hero.kampanya_ad' => $state['kampanya_ad'] ?? '',
            'hero.kampanya_baslangic' => (string) ($state['kampanya_baslangic'] ?? ''),
            'hero.kampanya_bitis' => (string) ($state['kampanya_bitis'] ?? ''),
            'hero.kampanya_rozet' => $state['kampanya_rozet'] ?? '',
            'hero.kampanya_baslik' => $state['kampanya_baslik'] ?? '',
            'hero.kampanya_vurgu' => $state['kampanya_vurgu'] ?? '',
            'hero.kampanya_alt_baslik' => $state['kampanya_alt_baslik'] ?? '',
        ]);

        Notification::make()
            ->title('Hero ayarları kaydedildi')
            ->body($this->vitrinAktifMi()
                ? 'Değişiklik canlı sitede anında geçerli.'
                : 'Kaydedildi — Vitrin teması etkinleştirildiğinde geçerli olacak.')
            ->success()
            ->send();
    }

    /** Düzen kartlarından seçim (Blade'deki görsel seçici). */
    public function secDuzen(string $duzen): void
    {
        if (in_array($duzen, Hero::DUZENLER, true)) {
            $this->data['duzen'] = $duzen;
        }
    }

    /** Blade'de "şu an Vitrin mi" rozetini göstermek için. */
    public function aktifTema(): string
    {
        return Tema::aktif();
    }
}
