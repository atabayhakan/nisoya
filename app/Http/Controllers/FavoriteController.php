<?php

namespace App\Http\Controllers;

use App\Models\Listing;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FavoriteController extends Controller
{
    public function index(Request $request): View
    {
        $listings = Listing::query()
            ->whereIn('id', $request->user()->favorites()->pluck('listing_id'))
            ->active()
            ->with(['coverImage', 'category.parent', 'country', 'user'])
            ->latest()
            ->paginate(12);

        return view('panel.favorites.index', compact('listings'));
    }

    public function toggle(Request $request, Listing $listing): RedirectResponse
    {
        $existing = $request->user()->favorites()->where('listing_id', $listing->id)->first();

        if ($existing) {
            $existing->delete();
            $message = 'İlan favorilerden çıkarıldı.';
        } else {
            $request->user()->favorites()->create(['listing_id' => $listing->id]);
            $message = 'İlan favorilere eklendi. ❤️';
        }

        return back()->with('status', $message);
    }
}
