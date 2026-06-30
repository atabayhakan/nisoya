<?php

namespace App\Console\Commands;

use App\Models\Listing;
use Illuminate\Console\Command;

class ExpireFeaturedListings extends Command
{
    protected $signature = 'listings:expire-featured';

    protected $description = 'Süresi dolan öne çıkan ilanları normale döndürür.';

    public function handle(): int
    {
        $count = Listing::query()
            ->where('is_featured', true)
            ->whereNotNull('featured_until')
            ->where('featured_until', '<', now())
            ->update(['is_featured' => false]);

        $this->info("{$count} öne çıkan ilanın süresi doldu.");

        return self::SUCCESS;
    }
}
