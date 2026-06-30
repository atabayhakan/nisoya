<?php

namespace App\Http\Controllers;

use App\Models\Listing;
use App\Models\Report;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    /** Bir ilanı şikayet et (moderasyon kuyruğuna düşer). */
    public function store(Request $request, Listing $listing): RedirectResponse
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:1000'],
        ], attributes: ['reason' => 'sebep', 'note' => 'açıklama']);

        Report::create([
            'reporter_id' => $request->user()->id,
            'reportable_type' => 'listing',
            'reportable_id' => $listing->id,
            'reason' => $data['reason'],
            'note' => $data['note'] ?? null,
            'status' => 'acik',
        ]);

        return back()->with('status', 'Şikayetin alındı, teşekkürler. Ekibimiz inceleyecek.');
    }
}
