<?php

namespace App\Filament\Resources\SssSorulari;

use App\Filament\Concerns\RestrictsToAdmins;
use App\Filament\Resources\SssSorulari\Pages\CreateSssSorusu;
use App\Filament\Resources\SssSorulari\Pages\EditSssSorusu;
use App\Filament\Resources\SssSorulari\Pages\ListSssSorulari;
use App\Models\SssSorusu;
use BackedEnum;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use UnitEnum;

/**
 * SSS — anasayfa/footer'daki "Sıkça Sorulan Sorular" (2026-08-25).
 * `/sss` sayfasının GERÇEK içerik kaynağı budur, `Page(slug=sss)` DEĞİL —
 * o kayıt yalnız footer linki + meta açıklaması için yaşıyor (bkz. Sayfalar
 * kaynağındaki uyarı banner'ı).
 */
class SssSorusuResource extends Resource
{
    use RestrictsToAdmins;

    protected static ?string $model = SssSorusu::class;

    protected static ?string $slug = 'sss-sorulari';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQuestionMarkCircle;

    protected static string|UnitEnum|null $navigationGroup = 'İçerik & Tasarım (CMS)';

    protected static ?int $navigationSort = 3;

    public static function getNavigationLabel(): string
    {
        return 'SSS';
    }

    public static function getModelLabel(): string
    {
        return 'soru';
    }

    public static function getPluralModelLabel(): string
    {
        return 'SSS';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('soru')
                ->label('Soru')
                ->required()
                ->maxLength(300)
                ->columnSpanFull(),
            Textarea::make('cevap')
                ->label('Cevap')
                ->required()
                ->rows(4)
                ->columnSpanFull(),
            Toggle::make('is_active')->label('Aktif')->default(true),
            TextInput::make('sort_order')->label('Sıra')->numeric()->default(0),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('soru')->label('Soru')->searchable()->wrap(),
                ToggleColumn::make('is_active')->label('Aktif'),
                TextColumn::make('sort_order')->label('Sıra')->sortable(),
            ])
            ->defaultSort('sort_order');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSssSorulari::route('/'),
            'create' => CreateSssSorusu::route('/create'),
            'edit' => EditSssSorusu::route('/{record}/edit'),
        ];
    }
}
