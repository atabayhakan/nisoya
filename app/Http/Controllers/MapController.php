<?php

namespace App\Http\Controllers;

use App\Models\Listing;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MapController extends Controller
{
    public function index(Request $request): View
    {
        $type = $request->string('tip')->toString();

        $query = Listing::query()->active()
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->with(['category', 'country']);

        if (in_array($type, ['hizmet', 'urun'], true)) {
            $query->where('type', $type);
        }

        $points = $query->latest()->limit(500)->get()->map(fn (Listing $l) => [
            'title' => $l->title,
            'price' => $l->price !== null
                ? number_format((float) $l->price, 0).' '.$l->currency
                : 'Görüşülür',
            'type' => $l->type->value,
            'city' => $l->city,
            'lat' => $l->latitude,
            'lng' => $l->longitude,
            'url' => route('listings.show', [$l->id, $l->slug]),
        ]);

        return view('listings.map', [
            'points' => $points,
            'tip' => in_array($type, ['hizmet', 'urun'], true) ? $type : '',
        ]);
    }
}
