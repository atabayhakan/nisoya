<?php

declare(strict_types=1);

namespace App\Filament\Resources\JobListings;

use App\Enums\JobStatus;
use App\Filament\Resources\JobListings\Pages\CreateJobListing;
use App\Filament\Resources\JobListings\Pages\EditJobListing;
use App\Filament\Resources\JobListings\Pages\ListJobListings;
use App\Filament\Resources\JobListings\Schemas\JobListingForm;
use App\Filament\Resources\JobListings\Tables\JobListingsTable;
use App\Filament\Resources\JobListings\Widgets\JobListingStatsWidget;
use App\Models\JobListing;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class JobListingResource extends Resource
{
    protected static ?string $model = JobListing::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBriefcase;

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        return 'İş & Kariyer Portalı';
    }

    public static function getNavigationLabel(): string
    {
        return 'İş İlanları';
    }

    public static function getModelLabel(): string
    {
        return 'iş ilanı';
    }

    public static function getPluralModelLabel(): string
    {
        return 'İş İlanları';
    }

    public static function getNavigationBadge(): ?string
    {
        $pending = JobListing::query()->where('status', JobStatus::Beklemede)->count();

        return $pending > 0 ? (string) $pending : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Onay bekleyen yeni iş ilanları';
    }

    public static function form(Schema $schema): Schema
    {
        return JobListingForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return JobListingsTable::configure($table);
    }

    public static function getWidgets(): array
    {
        return [
            JobListingStatsWidget::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListJobListings::route('/'),
            'create' => CreateJobListing::route('/create'),
            'edit' => EditJobListing::route('/{record}/edit'),
        ];
    }
}
