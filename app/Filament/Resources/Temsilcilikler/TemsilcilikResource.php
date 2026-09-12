<?php

declare(strict_types=1);

namespace App\Filament\Resources\Temsilcilikler;

use App\Filament\Concerns\RestrictsToAdmins;
use App\Filament\Resources\TemsilcilikIslemleri\TemsilcilikIslemiResource;
use App\Filament\Resources\Temsilcilikler\Pages\CreateTemsilcilik;
use App\Filament\Resources\Temsilcilikler\Pages\EditTemsilcilik;
use App\Filament\Resources\Temsilcilikler\Pages\ListTemsilcilikler;
use App\Models\Country;
use App\Models\Temsilcilik;
use App\Models\TemsilcilikIslemi;
use App\Services\Ai\CountryGuideAiAssistant;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use UnitEnum;

/**
 * Ülke Rehberi — dış temsilcilikler (büyükelçilik/başkonsolosluk).
 *
 * Slug URL'nin ikinci segmentidir (/de/koeln — tasarım K2); değiştirmek
 * yayındaki linkleri kırar, o yüzden düzenlemede kilitli değil ama uyarılı.
 */
class TemsilcilikResource extends Resource
{
    use RestrictsToAdmins;

    protected static ?string $model = Temsilcilik::class;

    // Filament'in İngilizce çoğullaştırıcısı Türkçe addan saçma slug türetir
    // (bkz. kahya-hafiza dersi) — sabitle.
    protected static ?string $slug = 'temsilcilikler';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingLibrary;

    protected static string|UnitEnum|null $navigationGroup = 'Ülke Rehberi';

    protected static ?string $navigationLabel = 'Temsilcilikler';

    protected static ?int $navigationSort = 1;

    public static function getModelLabel(): string
    {
        return 'Temsilcilik';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Temsilcilikler';
    }

