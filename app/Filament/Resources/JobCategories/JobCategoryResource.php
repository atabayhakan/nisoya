<?php

declare(strict_types=1);

namespace App\Filament\Resources\JobCategories;

use App\Filament\Concerns\RestrictsToAdmins;
use App\Filament\Resources\JobCategories\Pages\CreateJobCategory;
use App\Filament\Resources\JobCategories\Pages\EditJobCategory;
use App\Filament\Resources\JobCategories\Pages\ListJobCategories;
use App\Filament\Resources\JobCategories\Schemas\JobCategoryForm;
use App\Filament\Resources\JobCategories\Tables\JobCategoriesTable;
use App\Filament\Resources\JobCategories\Widgets\JobCategoryStatsWidget;
use App\Models\JobCategory;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

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
        return 'İş Kategorisi';
    }

    public static function getPluralModelLabel(): string
    {
        return 'İş Kategorileri';
    }

    public static function getNavigationBadge(): ?string
    {
        $active = JobCategory::query()->where('is_active', true)->count();

        return $active > 0 ? (string) $active : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'primary';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Sitede yayında olan aktif sektör sayısı';
    }

    public static function form(Schema $schema): Schema
    {
        return JobCategoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return JobCategoriesTable::configure($table);
    }

    public static function getWidgets(): array
    {
        return [
            JobCategoryStatsWidget::class,
        ];
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
