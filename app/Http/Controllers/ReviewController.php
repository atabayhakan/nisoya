<?php

namespace App\Http\Controllers;

use App\Enums\DealStatus;
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

        // Değerlendirme yalnızca gerçekten iletişime geçilmiş (dolayısıyla hizmet/alışveriş
        // süreci başlamış) kullanıcılar için bırakılabilir — platformda henüz sipariş/işlem
        // kaydı olmadığından mesajlaşma geçmişi bunun için elimizdeki en güvenilir sinyal.
        abort_unless(
            Conversation::existsBetween($reviewer->id, $user->id),
            403,
            'Bu kullanıcıyı değerlendirebilmek için önce kendisiyle iletişime geçmiş (mesajlaşmış) olman gerekir.'
        );

        $data = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ], attributes: ['rating' => 'puan', 'comment' => 'yorum']);

        // Taraflar arasında TAMAMLANMIŞ bir anlaşma varsa değerlendirmeye bağla
        // → "Doğrulanmış işlem" rozeti kazanır (K-C). Yoksa deal_id null kalır
        // (konuşma-bağlı değerlendirme; hâlâ geçerli, sadece rozetsiz).
        $completedDealId = Deal::query()
            ->where('status', DealStatus::Tamamlandi->value)
            ->where(function ($q) use ($reviewer, $user) {
                $q->where(['seller_id' => $reviewer->id, 'buyer_id' => $user->id])
                    ->orWhere(['seller_id' => $user->id, 'buyer_id' => $reviewer->id]);
            })
            ->latest('completed_at')
            ->value('id');

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
