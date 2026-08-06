<?php

namespace App\Http\Controllers;

use App\Enums\ListingStatus;
use App\Enums\UserStatus;
use App\Models\Conversation;
use App\Models\Deal;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /** Herkese açık satıcı profili / vitrini. */
    public function show(Request $request, User $user): View
    {
        abort_if($user->status === UserStatus::Silinmis, 404);

        $user->load('paymentLinks', 'portfolioItems', 'jobCategory');

        /*
         * GÜNCEL / GEÇMİŞ SEKMESİ (2026-08-06)
         *
         * Sahibin isteği: ilan detayından satıcının hem güncel hem eski
         * ilanlarına gidilebilsin. İki liste için iki ayrı sayfa açmak yerine
         * profil sayfasına bir süzgeç eklendi — değerlendirmeler, güven
         * rozetleri ve ödeme yöntemleri zaten burada; satıcıyı iki ayrı
         * sayfaya bölmek onu tanımayı zorlaştırırdı.
         *
         * Beyaz liste ile okunur: bilinmeyen her değer "güncel"e düşer, yani
         * `?durum=` ile durum sızdırmak mümkün değil.
         */
        $durum = $request->query('durum') === 'gecmis' ? 'gecmis' : 'guncel';

        // Tek sorguda iki sayı — bkz. ListingController::show() içindeki not.
        $sayilar = $user->listings()
            ->selectRaw(
                'SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as guncel, '
                .'SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as gecmis',
                [ListingStatus::Aktif->value, ListingStatus::Pasif->value],
            )
            ->toBase()
            ->first();

        $ilanSayilari = [
            'guncel' => (int) ($sayilar->guncel ?? 0),
            'gecmis' => (int) ($sayilar->gecmis ?? 0),
        ];

        $listings = $user->listings()
            ->when(
                $durum === 'gecmis',
                fn ($q) => $q->where('status', ListingStatus::Pasif->value),
                fn ($q) => $q->active(),
            )
            ->with(['coverImage', 'category.parent', 'country', 'user'])
            ->latest()
            // Sekme değişince sayfa numarası taşınmasın diye sorgu dizesinde
            // korunur (bkz. aşağıdaki `withQueryString`).
            ->paginate(12)
            ->withQueryString();

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
        // Değerlendirme kapısı ReviewController::store() ile BİREBİR aynı olmak
        // zorunda — form burada görünüp orada 403 yerse kullanıcı haklı olarak
        // "bozuk" der: iki tarafın da yazdığı konuşma YA DA tamamlanmış anlaşma.
        $canReview = auth()->check()
            && auth()->id() !== $user->id
            && (Deal::latestCompletedIdBetween(auth()->id(), $user->id) !== null
                || Conversation::mutualExistsBetween(auth()->id(), $user->id));

        return view('profiles.show', compact(
            'user', 'listings', 'reviews', 'rating', 'myReview', 'canReview', 'durum', 'ilanSayilari'
        ));
    }
}
