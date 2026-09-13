<?php

declare(strict_types=1);

namespace App\Filament\Resources\JobListings\Pages;

use App\Filament\Resources\JobListings\JobListingResource;
use App\Models\JobListing;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateJobListing extends CreateRecord
{
    protected static string $resource = JobListingResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (blank($data['slug'] ?? null) && filled($data['title'] ?? null)) {
            $base = Str::slug($data['title']) ?: 'ilan';
            $slug = $base;
            $i = 1;
            while (JobListing::query()->where('slug', $slug)->exists()) {
                $slug = "{$base}-".(++$i);
            }
            $data['slug'] = $slug;
        }

        return $data;
    }
}
