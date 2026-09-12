<?php

declare(strict_types=1);

namespace App\Filament\Resources\IslemTurleri;

use App\Filament\Concerns\RestrictsToAdmins;
use App\Filament\Resources\IslemTurleri\Pages\CreateIslemTuru;
use App\Filament\Resources\IslemTurleri\Pages\EditIslemTuru;
use App\Filament\Resources\IslemTurleri\Pages\ListIslemTurleri;
use App\Filament\Resources\TemsilcilikIslemleri\TemsilcilikIslemiResource;
use App\Models\IslemTuru;
use App\Models\TemsilcilikIslemi;
use App\Services\Ai\CountryGuideAiAssistant;
use BackedEnum;
use Filament\Actions\Action;
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
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use UnitEnum;

/**
 * Ülke Rehberi — işlem türü şablonları (ÜLKE-BAĞIMSIZ; tasarım §3).
 *
 * Vekaletname/pasaport/askerlik gibi ~15 tür burada tanımlanır; ülkeye
 * özgü içerik "İşlem İçerikleri" ekranında temsilcilik başına girilir.
 */
class IslemTuruResource extends Resource
{
    use RestrictsToAdmins;

    protected static ?string $model = IslemTuru::class;

    protected static ?string $slug = 'islem-turleri';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|UnitEnum|null $navigationGroup = 'Ülke Rehberi';

    protected static ?string $navigationLabel = 'İşlem Türleri';

    protected static ?int $navigationSort = 2;

    public static function getModelLabel(): string
    {
        return 'İşlem Türü';
    }

    public static function getPluralModelLabel(): string
    {
        return 'İşlem Türleri';
    }

    public static function getNavigationBadge(): ?string
    {
        $count = IslemTuru::query()->where('is_active', true)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Aktif konsolosluk işlem kategorisi sayısı';
    }

