<?php

namespace App\Http\Controllers;

use App\Enums\JobStatus;
use App\Http\Requests\JobListingRequest;
use App\Models\Country;
use App\Models\Currency;
use App\Models\JobCategory;
use App\Models\JobListing;
use App\Services\GeocodingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\View\View;

class JobListingController extends Controller
{
    /** İşverenin kendi iş ilanları (panel). */
    public function index(Request $request): View
    {
        $company = $request->user()->company;

        $jobs = $company
            ? $company->jobListings()->withCount('applications')
                ->withExists(['featureRequests as has_pending_feature' => fn ($q) => $q->where('status', 'beklemede')])
                ->latest()->paginate(12)
            : collect();

        return view('panel.jobs.index', compact('jobs', 'company'));
    }

    public function create(Request $request): View|RedirectResponse
    {
        if (! $request->user()->company) {
            return redirect()->route('panel.company.edit')
                ->with('status', 'İş ilanı vermeden önce şirket profilini oluşturmalısın.');
        }

        return view('panel.jobs.create', $this->formData());
    }

    public function store(JobListingRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $coords = app(GeocodingService::class)->locate($data['city'] ?? null, $data['country_code'] ?? null);

        $job = $request->user()->company->jobListings()->create([
            'title' => $data['title'],
            'slug' => Str::slug($data['title']) ?: 'ilan',
            'description' => $data['description'],
            'job_category_id' => $data['job_category_id'] ?? null,
            'employment_type' => $data['employment_type'],
            'experience_level' => $data['experience_level'] ?? null,
            'salary_min' => $data['salary_min'] ?? null,
            'salary_max' => $data['salary_max'] ?? null,
            'salary_currency' => $data['salary_currency'] ?? null,
            'salary_period' => $data['salary_period'] ?? null,
            'country_code' => $data['country_code'] ?? null,
            'city' => $data['city'] ?? null,
            'latitude' => $coords['latitude'],
            'longitude' => $coords['longitude'],
            'is_remote' => $request->boolean('is_remote'),
            'deadline' => $data['deadline'] ?? null,
            'positions' => $data['positions'] ?? 1,
            'status' => JobStatus::Aktif->value,
        ]);

        return redirect()->route('panel.jobs.index')->with('status', 'İş ilanın yayınlandı! 🎉');
    }

    public function edit(JobListing $job): View
    {
        Gate::authorize('update', $job);

        return view('panel.jobs.edit', array_merge(['job' => $job], $this->formData()));
    }

    public function update(JobListingRequest $request, JobListing $job): RedirectResponse
    {
        Gate::authorize('update', $job);

        $data = $request->validated();
        $coords = app(GeocodingService::class)->locate($data['city'] ?? null, $data['country_code'] ?? null);

        $job->update([
            'title' => $data['title'],
            'slug' => Str::slug($data['title']) ?: 'ilan',
            'description' => $data['description'],
            'job_category_id' => $data['job_category_id'] ?? null,
            'employment_type' => $data['employment_type'],
            'experience_level' => $data['experience_level'] ?? null,
            'salary_min' => $data['salary_min'] ?? null,
            'salary_max' => $data['salary_max'] ?? null,
            'salary_currency' => $data['salary_currency'] ?? null,
            'salary_period' => $data['salary_period'] ?? null,
            'country_code' => $data['country_code'] ?? null,
            'city' => $data['city'] ?? null,
            'latitude' => $coords['latitude'],
            'longitude' => $coords['longitude'],
            'is_remote' => $request->boolean('is_remote'),
            'deadline' => $data['deadline'] ?? null,
            'positions' => $data['positions'] ?? 1,
        ]);

        return redirect()->route('panel.jobs.index')->with('status', 'İş ilanı güncellendi.');
    }

    /** İlanı kapat/aktifleştir/dolduruldu olarak işaretle. */
    public function updateStatus(Request $request, JobListing $job): RedirectResponse
    {
        Gate::authorize('update', $job);

        $request->validate(['status' => ['required', 'in:aktif,kapali,dolu']]);
        $job->update(['status' => $request->input('status')]);

        return back()->with('status', 'İlan durumu güncellendi.');
    }

    public function destroy(JobListing $job): RedirectResponse
    {
        Gate::authorize('delete', $job);

        $job->delete();

        return redirect()->route('panel.jobs.index')->with('status', 'İş ilanı silindi.');
    }

    /** Herkese açık iş ilanı detayı. */
    public function show(Request $request, JobListing $job, ?string $slug = null): View
    {
        $isOwner = $request->user()?->company?->id === $job->company_id;

        if ($job->status !== JobStatus::Aktif && ! $isOwner) {
            abort(404);
        }

        if (! $isOwner) {
            $job->increment('views_count');
        }

        $job->load(['company', 'category', 'country']);

        $hasApplied = $request->user()
            ? $job->applications()->where('user_id', $request->user()->id)->exists()
            : false;

        $isBookmarked = $request->user()
            ? $request->user()->jobBookmarks()->where('job_listing_id', $job->id)->exists()
            : false;

        return view('jobs.show', compact('job', 'isOwner', 'hasApplied', 'isBookmarked'));
    }

    /** Form için ortak veri. */
    protected function formData(): array
    {
        return [
            'categories' => JobCategory::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'currencies' => Currency::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'countries' => Country::query()->where('is_active', true)->orderBy('sort_order')->get(),
        ];
    }
}
