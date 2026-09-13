<?php

declare(strict_types=1);

namespace App\Filament\Resources\CompanyReviews;

use App\Enums\ReviewStatus;
use App\Filament\Resources\CompanyReviews\Pages\CreateCompanyReview;
use App\Filament\Resources\CompanyReviews\Pages\EditCompanyReview;
use App\Filament\Resources\CompanyReviews\Pages\ListCompanyReviews;
use App\Filament\Resources\CompanyReviews\Schemas\CompanyReviewForm;
use App\Filament\Resources\CompanyReviews\Tables\CompanyReviewsTable;
use App\Filament\Resources\CompanyReviews\Widgets\CompanyReviewStatsWidget;
use App\Models\CompanyReview;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CompanyReviewResource extends Resource
{
    protected static ?string $model = CompanyReview::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedStar;

    protected static ?int $navigationSort = 4;

    public static function getNavigationGroup(): ?string
    {
        return 'İş & Kariyer Portalı';
    }

    public static function getNavigationLabel(): string
    {
        return 'Şirket Değerlendirmeleri';
    }

    public static function getModelLabel(): string
    {
        return 'Şirket Değerlendirmesi';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Şirket Değerlendirmeleri';
    }

    public static function getNavigationBadge(): ?string
    {
        $hidden = CompanyReview::query()->where('status', ReviewStatus::Gizli)->count();

        return $hidden > 0 ? (string) $hidden : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Moderasyon / İnceleme bekleyen gizli yorumlar';
    }

    public static function form(Schema $schema): Schema
    {
        return CompanyReviewForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CompanyReviewsTable::configure($table);
    }

    public static function getWidgets(): array
    {
        return [
            CompanyReviewStatsWidget::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCompanyReviews::route('/'),
            'create' => CreateCompanyReview::route('/create'),
            'edit' => EditCompanyReview::route('/{record}/edit'),
        ];
    }
}
