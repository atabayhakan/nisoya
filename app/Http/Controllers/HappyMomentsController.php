<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\View\View;

/**
 * /mutlu-anlar — ev sahiplerinin herkese açtığı etkinlik albümleri.
 * En güçlü organik tanıtım: "bu siteyle düğün yapılıyor" vitrini.
 */
class HappyMomentsController extends Controller
{
    public function index(): View
    {
        $events = Event::query()
            ->where('album_is_public', true)
            ->where('is_active', true)
            ->whereHas('media', fn ($m) => $m->where('status', 'yayinda'))
            ->withCount(['media as published_media_count' => fn ($m) => $m->where('status', 'yayinda')])
            ->with(['media' => fn ($m) => $m->where('status', 'yayinda')->where('type', 'image')->latest('id')->limit(1)])
            ->orderByDesc('starts_at')
            ->paginate(12);

        return view('happy-moments.index', compact('events'));
    }
}
