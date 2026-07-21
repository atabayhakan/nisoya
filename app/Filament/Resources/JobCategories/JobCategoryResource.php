<?php

namespace App\Filament\Resources\JobCategories;

use App\Filament\Concerns\RestrictsToAdmins;
use App\Filament\Resources\JobCategories\Pages\CreateJobCategory;
use App\Filament\Resources\JobCategories\Pages\EditJobCategory;
use App\Filament\Resources\JobCategories\Pages\ListJobCategories;
use App\Models\JobCategory;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class JobCategoryResource extends Resource
{
    use RestrictsToAdmins;

    protected static ?string $model = JobCategory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleGroup;

    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): ?string
    {
        return 'İş & Kariyer Portalı';
    }

    public static function getNavigationLabel(): string
    {
        return 'İş Kategorileri';
    }

    public static function getModelLabel(): string
    {
        return 'iş kategorisi';
    }

    public static function getPluralModelLabel(): string
    {
        return 'İş Kategorileri';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('Ad')->required()
                ->live(onBlur: true)
                ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug((string) $state))),
            TextInput::make('slug')->label('Slug')->required(),
            TextInput::make('icon')->label('İkon (heroicon adı)'),
            TextInput::make('sort_order')->label('Sıra')->numeric()->default(0),
            Toggle::make('is_active')->label('Aktif')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Ad')->searchable()->sortable(),
                TextColumn::make('slug')->label('Slug')->toggleable(),
                IconColumn::make('is_active')->label('Aktif')->boolean(),
                TextColumn::make('sort_order')->label('Sıra')->sortable(),
            ])
            ->defaultSort('sort_order');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListJobCategories::route('/'),
            'create' => CreateJobCategory::route('/create'),
            'edit' => EditJobCategory::route('/{record}/edit'),
        ];
    }
}
