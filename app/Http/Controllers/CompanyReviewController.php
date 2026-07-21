<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\CompanyReview;
use App\Models\JobApplication;
use App\Notifications\NewCompanyReviewNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CompanyReviewController extends Controller
{
    public function store(Request $request, Company $company): RedirectResponse
    {
        $reviewer = $request->user();

        abort_if($reviewer->id === $company->user_id, 403, 'Kendi şirketini değerlendiremezsin.');

        abort_unless(
            JobApplication::query()
                ->where('user_id', $reviewer->id)
                ->whereHas('jobListing', fn ($q) => $q->where('company_id', $company->id))
                ->exists(),
            403,
            'Bu şirketi değerlendirebilmek için önce bir iş ilanına başvurmuş olman gerekir.'
        );

        $data = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ], attributes: ['rating' => 'puan', 'comment' => 'yorum']);

        if (! empty($data['comment'])) {
            $profanityError = app(\App\Services\ProfanityFilterService::class)->validateText($data['comment']);
            if ($profanityError) {
                return back()->withErrors(['comment' => $profanityError])->withInput();
            }
        }

        $review = CompanyReview::updateOrCreate(
            ['company_id' => $company->id, 'reviewer_id' => $reviewer->id],
            ['rating' => $data['rating'], 'comment' => $data['comment'] ?? null, 'status' => 'yayinda'],
        );

        if ($review->wasRecentlyCreated && $company->user) {
            $company->user->notify(new NewCompanyReviewNotification($reviewer->name, $review->rating, route('companies.show', $company)));
        }

        return back()->with('status', 'Değerlendirmen kaydedildi. Teşekkürler!');
    }
}
