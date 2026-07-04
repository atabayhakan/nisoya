<?php

namespace App\Http\Controllers;

use App\Enums\EmploymentType;
use App\Models\Country;
use App\Models\JobCategory;
use App\Models\JobListing;
use Illuminate\Http\Request;
use Illuminate\View\View;

class JobBrowseController extends Controller
{
    public function index(Request $request): View
    {
        $query = JobListing::query()->active()
            ->with(['company', 'category', 'country']);

        // Anahtar kelime (başlık + açıklama + şirket adı)
        if ($q = trim((string) $request->query('q'))) {
            $query->where(function ($sub) use ($q) {
                $sub->where('title', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%")
                    ->orWhereHas('company', fn ($c) => $c->where('name', 'like', "%{$q}%"));
            });
        }

        if ($cat = $request->query('kategori')) {
            $query->whereHas('category', fn ($c) => $c->where('slug', $cat));
        }

        if ($type = $request->query('tip')) {
            if (in_array($type, array_column(EmploymentType::cases(), 'value'), true)) {
                $query->where('employment_type', $type);
            }
        }

        if ($country = $request->query('ulke')) {
            $query->where('country_code', $country);
        }

        if ($request->boolean('uzaktan')) {
            $query->where('is_remote', true);
        }

        // Sıralama: öne çıkanlar önce, sonra en yeni
        $jobs = $query
            ->orderByDesc('is_featured')
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('jobs.index', [
            'jobs' => $jobs,
            'categories' => JobCategory::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'countries' => Country::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'employmentTypes' => EmploymentType::cases(),
            'filters' => $request->only(['q', 'kategori', 'tip', 'ulke', 'uzaktan']),
        ]);
    }
}
