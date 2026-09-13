<?php

declare(strict_types=1);

namespace App\Filament\Resources\Companies\Schemas;

use App\Models\Country;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class CompanyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Kurumsal Kimlik')
                    ->description('Şirketin temel unvanı, yöneticisi ve logo bilgileri.')
                    ->columns(2)
                    ->schema([
                        Select::make('user_id')
                            ->label('Şirket Sahibi (Yönetici Üye)')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->helperText('Bir kullanıcı yalnızca bir kurumsal şirket profilinin sahibi olabilir.'),

                        TextInput::make('name')
                            ->label('Şirket Adı')
                            ->required()
                            ->maxLength(150)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (string $operation, ?string $state, callable $set): void {
                                if ($operation === 'create' && filled($state)) {
                                    $set('slug', Str::slug($state));
                                }
                            }),

                        TextInput::make('slug')
                            ->label('Kısa Ad (URL Slug)')
                            ->required()
                            ->maxLength(150)
                            ->unique(ignoreRecord: true)
                            ->prefix('nisoya.com/sirket/'),

                        TextInput::make('tagline')
                            ->label('Kurumsal Slogan')
                            ->placeholder('örn. Avrupa Türk Lojistiğinde Güvenli Çözümler')
                            ->maxLength(180),

                        FileUpload::make('logo_path')
                            ->label('Şirket Logosu')
                            ->image()
                            ->disk('public')
                            ->directory('company-logos')
                            ->maxSize(2048)
                            ->columnSpanFull()
                            ->helperText('Şeffaf PNG, WebP veya JPG formatında logo (Maksimum 2 MB).'),
                    ]),

                Section::make('Faaliyet & Büyüklük')
                    ->description('Sektörel uzmanlık, çalışan sayısı ve şirket geçmişi.')
                    ->columns(3)
                    ->schema([
                        TextInput::make('sector')
                            ->label('Sektör')
                            ->placeholder('örn. Bilişim, Lojistik, İnşaat, Gastronomi')
                            ->maxLength(100)
                            ->datalist([
                                'Bilişim & Yazılım',
                                'Lojistik & Taşımacılık',
                                'İnşaat & Gayrimenkul',
                                'Gastronomi & Restoran',
                                'Danışmanlık & Finans',
                                'Sağlık & Medikal',
                                'Otomotiv & Servis',
                                'Tekstil & Moda',
                                'Turizm & Seyahat',
                                'Eğitim & Tercümanlık',
                            ]),

                        Select::make('company_size')
                            ->label('Çalışan Sayısı')
                            ->options([
                                '1-10' => '1 - 10 Çalışan',
                                '11-50' => '11 - 50 Çalışan',
                                '51-200' => '51 - 200 Çalışan',
                                '201-500' => '201 - 500 Çalışan',
                                '500+' => '500+ Çalışan (Büyük Ölçekli)',
                            ]),

                        TextInput::make('founded_year')
                            ->label('Kuruluş Yılı')
                            ->numeric()
                            ->minValue(1900)
                            ->maxValue((int) date('Y'))
                            ->placeholder((string) date('Y')),

                        Textarea::make('about')
                            ->label('Şirket Hakkında (Detaylı Tanıtım)')
                            ->rows(4)
                            ->maxLength(5000)
                            ->placeholder('Şirketin sunduğu hizmetler, vizyonu ve çalışma kültürü...')
                            ->columnSpanFull(),
                    ]),

                Section::make('İletişim & Lokasyon')
                    ->description('Fiziki adres, web sitesi ve video bağlantıları.')
                    ->columns(2)
                    ->schema([
                        Select::make('country_code')
                            ->label('Ülke')
                            ->options(fn () => Country::query()->where('is_active', true)->orderBy('sort_order')->pluck('name_tr', 'code')->toArray())
                            ->searchable()
                            ->preload(),

                        TextInput::make('city')
                            ->label('Şehir')
                            ->placeholder('örn. Berlin, Londra, İstanbul')
                            ->maxLength(100),

                        TextInput::make('address')
                            ->label('Açık Adres')
                            ->placeholder('Merkez ofis / cadde / bina no')
                            ->maxLength(255)
                            ->columnSpanFull(),

                        TextInput::make('website')
                            ->label('Web Sitesi')
                            ->url()
                            ->placeholder('https://www.ornek.com')
                            ->maxLength(255),

                        TextInput::make('video_url')
                            ->label('Tanıtım Videosu (YouTube / Vimeo)')
                            ->url()
                            ->placeholder('https://www.youtube.com/watch?v=...')
                            ->maxLength(255),
                    ]),

                Section::make('Sosyal Medya Kanalları')
                    ->description('Adayların ve müşterilerin ulaşabileceği resmi hesaplar.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('social_linkedin')
                            ->label('LinkedIn')
                            ->url()
                            ->placeholder('https://linkedin.com/company/...'),

                        TextInput::make('social_instagram')
                            ->label('Instagram')
                            ->placeholder('kullanici_adi veya profil linki'),

                        TextInput::make('social_whatsapp')
                            ->label('WhatsApp İletişim Hattı')
                            ->placeholder('wa.me/49151... veya telefon no'),

                        TextInput::make('social_twitter')
                            ->label('X (Twitter)')
                            ->url()
                            ->placeholder('https://x.com/...'),
                    ]),

                Section::make('Kurumsal Doğrulama & Güvenilirlik')
                    ->description('Platform yöneticisi tarafından verilen resmi onay rozeti.')
                    ->schema([
                        Toggle::make('is_verified')
                            ->label('Doğrulanmış Kurumsal Şirket (Mavi Rozet)')
                            ->helperText('Açık olduğunda şirket profiline ve yayınladığı iş ilanlarına "✓ Doğrulanmış Kurumsal" rozeti eklenir.')
                            ->default(false),
                    ]),
            ]);
    }
}
