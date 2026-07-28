<?php

namespace App\Http\Controllers;

use App\Enums\UserStatus;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /** Herkese açık satıcı profili / vitrini. */
    public function show(User $user): View
    {
        abort_if($user->status === UserStatus::Silinmis, 404);

        $user->load('paymentLinks', 'portfolioItems', 'jobCategory');

        $listings = $user->listings()
            ->active()
            ->with(['coverImage', 'category.parent', 'country', 'user'])
            ->latest()
            ->paginate(12);

        // DEĞERLENDİRMELER SAYFALANIR — ama üç şey AYRI sorgudan gelmek zorunda.
        //
        // Önceden hepsi sınırsız `->get()` ile tek koleksiyondan türetiliyordu:
        // ortalama, toplam sayı ve "kullanıcının kendi yorumu". İlanlar zaten
        // 12'şer sayfalanıyordu, değerlendirmeler sayfalanmıyordu — 200
        // değerlendirmesi olan bir profil hepsini birden çekiyordu.
        //
        // Düz `paginate()` eklemek İKİ SESSİZ BOZULMA üretirdi:
        //   1. avg/count yalnız GÖRÜNEN sayfadan hesaplanır → 4.8 olan puan
        //      ikinci sayfada bambaşka çıkar. Rozet eşikleri buna bakıyor.
        //   2. `firstWhere('reviewer_id', ...)` yalnız görünen sayfada arar →
        //      kullanıcının kendi yorumu 2. sayfadaysa form "Güncelle" yerine
        //      "Gönder" moduna düşer ve ikinci bir kayıt denemesi yapılır.
        //
        // Bu yüzden ortalama/sayı ayrı bir toplama sorgusundan, kendi yorumu da
        // ayrı bir sorgudan geliyor (User::trustProfile aynı deseni kullanıyor).
        $yayindaki = $user->reviewsReceived()->where('status', 'yayinda');

        $reviews = (clone $yayindaki)
            ->with('reviewer')
            ->latest()
            // Paginator ADLANDIRILDI: adlandırılmazsa ilanlarla aynı `page`
            // parametresini paylaşır ve yorum sayfasını çevirmek ilan listesini
            // de kaydırır (ilanlar varsayılan `page`'i kullanıyor).
            ->paginate(10, ['*'], 'yorum');

        $ozet = (clone $yayindaki)
            ->selectRaw('COUNT(*) as adet, AVG(rating) as ortalama')
            ->first();

        $rating = [
            'avg' => round((float) ($ozet->ortalama ?? 0), 1),
            'count' => (int) ($ozet->adet ?? 0),
        ];

        $myReview = auth()->check()
            ? (clone $yayindaki)->where('reviewer_id', auth()->id())->first()
            : null;
        // Değerlendirme yalnızca daha önce iletişime geçmiş (mesajlaşmış) kullanıcılar
        // için açılır — bkz. ReviewController::store().
        $canReview = auth()->check()
            && auth()->id() !== $user->id
            && Conversation::existsBetween(auth()->id(), $user->id);

        return view('profiles.show', compact('user', 'listings', 'reviews', 'rating', 'myReview', 'canReview'));
    }
}
