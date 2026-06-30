<?php

namespace App\Http\Controllers;

use App\Models\Listing;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class FeatureController extends Controller
{
    /** İlan sahibi öne çıkarma talebi oluşturur (admin onayına düşer). */
    public function store(Request $request, Listing $listing): RedirectResponse
    {
        Gate::authorize('update', $listing);

        $data = $request->validate([
            'days' => ['required', 'integer', 'in:7,30'],
        ], attributes: ['days' => 'süre']);

        if ($listing->isCurrentlyFeatured()) {
            return back()->with('status', 'İlanın zaten öne çıkan.');
        }

        if ($listing->featureRequests()->where('status', 'beklemede')->exists()) {
            return back()->with('status', 'Öne çıkarma talebin zaten inceleniyor.');
        }

        $listing->featureRequests()->create([
            'user_id' => $request->user()->id,
            'days' => $data['days'],
            'status' => 'beklemede',
        ]);

        return back()->with('status', 'Öne çıkarma talebin alındı. Yönetici onayından sonra ilanın listelerin başında görünecek. ⭐');
    }
}
