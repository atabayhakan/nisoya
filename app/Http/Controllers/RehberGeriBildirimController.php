<?php

namespace App\Http\Controllers;

use App\Models\RehberGeriBildirimi;
use App\Models\TemsilcilikIslemi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * "Bu bilgi güncel mi?" geri bildirimi — anonim, oturum gerektirmez.
 *
 * Rehberi kullanan kişi çoğu zaman üye değildir (SEO'dan gelir); giriş
 * şartı koymak sinyali öldürür. Koruma bu yüzden kimlikte değil davranışta:
 * honeypot (bot) + rate limit (seri gönderim) + IP hash (desen görünürlüğü).
 */
class RehberGeriBildirimController extends Controller
{
    public function store(Request $request, TemsilcilikIslemi $islemKaydi): RedirectResponse
    {
        // Taslak kayda geri bildirim olmaz — o sayfa zaten görünmüyor.
        abort_unless($islemKaydi->yayindaMi(), 404);

        $data = $request->validate([
            'tur' => ['required', 'string', 'in:'.implode(',', array_keys(RehberGeriBildirimi::TURLER))],
            'metin' => ['nullable', 'string', 'max:1000'],
        ]);

        RehberGeriBildirimi::create([
            'temsilcilik_islemi_id' => $islemKaydi->id,
            'tur' => $data['tur'],
            'metin' => $data['metin'] ?? null,
            'ip_hash' => hash('sha256', (string) $request->ip()),
        ]);

        return back()->with('rehber_tesekkur', 'Teşekkürler — bildirimin incelenmek üzere kaydedildi.');
    }
}
