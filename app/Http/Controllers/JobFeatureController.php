<?php

namespace App\Http\Controllers;

use App\Models\JobListing;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class JobFeatureController extends Controller
{
    /** İşveren öne çıkarma talebi oluşturur (admin onayına düşer). */
    public function store(Request $request, JobListing $job): RedirectResponse
    {
        Gate::authorize('update', $job);

        $data = $request->validate([
            'days' => ['required', 'integer', 'in:7,30'],
        ], attributes: ['days' => 'süre']);

        if ($job->isCurrentlyFeatured()) {
            return back()->with('status', 'İlanın zaten öne çıkan.');
        }

        if ($job->featureRequests()->where('status', 'beklemede')->exists()) {
            return back()->with('status', 'Öne çıkarma talebin zaten inceleniyor.');
        }

        $job->featureRequests()->create([
            'user_id' => $request->user()->id,
            'days' => $data['days'],
            'status' => 'beklemede',
        ]);

        return back()->with('status', 'Öne çıkarma talebin alındı. Yönetici onayından sonra ilanın listelerin başında görünecek. ⭐');
    }
}
