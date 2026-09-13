<?php

declare(strict_types=1);

namespace App\Filament\Resources\JobListings\Schemas;

use App\Enums\EmploymentType;
use App\Enums\ExperienceLevel;
use App\Enums\JobStatus;
use App\Enums\SalaryPeriod;
use App\Models\Country;
use App\Models\Currency;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class JobListingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('İlan & Kurum Bilgileri')
                    ->description('İşveren şirket, pozisyon başlığı ve temel kategori seçimi.')
                    ->columns(2)
                    ->schema([
                        Select::make('company_id')
                            ->label('İşveren Şirket')
                            ->relationship('company', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Select::make('job_category_id')
                            ->label('Kategori')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload(),

                        TextInput::make('title')
                            ->label('Pozisyon / İlan Başlığı')
                            ->placeholder('örn. Senior PHP / Laravel Geliştirici')
                            ->required()
                            ->maxLength(200)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (string $operation, ?string $state, callable $set): void {
                                if ($operation === 'create' && filled($state)) {
                                    $set('slug', Str::slug($state));
                                }
                            }),

                        TextInput::make('slug')
                            ->label('Kısa Ad (URL Slug)')
                            ->required()
                            ->maxLength(200)
                            ->unique(ignoreRecord: true)
                            ->prefix('nisoya.com/is/'),

                        TextInput::make('positions')
                            ->label('Açık Pozisyon (Kontenjan)')
                            ->numeric()
                            ->default(1)
                            ->minValue(1)
                            ->maxValue(100),
                    ]),

                Section::make('Çalışma Şekli & Nitelikler')
                    ->description('Çalışma modeli, deneyim seviyesi ve uzaktan çalışma imkanı.')
                    ->columns(3)
                    ->schema([
                        Select::make('employment_type')
                            ->label('Çalışma Tipi')
                            ->options(EmploymentType::class)
                            ->required(),

                        Select::make('experience_level')
                            ->label('Deneyim Seviyesi')
                            ->options(ExperienceLevel::class),

                        Toggle::make('is_remote')
                            ->label('Uzaktan (Remote / Evden) Çalışma')
                            ->inline(false),
                    ]),

                Section::make('Maaş & Ücretlendirme')
                    ->description('Adaylar için şeffaf ücret aralığı ve para birimi.')
                    ->columns(4)
                    ->schema([
                        TextInput::make('salary_min')
                            ->label('Minimum Maaş')
                            ->numeric()
                            ->prefix('Min'),

                        TextInput::make('salary_max')
                            ->label('Maksimum Maaş')
                            ->numeric()
                            ->prefix('Max'),

                        Select::make('salary_currency')
                            ->label('Para Birimi')
                            ->options(fn () => Currency::query()->where('is_active', true)->orderBy('sort_order')->pluck('code', 'code')->toArray())
                            ->default('EUR'),

                        Select::make('salary_period')
                            ->label('Maaş Periyodu')
                            ->options(SalaryPeriod::class)
                            ->default('aylik'),
                    ]),

                Section::make('Lokasyon & Takvim')
                    ->description('İşin yapılacağı konum ve son başvuru tarihi.')
                    ->columns(3)
                    ->schema([
                        Select::make('country_code')
                            ->label('Ülke')
                            ->options(fn () => Country::query()->where('is_active', true)->orderBy('sort_order')->pluck('name_tr', 'code')->toArray())
                            ->searchable()
                            ->preload(),

                        TextInput::make('city')
                            ->label('Şehir')
                            ->placeholder('örn. Köln, Berlin, Londra')
                            ->maxLength(100),

                        DateTimePicker::make('deadline')
                            ->label('Son Başvuru Tarihi')
                            ->helperText('Boş bırakılırsa süresiz açık kalır.'),
                    ]),

                Section::make('İlan Detayı & Görev Tanımı')
                    ->description('Adayların göreceği iş tanımı, aranan nitelikler ve yan haklar.')
                    ->schema([
                        Textarea::make('description')
                            ->label('Pozisyon Açıklaması & Nitelikler')
                            ->rows(6)
                            ->required()
                            ->placeholder('İş tanımı, aranan tecrübe, yabancı dil şartı ve sunulan imkanlar...')
                            ->columnSpanFull(),
                    ]),

                Section::make('Yayın Durumu & Vitrin')
                    ->description('İlanın sitedeki görünürlüğü ve öne çıkarma ayarları.')
                    ->columns(3)
                    ->schema([
                        Select::make('status')
                            ->label('Yayın Durumu')
                            ->options(JobStatus::class)
                            ->default(JobStatus::Aktif)
                            ->required(),

                        Toggle::make('is_featured')
                            ->label('Öne Çıkan İlan (Vitrin)')
                            ->inline(false)
                            ->helperText('İlanı listelerin en üstünde sarı rozetle gösterir.'),

                        DateTimePicker::make('featured_until')
                            ->label('Öne Çıkarma Bitiş Tarihi'),
                    ]),
            ]);
    }
}