    public static function getNavigationBadge(): ?string
    {
        $count = Temsilcilik::query()->where('is_active', true)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Yayındaki aktif dış temsilcilik sayısı';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Kimlik')
                ->columns(2)
                ->schema([
                    Select::make('country_code')
                        ->label('Ülke')
                        ->options(fn () => Country::query()->orderBy('sort_order')
                            ->get()
                            ->mapWithKeys(fn (Country $c) => [$c->code => ($c->emoji ? $c->emoji.' ' : '').$c->name_tr])
                        )
                        ->required()
                        ->native(false)
                        ->searchable(),
                    Select::make('tur')
                        ->label('Tür')
                        ->options([
                            Temsilcilik::TUR_BUYUKELCILIK => '🏛️ Büyükelçilik',
                            Temsilcilik::TUR_BASKONSOLOSLUK => '🏢 Başkonsolosluk',
                        ])
                        ->default(Temsilcilik::TUR_BASKONSOLOSLUK)
                        ->required(),
                    TextInput::make('ad')
                        ->label('Temsilcilik Adı')
                        ->placeholder('örn. Köln Başkonsolosluğu')
                        ->required()
                        ->maxLength(190)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                            if (blank($get('slug')) && filled($get('sehir'))) {
                                $set('slug', Str::slug((string) $get('sehir')));
                            }
                        }),
                    TextInput::make('sehir')
                        ->label('Şehir')
                        ->placeholder('örn. Köln')
                        ->required()
                        ->maxLength(120)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                            if (blank($get('slug'))) {
                                $set('slug', Str::slug((string) $state));
                            }
                        }),
                    TextInput::make('slug')
                        ->label('Kısa ad (URL Segmenti)')
                        ->required()
                        ->maxLength(80)
                        ->helperText('Örnek: nisoya.com/{ülke}/{kısa-ad} — yayına girdikten sonra değiştirmek dış linkleri kırar.'),
                ]),

            Section::make('İletişim & Konum')
                ->columns(2)
                ->schema([
                    TextInput::make('adres')
                        ->label('Fiziksel Adres')
                        ->placeholder('örn. Luxemburger Str. 285, 50939 Köln')
                        ->maxLength(500)
                        ->columnSpanFull(),
                    TextInput::make('resmi_url')
                        ->label('Resmî Web Portalı')
                        ->placeholder('örn. https://koln.bk.mfa.gov.tr')
                        ->url()
                        ->maxLength(300)
                        ->columnSpanFull()
                        ->helperText('Dışişleri Bakanlığı veya konsolosluk.gov.tr resmi adresi.'),
                    TextInput::make('latitude')
                        ->label('Enlem (GPS Latitude)')
                        ->placeholder('örn. 50.9167')
                        ->numeric()
                        ->helperText('Girildiğinde sitede Harita butonu ve navigasyon bağlantıları otomatik açılır.'),
                    TextInput::make('longitude')
                        ->label('Boylam (GPS Longitude)')
                        ->placeholder('örn. 6.9242')
                        ->numeric(),
                ]),

            Section::make('Özel Notlar & Randevu Rehberi')
                ->headerActions([
                    Action::make('aiNotUret')
                        ->label('AI ile Yönlendirme Notu Üret')
                        ->icon(Heroicon::OutlinedSparkles)
                        ->color('primary')
                        ->action(function (callable $get, callable $set, CountryGuideAiAssistant $assistant): void {
                            $ad = (string) $get('ad');
                            $sehir = (string) $get('sehir');
                            $ulke = (string) $get('country_code');
                            if (blank($ad) || blank($ulke)) {
                                Notification::make()->title('Lütfen önce temsilcilik adı ve ülkeyi doldurun')->warning()->send();

                                return;
                            }
                            $rehber = $assistant->generateMissionGuidance($ad, $sehir, $ulke);
                            $set('yonlendirme_notu', $rehber['yonlendirme_notu']);
                            if (blank($get('resmi_url')) && filled($rehber['oneri_resmi_url'])) {
                                $set('resmi_url', $rehber['oneri_resmi_url']);
                            }
                            Notification::make()->title('AI yönlendirme ve randevu taslağı yüklendi')->success()->send();
                        }),
                ])
                ->schema([
                    Textarea::make('yonlendirme_notu')
                        ->label('Ziyaretçi Yönlendirme ve Randevu Notu')
                        ->placeholder('Randevu zorunluluğu, mesai saatleri ve başvuru kuralları...')
                        ->rows(4)
                        ->helperText('Bu temsilcilik için özel işlem rehberi içeriği bulunmadığında sayfada ziyaretçiye gösterilir.')
                        ->columnSpanFull(),
                ]),

            Section::make('Yayın Durumu')
                ->columns(2)
                ->schema([
                    Toggle::make('is_active')
                        ->label('Aktif (Rehberde ve aramalarda göster)')
                        ->default(true),
                    TextInput::make('sort_order')
                        ->label('Sıralama Önceliği')
                        ->numeric()
                        ->default(0),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('country')->withCount('islemler'))
            ->columns([
                TextColumn::make('ad')
                    ->label('Temsilcilik')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->icon(fn (Temsilcilik $record): BackedEnum => $record->tur === Temsilcilik::TUR_BUYUKELCILIK
                        ? Heroicon::OutlinedBuildingLibrary
                        : Heroicon::OutlinedBuildingOffice2
                    )
                    ->iconColor(fn (Temsilcilik $record): string => $record->tur === Temsilcilik::TUR_BUYUKELCILIK ? 'primary' : 'info')
                    ->description(fn (Temsilcilik $record): string => $record->turEtiketi().($record->sehir ? ' • '.$record->sehir : '')),

                TextColumn::make('country_code')
                    ->label('Ülke')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(function (string $state, Temsilcilik $record): string {
                        $country = $record->country;
                        if ($country) {
                            return ($country->emoji ? $country->emoji.' ' : '').$country->name_tr;
                        }

                        return $state;
                    })
                    ->sortable(),

                TextColumn::make('sehir')
                    ->label('Şehir')
                    ->searchable()
                    ->sortable()
                    ->icon(Heroicon::OutlinedMapPin)
                    ->iconColor('gray'),

                TextColumn::make('slug')
                    ->label('Canlı URL')
                    ->formatStateUsing(fn (Temsilcilik $r): string => '/'.strtolower($r->country_code).'/'.$r->slug)
                    ->color('primary')
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->iconPosition('after')
                    ->copyable()
                    ->copyMessage('Sayfa adresi kopyalandı')
                    ->url(fn (Temsilcilik $r): string => url('/'.strtolower($r->country_code).'/'.$r->slug))
                    ->openUrlInNewTab()
                    ->tooltip('Canlı sayfayı sitede görüntüle veya kopyala'),

                TextColumn::make('resmi_url')
                    ->label('Resmî Portal')
                    ->icon(Heroicon::OutlinedGlobeAlt)
                    ->formatStateUsing(function (?string $state): string {
                        if (blank($state)) {
                            return 'Tanımsız';
                        }
                        $host = parse_url($state, PHP_URL_HOST);

                        return $host ? str_replace('www.', '', (string) $host) : 'Site';
                    })
                    ->badge()
                    ->color(fn (?string $state): string => filled($state) ? 'info' : 'gray')
                    ->url(fn (?string $state): ?string => filled($state) ? $state : null)
                    ->openUrlInNewTab()
                    ->tooltip('Dışişleri Bakanlığı resmi portalı')
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('islemler_count')
                    ->label('Rehber İşlemleri')
                    ->counts('islemler')
                    ->badge()
                    ->color(fn (int $state): string => $state > 0 ? 'success' : 'warning')
                    ->icon(Heroicon::OutlinedDocumentText)
                    ->url(fn (Temsilcilik $r): string => TemsilcilikIslemiResource::getUrl('index', [
                        'tableFilters' => ['temsilcilik_id' => ['value' => $r->id]],
                    ]))
                    ->tooltip('Bu temsilciliğe ait konsolosluk işlemlerini filtrele')
                    ->sortable(),

                ToggleColumn::make('is_active')
                    ->label('Aktif')
                    ->tooltip('Rehberde ve aramalarda göster'),
            ])
            ->filters([
                SelectFilter::make('country_code')
                    ->label('Ülke')
                    ->options(fn () => Country::query()->orderBy('sort_order')
                        ->get()
                        ->mapWithKeys(fn (Country $c) => [$c->code => ($c->emoji ? $c->emoji.' ' : '').$c->name_tr])
                    )
                    ->searchable(),

                SelectFilter::make('tur')
                    ->label('Temsilcilik Türü')
                    ->options([
                        Temsilcilik::TUR_BUYUKELCILIK => '🏛️ Büyükelçilik',
                        Temsilcilik::TUR_BASKONSOLOSLUK => '🏢 Başkonsolosluk',
                    ]),

                TernaryFilter::make('is_active')
                    ->label('Yayın Durumu')
                    ->placeholder('Tümü')
                    ->trueLabel('Yalnızca Aktif')
                    ->falseLabel('Yalnızca Pasif'),

                Filter::make('koordinat_var')
                    ->label('Harita / Navigasyon Destekli')
                    ->query(fn ($query) => $query->whereNotNull('latitude')->whereNotNull('longitude')),
            ])
            ->recordActions([
                Action::make('canli_sayfa')
                    ->label('Canlı Sayfa')
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->color('gray')
                    ->tooltip('Sitedeki sayfayı yeni sekmede aç')
                    ->url(fn (Temsilcilik $record): string => url('/'.strtolower($record->country_code).'/'.$record->slug))
                    ->openUrlInNewTab(),

                Action::make('islemler')
                    ->label('İşlemler')
                    ->icon(Heroicon::OutlinedDocumentText)
                    ->color('info')
                    ->tooltip('Bu temsilciliğin konsolosluk işlemlerine git')
                    ->url(fn (Temsilcilik $record): string => TemsilcilikIslemiResource::getUrl('index', [
                        'tableFilters' => ['temsilcilik_id' => ['value' => $record->id]],
                    ])),

                Action::make('aiDenetim')
                    ->label('AI Denetim')
                    ->icon(Heroicon::OutlinedSparkles)
                    ->color('primary')
                    ->tooltip('Temsilcilik ve rehber kapsamını yapay zekâ ile denetle')
                    ->modalHeading(fn (Temsilcilik $record): string => $record->ad.' — Kalite & Kapsam Denetimi')
                    ->modalDescription(function (Temsilcilik $record): HtmlString {
                        $toplamIslem = $record->islemler()->count();
                        $yayindaIslem = $record->islemler()->where('status', TemsilcilikIslemi::STATUS_YAYIN)->count();

                        /** @var CountryGuideAiAssistant $assistant */
                        $assistant = app(CountryGuideAiAssistant::class);
                        $rapor = $assistant->auditMission(
                            missionName: $record->ad,
                            countryCode: $record->country_code,
                            address: $record->adres,
                            latitude: $record->latitude !== null ? (float) $record->latitude : null,
                            longitude: $record->longitude !== null ? (float) $record->longitude : null,
                            officialUrl: $record->resmi_url,
                            proceduresCount: $toplamIslem,
                            publishedCount: $yayindaIslem
                        );

                        $puan = $rapor['puan'];
                        $durum = $rapor['durum'];
                        $puanColor = $puan >= 75 ? 'text-emerald-700 dark:text-emerald-400' : 'text-amber-700 dark:text-amber-400';

                        $guclulerHtml = '';
                        foreach ($rapor['guclu_yonler'] as $g) {
                            $guclulerHtml .= "<li class='text-xs text-gray-700 dark:text-gray-300'>✓ {$g}</li>";
                        }

                        $eksiklerHtml = '';
                        foreach ($rapor['eksikler'] as $e) {
                            $eksiklerHtml .= "<li class='text-xs text-amber-800 dark:text-amber-300'>! {$e}</li>";
                        }

                        return new HtmlString(
                            "<div class='space-y-3 text-sm p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700'>"
                            ."<div class='flex items-center justify-between border-b border-gray-200 dark:border-gray-700 pb-2'>"
                            ."<span class='font-semibold text-gray-900 dark:text-gray-100'>{$durum}</span>"
                            ."<span class='font-bold text-base {$puanColor}'>{$puan} / 100</span>"
                            .'</div>'
                            .($guclulerHtml ? "<div class='space-y-1'><div class='text-xs font-semibold text-gray-800 dark:text-gray-200'>Eksiksiz Alanlar:</div><ul class='space-y-0.5'>{$guclulerHtml}</ul></div>" : '')
                            .($eksiklerHtml ? "<div class='space-y-1 pt-1'><div class='text-xs font-semibold text-amber-700 dark:text-amber-400'>Tamamlanması Gerekenler:</div><ul class='space-y-0.5'>{$eksiklerHtml}</ul></div>" : '')
                            .'</div>'
                        );
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Kapat'),

                Action::make('duzenle')
                    ->label('Düzenle')
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->color('primary')
                    ->url(fn (Temsilcilik $record): string => static::getUrl('edit', ['record' => $record])),

                ActionGroup::make([
                    Action::make('harita')
                        ->label('Haritada Aç')
                        ->icon(Heroicon::OutlinedMapPin)
                        ->color('gray')
                        ->visible(fn (Temsilcilik $record): bool => $record->latitude !== null && $record->longitude !== null)
                        ->url(fn (Temsilcilik $record): ?string => $record->haritaBaglantilari()->first()['url'] ?? null)
                        ->openUrlInNewTab(),

                    Action::make('resmi_site')
                        ->label('Resmî Siteye Git')
                        ->icon(Heroicon::OutlinedGlobeAlt)
                        ->color('gray')
                        ->visible(fn (Temsilcilik $record): bool => filled($record->resmi_url))
                        ->url(fn (Temsilcilik $record): ?string => $record->resmi_url)
                        ->openUrlInNewTab(),
                ]),
            ])
            ->defaultSort('sort_order');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTemsilcilikler::route('/'),
            'create' => CreateTemsilcilik::route('/create'),
            'edit' => EditTemsilcilik::route('/{record}/edit'),
        ];
    }
}
