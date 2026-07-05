<?php

namespace App\Console\Commands;

use App\Models\JobListing;
use Illuminate\Console\Command;

class ExpireFeaturedJobListings extends Command
{
    protected $signature = 'job-listings:expire-featured';

    protected $description = 'Süresi dolan öne çıkan iş ilanlarını normale döndürür.';

    public function handle(): int
    {
        $count = JobListing::query()
            ->where('is_featured', true)
            ->whereNotNull('featured_until')
            ->where('featured_until', '<', now())
            ->update(['is_featured' => false]);

        $this->info("{$count} öne çıkan iş ilanının süresi doldu.");

        return self::SUCCESS;
    }
}
