<?php

namespace App\Http\Controllers;

use App\Enums\AccountType;
use App\Models\Company;
use App\Models\Country;
use App\Models\JobApplication;
use App\Services\ImageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CompanyController extends Controller
{
    /** Şirket profili oluşturma/düzenleme formu (panel). */
    public function edit(Request $request): View
    {
        $company = $request->user()->company;

        return view('panel.company.edit', [
            'company' => $company,
            'countries' => Country::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'galleryImages' => $company ? $company->galleryImages : collect(),
        ]);
    }

    /** Şirket profilini kaydet (oluştur veya güncelle). İlk kayıtta hesap kurumsala geçer. */
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'tagline' => ['nullable', 'string', 'max:180'],
            'about' => ['nullable', 'string', 'max:5000'],
            'website' => ['nullable', 'url', 'max:255'],
            'video_url' => ['nullable', 'url', 'max:255'],
            'sector' => ['nullable', 'string', 'max:100'],
            'company_size' => ['nullable', 'string', 'max:20'],
            'founded_year' => ['nullable', 'integer', 'min:1900', 'max:'.(int) date('Y')],
            'country_code' => ['nullable', 'exists:countries,code'],
            'city' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:255'],
            'social_linkedin' => ['nullable', 'url', 'max:255'],
            'social_instagram' => ['nullable', 'string', 'max:255'],
            'social_whatsapp' => ['nullable', 'string', 'max:255', 'regex:/^(https?:\/\/)?(www\.)?(wa\.me|api\.whatsapp\.com)\/.+$/i'],
            'social_twitter' => ['nullable', 'url', 'max:255'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], messages: [
            'social_whatsapp.regex' => 'WhatsApp bağlantısı wa.me veya api.whatsapp.com adresiyle başlamalı.',
        ], attributes: [
            'name' => 'şirket adı', 'tagline' => 'slogan', 'about' => 'hakkında',
            'website' => 'web sitesi', 'video_url' => 'tanıtım videosu', 'sector' => 'sektör', 'founded_year' => 'kuruluş yılı',
            'country_code' => 'ülke', 'city' => 'şehir', 'address' => 'adres', 'logo' => 'logo',
            'social_whatsapp' => 'WhatsApp', 'social_twitter' => 'Twitter/X',
        ]);

        $company = $user->company;

        if ($request->hasFile('logo')) {
            $imageService = app(ImageService::class);
            try {
                $result = $imageService->storeOptimized($request->file('logo'), 'company-logos', 400, 85);
            } catch (\RuntimeException) {
                return back()->withErrors(['logo' => 'Logo işlenemedi, lütfen başka bir dosyayla dene.']);
            }
            if ($company?->logo_path) {
                $imageService->deleteVariants(array_values($imageService->siblingVariantPaths($company->logo_path)));
            }
            $data['logo_path'] = $result['large'];
        }
        unset($data['logo']);

        if ($company) {
            $company->update($data);
        } else {
            $data['slug'] = $this->uniqueSlug($data['name']);
            $user->company()->create($data);
            // İlk şirket profili → hesap kurumsala geçer (iş ilanı verebilir).
            $user->update(['account_type' => AccountType::Kurumsal]);
        }

        return redirect()->route('panel.company.edit')->with('status', 'Şirket profilin kaydedildi.');
    }

    /** Herkese açık şirket sayfası. */
    public function show(Company $company): View
    {
        $company->load('country', 'galleryImages');

        $jobs = $company->jobListings()->active()
            ->latest()
            ->paginate(10);

        $reviews = $company->reviews()->where('status', 'yayinda')->with('reviewer')->latest()->get();
        $rating = [
            'avg' => round((float) $reviews->avg('rating'), 1),
            'count' => $reviews->count(),
        ];
        $myReview = auth()->check()
            ? $reviews->firstWhere('reviewer_id', auth()->id())
            : null;
        // Değerlendirme yalnızca bu şirkete daha önce başvurmuş adaylar için
        // açılır — bkz. CompanyReviewController::store().
        $canReview = auth()->check()
            && auth()->id() !== $company->user_id
            && JobApplication::query()
                ->where('user_id', auth()->id())
                ->whereHas('jobListing', fn ($q) => $q->where('company_id', $company->id))
                ->exists();

        return view('companies.show', compact('company', 'jobs', 'reviews', 'rating', 'myReview', 'canReview'));
    }

    protected function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'sirket';
        $slug = $base;
        $i = 1;
        while (Company::where('slug', $slug)->exists()) {
            $slug = $base.'-'.(++$i);
        }

        return $slug;
    }
}
