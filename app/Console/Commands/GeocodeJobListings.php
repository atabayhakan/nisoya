<?php

namespace App\Console\Commands;

use App\Models\JobListing;
use App\Services\GeocodingService;
use Illuminate\Console\Command;

class GeocodeJobListings extends Command
{
    protected $signature = 'job-listings:geocode {--all : Koordinatı olanlar dahil hepsini yeniden işle}';

    protected $description = 'İş ilanlarına şehir/ülke bazlı koordinat atar (harita için).';

    public function handle(GeocodingService $geo): int
    {
        $query = JobListing::query()->whereNotNull('country_code');

        if (! $this->option('all')) {
            $query->whereNull('latitude');
        }

        $jobs = $query->get();
        $this->info($jobs->count().' iş ilanı işlenecek...');

        foreach ($jobs as $job) {
            $coords = $geo->locate($job->city, $job->country_code);
            $job->update(['latitude' => $coords['latitude'], 'longitude' => $coords['longitude']]);
            usleep(1_100_000); // Nominatim nezaket gecikmesi
        }

        $this->info('Tamamlandı.');

        return self::SUCCESS;
    }
}
