<?php

namespace App\Http\Controllers;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /** Herkese açık satıcı profili / vitrini. */
    public function show(User $user): View
    {
        abort_if($user->status === UserStatus::Silinmis, 404);

        $listings = $user->listings()
            ->active()
            ->with(['coverImage', 'category.parent', 'country', 'user'])
            ->latest()
            ->paginate(12);

        $reviews = $user->reviewsReceived()->where('status', 'yayinda')->with('reviewer')->latest()->get();
        $rating = [
            'avg' => round((float) $reviews->avg('rating'), 1),
            'count' => $reviews->count(),
        ];
        $myReview = auth()->check()
            ? $reviews->firstWhere('reviewer_id', auth()->id())
            : null;

        return view('profiles.show', compact('user', 'listings', 'reviews', 'rating', 'myReview'));
    }
}
