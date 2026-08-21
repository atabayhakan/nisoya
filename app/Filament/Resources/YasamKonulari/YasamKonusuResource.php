<?php

namespace App\Filament\Resources\YasamKonulari;

use App\Filament\Concerns\RestrictsToAdmins;
use App\Filament\Resources\YasamKonulari\Pages\CreateYasamKonusu;
use App\Filament\Resources\YasamKonulari\Pages\EditYasamKonusu;
use App\Filament\Resources\YasamKonulari\Pages\ListYasamKonulari;
use App\Models\YasamKategorisi;
use App\Models\YasamKonusu;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use UnitEnum;

/**
 * Yaşam Rehberi — konular (bir kategori altındaki, ülkeden bağımsız
 * sorular/başlıklar — örn. "SSN'siz banka hesabı açma").
 */
class YasamKonusuResource extends Resource
{
    use RestrictsToAdmins;

    protected static ?string $model = YasamKonusu::class;

    protected static ?string $slug = 'yasam-konulari';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQuestionMarkCircle;

    protected static string|UnitEnum|null $navigationGroup = 'Ülke Rehberi';

    protected static ?string $navigationLabel = 'Yaşam Konuları';

    protected static ?int $navigationSort = 6;

    public static function getModelLabel(): string
    {
        return 'yaşam konusu';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Yaşam Konuları';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->columns(2)
                ->schema([
                    Select::make('kategori_id')
                        ->label('Kategori')
                        ->options(fn () => YasamKategorisi::query()->orderBy('sort_order')->pluck('ad', 'id'))
                        ->required()
                        ->native(false)
                        ->searchable(),
                    TextInput::make('baslik')
                        ->label('Başlık')
                        ->placeholder('örn. SSN\'siz banka hesabı açma')
                        ->required()
                        ->maxLength(160)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                            if (blank($get('slug'))) {
                                $set('slug', Str::slug((string) $state));
                            }
                        }),
                    TextInput::make('slug')
                        ->label('Kısa ad (URL)')
                        ->required()
                        ->maxLength(100)
                        ->helperText('Adres: nisoya.com/de/yasam/kategori/kısa-ad'),
                    TextInput::make('kisa_aciklama')
                        ->label('Kısa açıklama (ops.)')
                        ->maxLength(300)
                        ->columnSpanFull(),
                    TextInput::make('sort_order')->label('Sıra')->numeric()->default(0),
                    Toggle::make('is_active')->label('Aktif')->default(true),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('kategori.ad')->label('Kategori')->sortable(),
                TextColumn::make('baslik')->label('Konu')->searchable()->sortable(),
                TextColumn::make('icerikler_count')->label('Ülke içeriği')->counts('icerikler'),
                ToggleColumn::make('is_active')->label('Aktif'),
            ])
            ->filters([
                SelectFilter::make('kategori_id')
                    ->label('Kategori')
                    ->options(fn () => YasamKategorisi::query()->orderBy('sort_order')->pluck('ad', 'id')),
            ])
            ->defaultSort('sort_order');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListYasamKonulari::route('/'),
            'create' => CreateYasamKonusu::route('/create'),
            'edit' => EditYasamKonusu::route('/{record}/edit'),
        ];
    }
}
