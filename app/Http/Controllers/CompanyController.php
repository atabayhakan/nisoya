<?php

namespace App\Http\Controllers;

use App\Enums\AccountType;
use App\Models\Company;
use App\Models\Country;
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
        return view('panel.company.edit', [
            'company' => $request->user()->company,
            'countries' => Country::query()->where('is_active', true)->orderBy('sort_order')->get(),
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
            'sector' => ['nullable', 'string', 'max:100'],
            'company_size' => ['nullable', 'string', 'max:20'],
            'founded_year' => ['nullable', 'integer', 'min:1900', 'max:'.(int) date('Y')],
            'country_code' => ['nullable', 'exists:countries,code'],
            'city' => ['nullable', 'string', 'max:100'],
            'social_linkedin' => ['nullable', 'url', 'max:255'],
            'social_instagram' => ['nullable', 'string', 'max:255'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], attributes: [
            'name' => 'şirket adı', 'tagline' => 'slogan', 'about' => 'hakkında',
            'website' => 'web sitesi', 'sector' => 'sektör', 'founded_year' => 'kuruluş yılı',
            'country_code' => 'ülke', 'city' => 'şehir', 'logo' => 'logo',
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
        $company->load('country');

        $jobs = $company->jobListings()->active()
            ->latest()
            ->paginate(10);

        return view('companies.show', compact('company', 'jobs'));
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
