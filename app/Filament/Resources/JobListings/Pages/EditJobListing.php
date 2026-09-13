<?php

declare(strict_types=1);

namespace App\Filament\Resources\JobListings\Pages;

use App\Filament\Resources\JobListings\JobListingResource;
use App\Models\JobListing;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditJobListing extends EditRecord
{
    protected static string $resource = JobListingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sitedeGor')
                ->label('İlana Git')
                ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                ->color('gray')
                ->url(fn (JobListing $record): string => route('jobs.show', ['job' => $record->id, 'slug' => $record->slug]), shouldOpenInNewTab: true),

            DeleteAction::make(),
        ];
    }
}
