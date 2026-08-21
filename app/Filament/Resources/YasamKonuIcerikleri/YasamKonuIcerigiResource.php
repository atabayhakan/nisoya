<?php

namespace App\Filament\Resources\YasamKonuIcerikleri;

use App\Filament\Concerns\RestrictsToAdmins;
use App\Filament\Resources\YasamKonuIcerikleri\Pages\CreateYasamKonuIcerigi;
use App\Filament\Resources\YasamKonuIcerikleri\Pages\EditYasamKonuIcerigi;
use App\Filament\Resources\YasamKonuIcerikleri\Pages\ListYasamKonuIcerikleri;
use App\Models\Country;
use App\Models\YasamKonuIcerigi;
use App\Models\YasamKonusu;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Yaşam Rehberi — konu içerikleri (rehberin asıl gövdesi, konu × ülke).
 *
 * TASLAK-ÖNCE SÖZLEŞMESİ (Ülke Rehberi'ndeki K7'nin aynısı): yeni kayıt
 * taslak doğar; sahip kaynaktan doğrulayıp "doğrulama tarihi"ni bugüne
 * çekerek yayına alır. 90 günü aşan yayındaki kayıtlar Kâhya'nın günlük
 * raporuna "bayat" uyarısı olarak düşer (bkz. YasamKonuIcerigi::scopeBayat).
 */
class YasamKonuIcerigiResource extends Resource
{
    use RestrictsToAdmins;

    protected static ?string $model = YasamKonuIcerigi::class;

    protected static ?string $slug = 'yasam-konu-icerikleri';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = 'Ülke Rehberi';

    protected static ?string $navigationLabel = 'Yaşam Konu İçerikleri';

    protected static ?int $navigationSort = 7;

    public static function getModelLabel(): string
    {
        return 'yaşam konu içeriği';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Yaşam Konu İçerikleri';
    }

    /** Taslak bekleyen içerik sayısı — doldurulacak işin görünür ölçüsü. */
    public static function getNavigationBadge(): ?string
    {
        return (string) (YasamKonuIcerigi::query()->where('status', YasamKonuIcerigi::STATUS_TASLAK)->count() ?: '');
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Nerede, hangi konu')
                ->columns(2)
                ->schema([
                    Select::make('yasam_konusu_id')
                        ->label('Konu')
                        ->options(fn () => YasamKonusu::query()->with('kategori')->orderBy('sort_order')
                            ->get()->mapWithKeys(fn (YasamKonusu $k) => [$k->id => $k->kategori->ad.' — '.$k->baslik]))
                        ->required()
                        ->native(false)
                        ->searchable()
                        ->disabledOn('edit'),
                    Select::make('country_code')
                        ->label('Ülke')
                        ->options(fn () => Country::query()->orderBy('sort_order')->pluck('name_tr', 'code'))
                        ->required()
                        ->native(false)
                        ->searchable()
                        ->disabledOn('edit'),
                ]),

            Section::make('İçerik')
                ->schema([
                    Repeater::make('icerik')
                        ->label('Gövde')
                        ->schema([
                            Select::make('tip')
                                ->label('Blok tipi')
                                ->options([
                                    'baslik' => 'Alt başlık',
                                    'paragraf' => 'Paragraf',
                                    'madde' => 'Madde (liste satırı)',
                                ])
                                ->default('paragraf')
                                ->required()
                                ->native(false),
                            TextInput::make('metin')
                                ->label('Metin')
                                ->required()
                                ->maxLength(1000)
                                ->columnSpanFull(),
                        ])
                        ->columns(2)
                        ->reorderable()
                        ->collapsible()
                        ->defaultItems(0)
                        ->addActionLabel('Blok ekle')
                        ->helperText('Ardışık "madde" blokları sitede tek listeye toplanır.'),
                    TextInput::make('kaynak_url')
                        ->label('Kaynak adresi')
                        ->url()
                        ->maxLength(300)
                        ->helperText('Sayfadaki "Kaynağı aç" butonu buraya gider.'),
                    TextInput::make('kaynak_aciklama')
                        ->label('Kaynak açıklaması (ops.)')
                        ->maxLength(300),
                ]),

            Section::make('Yayın & doğrulama')
                ->columns(3)
                ->schema([
                    Select::make('status')
                        ->label('Durum')
                        ->options([
                            YasamKonuIcerigi::STATUS_TASLAK => 'Taslak (sitede görünmez)',
                            YasamKonuIcerigi::STATUS_YAYIN => 'Yayında',
                        ])
                        ->default(YasamKonuIcerigi::STATUS_TASLAK)
                        ->required()
                        ->helperText('Yayına almadan önce içeriği kaynaktan doğrula.'),
                    DatePicker::make('dogrulanma_tarihi')
                        ->label('Son doğrulama tarihi')
                        ->native(false)
                        ->helperText(YasamKonuIcerigi::BAYATLIK_GUN.' günü aşarsa Kâhya raporunda "bayat" uyarısı çıkar.'),
                    Select::make('yazan_tur')
                        ->label('Kim yazdı')
                        ->options([
                            YasamKonuIcerigi::YAZAN_AI => 'AI (araştırma ajanı)',
                            YasamKonuIcerigi::YAZAN_TOPLULUK => 'Topluluk önerisi',
                            YasamKonuIcerigi::YAZAN_SAHIP => 'Sahip',
                        ])
                        ->default(YasamKonuIcerigi::YAZAN_AI)
                        ->required()
                        ->native(false),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('konu.baslik')->label('Konu')->searchable()->sortable(),
                TextColumn::make('country_code')->label('Ülke')->badge(),
                TextColumn::make('status')
                    ->label('Durum')
                    ->badge()
                    ->color(fn (string $state): string => $state === YasamKonuIcerigi::STATUS_YAYIN ? 'success' : 'gray')
                    ->formatStateUsing(fn (string $state): string => $state === YasamKonuIcerigi::STATUS_YAYIN ? 'Yayında' : 'Taslak'),
                TextColumn::make('dogrulanma_tarihi')
                    ->label('Son doğrulama')
                    ->date('d.m.Y')
                    ->placeholder('hiç')
                    ->sortable(),
                TextColumn::make('yazan_tur')->label('Kim yazdı')->badge(),
                TextColumn::make('oneriler_count')->label('Öneri')->counts('oneriler'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Durum')
                    ->options([
                        YasamKonuIcerigi::STATUS_TASLAK => 'Taslak',
                        YasamKonuIcerigi::STATUS_YAYIN => 'Yayında',
                    ]),
                SelectFilter::make('country_code')
                    ->label('Ülke')
                    ->options(fn () => Country::query()->orderBy('sort_order')->pluck('name_tr', 'code')),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListYasamKonuIcerikleri::route('/'),
            'create' => CreateYasamKonuIcerigi::route('/create'),
            'edit' => EditYasamKonuIcerigi::route('/{record}/edit'),
        ];
    }
}
