<?php

declare(strict_types=1);

namespace App\Filament\Resources\JobFeatureRequests;

use App\Enums\FeatureRequestStatus;
use App\Filament\Concerns\RestrictsToAdmins;
use App\Filament\Resources\JobFeatureRequests\Pages\CreateJobFeatureRequest;
use App\Filament\Resources\JobFeatureRequests\Pages\EditJobFeatureRequest;
use App\Filament\Resources\JobFeatureRequests\Pages\ListJobFeatureRequests;
use App\Filament\Resources\JobFeatureRequests\Schemas\JobFeatureRequestForm;
use App\Filament\Resources\JobFeatureRequests\Tables\JobFeatureRequestsTable;
use App\Filament\Resources\JobFeatureRequests\Widgets\JobFeatureRequestStatsWidget;
use App\Models\JobFeatureRequest;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class JobFeatureRequestResource extends Resource
{
    use RestrictsToAdmins;

    protected static ?string $model = JobFeatureRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRocketLaunch;

    protected static string|UnitEnum|null $navigationGroup = 'İş & Kariyer Portalı';

    protected static ?int $navigationSort = 5;

    public static function getNavigationLabel(): string
    {
        return 'İş İlanı Öne Çıkarma';
    }

    public static function getModelLabel(): string
    {
        return 'İş İlanı Öne Çıkarma Talebi';
    }

    public static function getPluralModelLabel(): string
    {
        return 'İş İlanı Öne Çıkarma Talepleri';
    }

    public static function getNavigationBadge(): ?string
    {
        $bekleyen = JobFeatureRequest::query()->where('status', FeatureRequestStatus::Beklemede)->count();

        return $bekleyen > 0 ? (string) $bekleyen : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Schema $schema): Schema
    {
        return JobFeatureRequestForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return JobFeatureRequestsTable::configure($table);
    }

    public static function getWidgets(): array
    {
        return [
            JobFeatureRequestStatsWidget::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListJobFeatureRequests::route('/'),
            'create' => CreateJobFeatureRequest::route('/create'),
            'edit' => EditJobFeatureRequest::route('/{record}/edit'),
        ];
    }
}
