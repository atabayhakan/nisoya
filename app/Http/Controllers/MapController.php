<?php

namespace App\Http\Controllers;

use App\Models\JobListing;
use App\Models\Listing;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MapController extends Controller
{
    public function index(Request $request): View
    {
        $type = $request->string('tip')->toString();

        if ($type === 'is') {
            $points = JobListing::query()->active()
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->with(['company', 'category'])
                ->latest()
                ->limit(500)
                ->get()
                ->map(fn (JobListing $j) => [
                    'id' => $j->id,
                    'title' => $j->title,
                    'price' => $j->salaryLabel() ?? 'Görüşülür',
                    'type' => 'is',
                    'city' => $j->city,
                    'country_code' => $j->country_code,
                    'category' => $j->category?->name ?? 'İş İlanı',
                    'company' => $j->company?->name,
                    'image' => $j->company?->logoUrl(),
                    'lat' => (float) $j->latitude,
                    'lng' => (float) $j->longitude,
                    'url' => route('jobs.show', [$j->id, $j->slug]),
                ]);

            return view('listings.map', ['points' => $points, 'tip' => 'is']);
        }

        // gercek(): harita pini rozet TAŞIYAMAZ — örnek ilanlar haritada
        // sahte bir yayılma haritası çizerdi ("Almanya'nın her yerinde ilan
        // var"). Kart rozetinin çözdüğü sorun burada çözülemediği için
        // örnekler haritaya hiç girmez.
        $query = Listing::query()->active()->gercek()
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->with(['category', 'country', 'coverImage']);

        if (in_array($type, ['hizmet', 'urun'], true)) {
            $query->where('type', $type);
        }

        $points = $query->latest()->limit(500)->get()->map(fn (Listing $l) => [
            'id' => $l->id,
            'title' => $l->title,
            'price' => $l->price !== null
                ? number_format((float) $l->price, 0, ',', '.').' '.$l->currency
                : 'Görüşülür',
            'type' => $l->type->value,
            'city' => $l->city,
            'country_code' => $l->country_code,
            'country_name' => $l->country?->name_tr,
            'category' => $l->category?->name,
            'image' => $l->coverImage?->enIyiUrl('thumb'),
            'lat' => (float) $l->latitude,
            'lng' => (float) $l->longitude,
            'url' => route('listings.show', [$l->id, $l->slug]),
        ]);

        return view('listings.map', [
            'points' => $points,
            'tip' => in_array($type, ['hizmet', 'urun'], true) ? $type : '',
        ]);
    }
}
