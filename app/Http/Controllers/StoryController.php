<?php

namespace App\Http\Controllers;

use App\Enums\StoryStatus;
use App\Services\NabizService;
use App\Services\ProfanityFilterService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * "Gurbet Günlüğü": kullanıcıların anonim olarak yayınlanan kısa
 * hikaye/deneyim paylaşımları. Gönderim isim/hesapla ilişkilendirilir
 * (moderasyon ve kötüye kullanım takibi için) ama onaylanan hikaye
 * herkese açık tarafta hiçbir kimlik bilgisi olmadan gösterilir
 * (bkz. NabizService::approvedStories()).
 */
class StoryController extends Controller
{
    public function store(Request $request, NabizService $nabiz): RedirectResponse
    {
        if (! $nabiz->storyWallEnabled()) {
            return back()->withErrors(['body' => 'Gurbet Günlüğü şu anda kapalı.']);
        }

        $user = $request->user();

        if ($user->stories()->where('status', StoryStatus::Beklemede)->exists()) {
            return back()->withErrors(['body' => 'Değerlendirilmekte olan bir hikayen zaten var. Sonucu belli olunca yeni bir hikaye paylaşabilirsin.']);
        }

        $data = $request->validate([
            'body' => ['required', 'string', 'min:20', 'max:600'],
        ], attributes: ['body' => 'hikaye']);

        // Hikaye metni de küfür filtresinden geçmeli (denetim #8).
        $profanityError = app(ProfanityFilterService::class)->validateText($data['body']);
        if ($profanityError) {
            return back()->withErrors(['body' => $profanityError])->withInput();
        }

        $user->stories()->create([
            'body' => $data['body'],
            'status' => StoryStatus::Beklemede,
        ]);

        return back()->with('status', 'Hikayen alındı! Onaylandıktan sonra tamamen anonim olarak yayınlanacak.');
    }
}
