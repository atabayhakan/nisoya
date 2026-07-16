<?php

namespace App\Filament\Resources\Zones;

use App\Filament\Resources\Zones\Pages\EditZone;
use App\Filament\Resources\Zones\Pages\ListZones;
use App\Filament\Support\ContentBlocks;
use App\Models\Zone;
use BackedEnum;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Sitenin önceden kablolanmış noktalarına (anasayfa, ilan listesi, footer vb.)
 * admin panelinden içerik bloğu veya reklam eklenmesini sağlar. Anahtarlar
 * (`key`) kodda `<x-zone zone-key="...">` çağrılarına karşılık gelir — yeni
 * kayıt oluşturma/silme kasıtlı olarak kapalı (bkz. ZoneSeeder, getPages()).
 */
class ZoneResource extends Resource
{
    protected static ?string $model = Zone::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static string|UnitEnum|null $navigationGroup = 'Site Yönetimi';

    protected static ?string $navigationLabel = 'Alanlar (Reklam/İçerik)';

    protected static ?int $navigationSort = 3;

    public static function getModelLabel(): string
    {
        return 'alan';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Alanlar';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Konum bilgisi')
                ->description('Bu alan kodda tanımlıdır, buradan sadece bilgi amaçlı görüntülenir.')
                ->columns(2)
                ->schema([
                    TextInput::make('name')->label('Etiket')->disabled(),
                    TextInput::make('key')->label('Anahtar (kod)')->disabled()->extraInputAttributes(['class' => 'font-mono text-xs']),
                    Textarea::make('location_note')->label('Sitede nerede görünür?')->disabled()->rows(2)->columnSpanFull(),
                ]),

            Section::make('Yayın ayarları')
                ->columns(3)
                ->schema([
                    Toggle::make('is_active')
                        ->label('Aktif (sitede göster)')
                        ->helperText('Kapalıyken bu alan sitede hiç görünmez.')
                        ->columnSpanFull(),
                    DateTimePicker::make('starts_at')->label('Başlangıç (ops.)')->helperText('Boş bırakılırsa hemen başlar.'),
                    DateTimePicker::make('ends_at')->label('Bitiş (ops.)')->helperText('Boş bırakılırsa süresiz gösterilir.'),
                ]),

            Section::make('İçerik blokları')
                ->description('Bu alana metin, görsel, çağrı (CTA) ya da reklam bloğu ekle. Birden fazla blok eklenirse sırayla gösterilir.')
                ->schema([
                    Builder::make('blocks')
                        ->label('')
                        ->collapsible()
                        ->cloneable()
                        ->blockNumbers(false)
                        ->blocks([
                            ...ContentBlocks::schema(),
                            Builder\Block::make('reklam')
                                ->label('Reklam (Google AdSense)')
                                ->icon('heroicon-o-currency-dollar')
                                ->schema([
                                    TextInput::make('slot_id')
                                        ->label('AdSense Slot ID')
                                        ->required()
                                        ->helperText('Google AdSense panelinden aldığın reklam birimi ID\'si (ör. 1234567890).'),
                                    Select::make('format')
                                        ->label('Format')
                                        ->options([
                                            'auto' => 'Otomatik',
                                            'horizontal' => 'Yatay (banner)',
                                            'rectangle' => 'Dikdörtgen (kare)',
                                            'vertical' => 'Dikey (skyscraper)',
                                        ])
                                        ->default('auto')
                                        ->required(),
                                ]),
                        ]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Etiket')->searchable(),
                TextColumn::make('key')->label('Anahtar')->fontFamily('mono')->size('xs')->copyable(),
                TextColumn::make('location_note')->label('Konum')->limit(50)->wrap()->toggleable(),
                TextColumn::make('blocks')->label('Blok sayısı')->state(fn (Zone $record) => count($record->blocks ?? []))->badge(),
                ToggleColumn::make('is_active')->label('Aktif'),
                TextColumn::make('ends_at')->label('Bitiş')->dateTime('d.m.Y H:i')->placeholder('—')->toggleable(),
            ])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListZones::route('/'),
            'edit' => EditZone::route('/{record}/edit'),
        ];
    }
}
