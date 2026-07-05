<?php

namespace App\Filament\Resources\CompanyReviews\Pages;

use App\Filament\Resources\CompanyReviews\CompanyReviewResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCompanyReview extends EditRecord
{
    protected static string $resource = CompanyReviewResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
