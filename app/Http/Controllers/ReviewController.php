<?php

namespace App\Http\Controllers;

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

        $data = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ], attributes: ['rating' => 'puan', 'comment' => 'yorum']);

        $review = Review::updateOrCreate(
            ['reviewee_id' => $user->id, 'reviewer_id' => $reviewer->id, 'listing_id' => null],
            ['rating' => $data['rating'], 'comment' => $data['comment'] ?? null, 'status' => 'yayinda'],
        );

        if ($review->wasRecentlyCreated) {
            $user->notify(new NewReviewNotification($reviewer->name, $review->rating, route('profiles.show', $user->username)));
        }

        return back()->with('status', 'Değerlendirmen kaydedildi. Teşekkürler!');
    }
}
