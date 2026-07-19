<?php

namespace App\Http\Controllers;

use App\Enums\ReportCategory;
use App\Models\Listing;
use App\Models\Report;
use App\Models\User;
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
            // Tam sınıf adı saklanır (Laravel morph varsayılanı) — böylece admin
            // panelinde $report->reportable doğru çözülür.
            'reportable_type' => Listing::class,
            'reportable_id' => $listing->id,
            'reason' => $data['reason'],
            'category' => ReportCategory::Diger->value,
            'note' => $data['note'] ?? null,
            'status' => 'acik',
        ]);

        return back()->with('status', 'Şikayetin alındı, teşekkürler. Ekibimiz inceleyecek.');
    }

    /**
     * Bir kullanıcıyı DOLANDIRICILIK için bildir. Genel ilan şikayetinden ayrı
     * bir akış: hedef kullanıcıdır, kategori "dolandırıcılık"tır ve moderasyon
     * kuyruğunda önceliklidir. Kanıt (mesaj geçmişi) zaten sistemde.
     */
    public function reportFraud(Request $request, User $user): RedirectResponse
    {
        $reporter = $request->user();

        abort_if($reporter->id === $user->id, 403, 'Kendini bildiremezsin.');

        $data = $request->validate([
            'note' => ['required', 'string', 'min:10', 'max:1000'],
        ], attributes: ['note' => 'açıklama']);

        Report::create([
            'reporter_id' => $reporter->id,
            'reportable_type' => User::class,
            'reportable_id' => $user->id,
            'reason' => 'Dolandırıcılık şüphesi',
            'category' => ReportCategory::Dolandiricilik->value,
            'note' => $data['note'],
            'status' => 'acik',
        ]);

        return back()->with('status', 'Bildirimin alındı, ekibimiz en kısa sürede inceleyecek. Ödemeni henüz yapmadıysan güvende kalmak için acele etme.');
    }
}
