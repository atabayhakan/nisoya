<?php

namespace App\Filament\Resources\IslemTurleri;

use App\Filament\Concerns\RestrictsToAdmins;
use App\Filament\Resources\IslemTurleri\Pages\CreateIslemTuru;
use App\Filament\Resources\IslemTurleri\Pages\EditIslemTuru;
use App\Filament\Resources\IslemTurleri\Pages\ListIslemTurleri;
use App\Models\IslemTuru;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
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
        return 'işlem türü';
    }

    public static function getPluralModelLabel(): string
    {
        return 'İşlem Türleri';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('ad')
                ->label('Ad')
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
                ->unique(ignoreRecord: true)
                ->helperText('Adres: nisoya.com/de/koeln/kısa-ad — yayına girdikten sonra değiştirmek linkleri kırar.'),
            TextInput::make('aciklama')
                ->label('Tek cümlelik açıklama')
                ->maxLength(500)
                ->helperText('Temsilcilik sayfasındaki listede işlemin altında görünür.'),
            Toggle::make('is_active')->label('Aktif')->default(true),
            TextInput::make('sort_order')->label('Sıra')->numeric()->default(0),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('ad')->label('İşlem türü')->searchable()->sortable(),
                TextColumn::make('slug')->label('URL kısa adı'),
                TextColumn::make('islemler_count')->label('İçerik girilen temsilcilik')->counts('islemler'),
                ToggleColumn::make('is_active')->label('Aktif'),
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
