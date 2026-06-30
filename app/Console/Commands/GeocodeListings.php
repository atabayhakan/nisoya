<?php

namespace App\Console\Commands;

use App\Models\Listing;
use App\Services\GeocodingService;
use Illuminate\Console\Command;

class GeocodeListings extends Command
{
    protected $signature = 'listings:geocode {--all : Koordinatı olanlar dahil hepsini yeniden işle}';

    protected $description = 'İlanlara şehir/ülke bazlı koordinat atar (harita için).';

    public function handle(GeocodingService $geo): int
    {
        $query = Listing::query()->whereNotNull('country_code');

        if (! $this->option('all')) {
            $query->whereNull('latitude');
        }

        $listings = $query->get();
        $this->info($listings->count().' ilan işlenecek...');

        foreach ($listings as $listing) {
            $coords = $geo->locate($listing->city, $listing->country_code);
            $listing->update(['latitude' => $coords['latitude'], 'longitude' => $coords['longitude']]);
            usleep(1_100_000); // Nominatim nezaket gecikmesi
        }

        $this->info('Tamamlandı.');

        return self::SUCCESS;
    }
}
