<?php

namespace App\Filament\Resources\CompanyReviews\Pages;

use App\Filament\Resources\CompanyReviews\CompanyReviewResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCompanyReviews extends ListRecords
{
    protected static string $resource = CompanyReviewResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
