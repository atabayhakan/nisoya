<?php

namespace App\Filament\Resources\FeatureRequests;

use App\Filament\Concerns\RestrictsToAdmins;
use App\Filament\Resources\FeatureRequests\Pages\CreateFeatureRequest;
use App\Filament\Resources\FeatureRequests\Pages\EditFeatureRequest;
use App\Filament\Resources\FeatureRequests\Pages\ListFeatureRequests;
use App\Filament\Resources\FeatureRequests\Schemas\FeatureRequestForm;
use App\Filament\Resources\FeatureRequests\Tables\FeatureRequestsTable;
use App\Models\FeatureRequest;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class FeatureRequestResource extends Resource
{
    use RestrictsToAdmins;

    protected static ?string $model = FeatureRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRocketLaunch;

    protected static ?int $navigationSort = 4;

    public static function getNavigationGroup(): ?string
    {
        return 'Pazaryeri & Ticaret';
    }

    public static function getNavigationLabel(): string
    {
        return 'Öne Çıkarma Talepleri';
    }

    public static function getModelLabel(): string
    {
        return 'öne çıkarma talebi';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Öne Çıkarma Talepleri';
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) (FeatureRequest::query()->where('status', 'beklemede')->count() ?: '');
    }

    public static function form(Schema $schema): Schema
    {
        return FeatureRequestForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FeatureRequestsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFeatureRequests::route('/'),
            'create' => CreateFeatureRequest::route('/create'),
            'edit' => EditFeatureRequest::route('/{record}/edit'),
        ];
    }
}
