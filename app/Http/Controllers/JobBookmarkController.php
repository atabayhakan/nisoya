<?php

namespace App\Http\Controllers;

use App\Models\JobListing;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class JobBookmarkController extends Controller
{
    public function index(Request $request): View
    {
        $jobs = JobListing::query()
            ->whereIn('id', $request->user()->jobBookmarks()->pluck('job_listing_id'))
            ->active()
            ->with(['company', 'category', 'country'])
            ->latest()
            ->paginate(12);

        return view('panel.job-bookmarks.index', compact('jobs'));
    }

    public function toggle(Request $request, JobListing $job): RedirectResponse
    {
        $existing = $request->user()->jobBookmarks()->where('job_listing_id', $job->id)->first();

        if ($existing) {
            $existing->delete();
            $message = 'İş ilanı yer imlerinden çıkarıldı.';
        } else {
            $request->user()->jobBookmarks()->create(['job_listing_id' => $job->id]);
            $message = 'İş ilanı yer imlerine eklendi. 🔖';
        }

        return back()->with('status', $message);
    }
}
