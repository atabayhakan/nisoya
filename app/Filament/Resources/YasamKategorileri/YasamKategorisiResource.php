<?php

namespace App\Filament\Resources\YasamKategorileri;

use App\Filament\Concerns\RestrictsToAdmins;
use App\Filament\Resources\YasamKategorileri\Pages\CreateYasamKategorisi;
use App\Filament\Resources\YasamKategorileri\Pages\EditYasamKategorisi;
use App\Filament\Resources\YasamKategorileri\Pages\ListYasamKategorileri;
use App\Models\YasamKategorisi;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use UnitEnum;

/**
 * Yaşam Rehberi — kategoriler (Bankacılık & Finans, Barınma, ...).
 *
 * Ülkeden bağımsız şablon — Ülke Rehberi'ndeki IslemTuruResource'un aynısı.
 */
class YasamKategorisiResource extends Resource
{
    use RestrictsToAdmins;

    protected static ?string $model = YasamKategorisi::class;

    protected static ?string $slug = 'yasam-kategorileri';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static string|UnitEnum|null $navigationGroup = 'Ülke Rehberi';

    protected static ?string $navigationLabel = 'Yaşam Kategorileri';

    protected static ?int $navigationSort = 5;

    public static function getModelLabel(): string
    {
        return 'yaşam kategorisi';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Yaşam Kategorileri';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->columns(2)
                ->schema([
                    TextInput::make('ad')
                        ->label('Ad')
                        ->placeholder('örn. Bankacılık & Finans')
                        ->required()
                        ->maxLength(120)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                            if (blank($get('slug'))) {
                                $set('slug', Str::slug((string) $state));
                            }
                        }),
                    TextInput::make('slug')
                        ->label('Kısa ad (URL)')
                        ->required()
                        ->maxLength(80)
                        ->helperText('Adres: nisoya.com/de/yasam/kısa-ad'),
                    TextInput::make('ikon')
                        ->label('İkon (emoji)')
                        ->placeholder('🏦')
                        ->maxLength(60),
                    TextInput::make('sort_order')->label('Sıra')->numeric()->default(0),
                    Toggle::make('is_active')->label('Aktif (rehberde göster)')->default(true),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('ad')->label('Kategori')->searchable()->sortable(),
                TextColumn::make('slug')->label('URL')->formatStateUsing(fn (YasamKategorisi $r): string => '/yasam/'.$r->slug),
                TextColumn::make('konular_count')->label('Konu')->counts('konular'),
                ToggleColumn::make('is_active')->label('Aktif'),
            ])
            ->defaultSort('sort_order');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListYasamKategorileri::route('/'),
            'create' => CreateYasamKategorisi::route('/create'),
            'edit' => EditYasamKategorisi::route('/{record}/edit'),
        ];
    }
}
