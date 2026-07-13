<?php

namespace App\Http\Controllers;

use App\Models\Listing;
use App\Models\ListingUnavailableRange;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * İlan sahibinin müsaitlik takvimi yönetimi: dolu tarih aralığı ekle/sil.
 * (Emlak kısa dönem; Faz E2'de kiralık araçlar da aynı uçları kullanacak.)
 */
class ListingAvailabilityController extends Controller
{
    public function store(Request $request, Listing $listing): RedirectResponse
    {
        Gate::authorize('update', $listing);

        $data = $request->validate([
            'starts_on' => ['required', 'date'],
            'ends_on' => ['required', 'date', 'after_or_equal:starts_on'],
            'note' => ['nullable', 'string', 'max:255'],
        ], [], [
            'starts_on' => 'başlangıç tarihi',
            'ends_on' => 'bitiş tarihi',
            'note' => 'not',
        ]);

        $listing->unavailableRanges()->create($data);

        return back()->with('status', 'Tarih aralığı dolu olarak işaretlendi.');
    }

    public function destroy(Listing $listing, ListingUnavailableRange $range): RedirectResponse
    {
        Gate::authorize('update', $listing);

        abort_unless($range->listing_id === $listing->id, 404);

        $range->delete();

        return back()->with('status', 'Tarih aralığı takvimden kaldırıldı.');
    }
}
