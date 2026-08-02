<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Deal;
use App\Models\Review;
use App\Models\User;
use App\Notifications\NewReviewNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    /** Bir satıcıyı (kullanıcıyı) değerlendir. */
    public function store(Request $request, User $user): RedirectResponse
    {
        $reviewer = $request->user();

        abort_if($reviewer->id === $user->id, 403, 'Kendini değerlendiremezsin.');

        // Tamamlanmış anlaşma hem kapıyı açar hem değerlendirmeye bağlanır
        // ("Doğrulanmış işlem" rozeti, K-C) — bu yüzden kapıdan ÖNCE bakılır.
        $completedDealId = Deal::latestCompletedIdBetween($reviewer->id, $user->id);

        /*
         * DEĞERLENDİRME KAPISI: puan, gerçekleşmiş bir etkileşimin beyanıdır.
         * Eski kapı "aramızda bir konuşma kaydı var" idi ve TEK YÖNLÜ tek
         * mesajla açılıyordu — beş taze hesap, beş "merhaba" ve beş yıldızla
         * rozet basılabilirdi. Şimdi: ya İKİ TARAFIN DA yazdığı bir konuşma
         * ya da tamamlanmış bir anlaşma gerekir.
         */
        abort_unless(
            $completedDealId !== null || Conversation::mutualExistsBetween($reviewer->id, $user->id),
            403,
            'Bu kullanıcıyı değerlendirebilmek için aranızda iki tarafın da yazdığı bir konuşma ya da tamamlanmış bir anlaşma olması gerekir.'
        );

        $data = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ], attributes: ['rating' => 'puan', 'comment' => 'yorum']);

        $review = Review::updateOrCreate(
            ['reviewee_id' => $user->id, 'reviewer_id' => $reviewer->id, 'listing_id' => null],
            ['rating' => $data['rating'], 'comment' => $data['comment'] ?? null, 'status' => 'yayinda', 'deal_id' => $completedDealId],
        );

        if ($review->wasRecentlyCreated) {
            $user->notify(new NewReviewNotification($reviewer->name, $review->rating, route('profiles.show', $user->username)));
        }

        return back()->with('status', 'Değerlendirmen kaydedildi. Teşekkürler!');
    }
}