    public static function getIconForRecord(IslemTuru $record): BackedEnum
    {
        $slug = strtolower((string) $record->slug);
        $ad = mb_strtolower((string) $record->ad, 'UTF-8');

        return match (true) {
            str_contains($slug, 'pasaport') || str_contains($ad, 'pasaport') => Heroicon::OutlinedIdentification,
            str_contains($slug, 'vekalet') || str_contains($slug, 'noter') || str_contains($ad, 'vekalet') || str_contains($ad, 'noter') => Heroicon::OutlinedScale,
            str_contains($slug, 'kimlik') || str_contains($ad, 'kimlik') => Heroicon::OutlinedUserCircle,
            str_contains($slug, 'dogum') || str_contains($ad, 'doğum') => Heroicon::OutlinedHeart,
            str_contains($slug, 'evli') || str_contains($ad, 'evlen') || str_contains($ad, 'nikah') => Heroicon::OutlinedSparkles,
            str_contains($slug, 'cenaze') || str_contains($slug, 'olum') || str_contains($ad, 'vefat') || str_contains($ad, 'cenaze') => Heroicon::OutlinedShieldCheck,
            str_contains($slug, 'asker') || str_contains($ad, 'asker') => Heroicon::OutlinedShieldExclamation,
            str_contains($slug, 'adli-sicil') || str_contains($ad, 'adli sicil') => Heroicon::OutlinedDocumentCheck,
            str_contains($slug, 'apostil') || str_contains($slug, 'tercume') || str_contains($ad, 'tasdik') => Heroicon::OutlinedDocumentMagnifyingGlass,
            default => Heroicon::OutlinedClipboardDocumentList,
        };
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('İşlem Türü & URL')
                ->columns(2)
                ->schema([
                    TextInput::make('ad')
                        ->label('İşlem Türü Adı')
                        ->placeholder('örn. Pasaport Başvuru ve Yenileme')
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
                        ->placeholder('örn. pasaport')
                        ->required()
                        ->maxLength(80)
                        ->unique(ignoreRecord: true)
                        ->helperText('Adres şablonu: /{ülke}/{temsilcilik}/{kısa-ad} — yayına girdikten sonra değiştirmek dış linkleri kırar.'),
                ]),

            Section::make('Açıklama & Rehber Tanımı')
                ->headerActions([
                    Action::make('aiAciklamaUret')
                        ->label('AI ile Açıklama Öner')
                        ->icon(Heroicon::OutlinedSparkles)
                        ->color('primary')
                        ->action(function (callable $get, callable $set, CountryGuideAiAssistant $assistant): void {
                            $ad = (string) $get('ad');
                            if (blank($ad)) {
                                Notification::make()->title('Lütfen önce işlem türü adını giriniz')->warning()->send();

                                return;
                            }
                            $aciklama = $assistant->suggestProcedureDescription($ad);
                            $set('aciklama', $aciklama);
                            Notification::make()->title('AI açıklama önerisi forma yüklendi')->success()->send();
                        }),
                ])
                ->schema([
                    TextInput::make('aciklama')
                        ->label('Kısa Rehber Açıklaması')
                        ->placeholder('örn. Umuma mahsus, hususi ve hizmet pasaportu başvuru, temdit ve kayıp işlemleri.')
                        ->maxLength(500)
                        ->helperText('Temsilcilik sayfasındaki listede işlemin hemen altında ziyaretçiye gösterilir.')
                        ->columnSpanFull(),
                ]),

            Section::make('Yayın & Sıralama')
                ->columns(2)
                ->schema([
                    Toggle::make('is_active')
                        ->label('Aktif (Temsilcilik sayfalarında göster)')
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
            ->modifyQueryUsing(fn ($query) => $query->withCount('islemler'))
            ->columns([
                TextColumn::make('ad')
                    ->label('İşlem Türü')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->icon(fn (IslemTuru $record): BackedEnum => static::getIconForRecord($record))
                    ->iconColor('primary')
                    ->description(fn (IslemTuru $record): ?string => $record->aciklama ? Str::limit($record->aciklama, 50) : null),

                TextColumn::make('slug')
                    ->label('URL Kısa Adı')
                    ->badge()
                    ->color('gray')
                    ->icon(Heroicon::OutlinedCommandLine)
                    ->copyable()
                    ->copyMessage('URL kısa adı kopyalandı')
                    ->tooltip('Rehber URL segmenti'),

                TextColumn::make('islemler_count')
                    ->label('İçerik Girilen Temsilcilik')
                    ->counts('islemler')
                    ->badge()
                    ->color(fn (int $state): string => $state > 0 ? 'success' : 'warning')
                    ->icon(Heroicon::OutlinedBuildingLibrary)
                    ->formatStateUsing(fn (int $state): string => "{$state} Temsilcilik")
                    ->url(fn (IslemTuru $r): string => TemsilcilikIslemiResource::getUrl('index', [
                        'tableFilters' => ['islem_turu_id' => ['value' => $r->id]],
                    ]))
                    ->tooltip('Bu işlem türüne ait tüm konsolosluk içeriklerini filtrele')
                    ->sortable(),

                ToggleColumn::make('is_active')
                    ->label('Aktif')
                    ->tooltip('Tüm temsilciliklerde genel aktiflik'),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Yayın Durumu')
                    ->placeholder('Tümü')
                    ->trueLabel('Yalnızca Aktif')
                    ->falseLabel('Yalnızca Pasif'),

                Filter::make('icerik_var')
                    ->label('İçeriği Olanlar')
                    ->query(fn ($query) => $query->has('islemler')),

                Filter::make('icerik_yok')
                    ->label('İçeriği Eksik Olanlar')
                    ->query(fn ($query) => $query->doesntHave('islemler')),
            ])
            ->recordActions([
                Action::make('islemler')
                    ->label('İçerikler')
                    ->icon(Heroicon::OutlinedDocumentText)
                    ->color('info')
                    ->tooltip('Bu işlem türüne ait temsilcilik içeriklerini listele')
                    ->url(fn (IslemTuru $record): string => TemsilcilikIslemiResource::getUrl('index', [
                        'tableFilters' => ['islem_turu_id' => ['value' => $record->id]],
                    ])),

                Action::make('aiAnaliz')
                    ->label('AI Analiz')
                    ->icon(Heroicon::OutlinedSparkles)
                    ->color('primary')
                    ->tooltip('İşlem türünün küresel temsilcilik dağılımını incele')
                    ->modalHeading(fn (IslemTuru $record): string => $record->ad.' — Küresel Dağılım Analizi')
                    ->modalDescription(function (IslemTuru $record): HtmlString {
                        $toplamIcerik = $record->islemler()->count();
                        $yayindaIcerik = $record->islemler()->where('status', TemsilcilikIslemi::STATUS_YAYIN)->count();
                        $taslakIcerik = $record->islemler()->where('status', TemsilcilikIslemi::STATUS_TASLAK)->count();
                        $yayinOrani = $toplamIcerik > 0 ? (int) round(($yayindaIcerik / $toplamIcerik) * 100) : 0;

                        return new HtmlString(
                            "<div class='space-y-3 text-sm p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700'>"
                            ."<div class='flex items-center justify-between border-b border-gray-200 dark:border-gray-700 pb-2'>"
                            ."<span class='font-semibold text-gray-900 dark:text-gray-100'>{$record->ad}</span>"
                            ."<span class='font-bold text-base text-emerald-700 dark:text-emerald-400'>%{$yayinOrani} Yayında</span>"
                            .'</div>'
                            ."<div class='grid grid-cols-3 gap-2 text-xs pt-1'>"
                            ."<div class='p-2 rounded bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800'><strong>Toplam Kapsam:</strong> {$toplamIcerik} Temsilcilik</div>"
                            ."<div class='p-2 rounded bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 text-emerald-700 dark:text-emerald-400'><strong>Yayında:</strong> {$yayindaIcerik}</div>"
                            ."<div class='p-2 rounded bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 text-amber-700 dark:text-amber-400'><strong>Taslak:</strong> {$taslakIcerik}</div>"
                            .'</div>'
                            ."<div class='text-xs text-gray-600 dark:text-gray-300 pt-2 border-t border-gray-200 dark:border-gray-700 leading-relaxed'>"
                            .($record->aciklama ? "<strong>Tanım:</strong> {$record->aciklama}" : '<strong>Uyarı:</strong> Bu işlem türünün henüz kısa rehber açıklaması girilmemiş.')
                            .'</div>'
                            .'</div>'
                        );
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Kapat'),

                Action::make('duzenle')
                    ->label('Düzenle')
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->color('primary')
                    ->url(fn (IslemTuru $record): string => static::getUrl('edit', ['record' => $record])),
            ])
            ->defaultSort('sort_order');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListIslemTurleri::route('/'),
            'create' => CreateIslemTuru::route('/create'),
            'edit' => EditIslemTuru::route('/{record}/edit'),
        ];
    }
}
