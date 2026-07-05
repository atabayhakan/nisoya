<?php

namespace App\Http\Controllers;

use App\Enums\AccountType;
use App\Enums\UserStatus;
use App\Models\Country;
use App\Models\JobCategory;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CandidateController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::query()
            ->where('is_searchable', true)
            ->where('status', UserStatus::Aktif->value)
            ->where('account_type', AccountType::Bireysel->value)
            ->with('jobCategory');

        // Anahtar kelime (ad + hakkında + yetenekler)
        if ($q = trim((string) $request->query('q'))) {
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('bio', 'like', "%{$q}%")
                    ->orWhere('skills', 'like', "%{$q}%");
            });
        }

        if ($cat = $request->query('kategori')) {
            $query->whereHas('jobCategory', fn ($c) => $c->where('slug', $cat));
        }

        if ($country = $request->query('ulke')) {
            $query->where('country_code', $country);
        }

        if ($city = trim((string) $request->query('sehir'))) {
            $query->where('city', 'like', "%{$city}%");
        }

        $candidates = $query
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('candidates.index', [
            'candidates' => $candidates,
            'categories' => JobCategory::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'countries' => Country::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'filters' => $request->only(['q', 'kategori', 'ulke', 'sehir']),
        ]);
    }
}
