<?php

namespace App\Http\Controllers;

use App\Enums\EmploymentType;
use App\Models\Country;
use App\Models\JobCategory;
use App\Models\JobSavedSearch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class JobSavedSearchController extends Controller
{
    public function index(Request $request): View
    {
        $searches = $request->user()->jobSavedSearches()->latest()->get();

        return view('panel.job-saved-searches.index', compact('searches'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'kategori' => ['nullable', 'string', 'max:255'],
            'ulke' => ['nullable', 'string', 'size:2'],
            'tip' => ['nullable', 'in:'.implode(',', array_column(EmploymentType::cases(), 'value'))],
            'uzaktan' => ['nullable', 'boolean'],
        ]);

        $defaults = ['q' => null, 'kategori' => null, 'ulke' => null, 'tip' => null, 'uzaktan' => null];
        $data = array_map(fn ($v) => ($v === '' ? null : $v), array_merge($defaults, $validated));
        $data['uzaktan'] = $request->boolean('uzaktan') ?: null;

        if (! array_filter($data)) {
            return back()->with('status', 'Kaydetmek için en az bir filtre seçmelisin.');
        }

        $duplicate = $request->user()->jobSavedSearches()
            ->where('q', $data['q'])->where('kategori', $data['kategori'])
            ->where('ulke', $data['ulke'])->where('tip', $data['tip'])->where('uzaktan', $data['uzaktan'])
            ->exists();

        if ($duplicate) {
            return redirect()->route('panel.job-saved-searches.index')->with('status', 'Bu arama zaten kayıtlı.');
        }

        $request->user()->jobSavedSearches()->create($data + [
            'label' => $this->makeLabel($data),
            'last_notified_at' => now(),
        ]);

        return redirect()->route('panel.job-saved-searches.index')
            ->with('status', 'Arama kaydedildi. Uygun yeni iş ilanları e-postana gelecek. 🔔');
    }

    public function destroy(Request $request, JobSavedSearch $jobSavedSearch): RedirectResponse
    {
        abort_unless($jobSavedSearch->user_id === $request->user()->id, 403);

        $jobSavedSearch->delete();

        return back()->with('status', 'Kayıtlı arama silindi.');
    }

    protected function makeLabel(array $d): string
    {
        $parts = [];
        if ($d['q']) {
            $parts[] = '"'.$d['q'].'"';
        }
        if ($d['kategori'] && $cat = JobCategory::query()->where('slug', $d['kategori'])->first()) {
            $parts[] = $cat->name;
        }
        if ($d['tip'] && $type = EmploymentType::tryFrom($d['tip'])) {
            $parts[] = $type->getLabel();
        }
        if ($d['ulke'] && $co = Country::find($d['ulke'])) {
            $parts[] = $co->name_tr;
        }
        if ($d['uzaktan']) {
            $parts[] = 'Uzaktan';
        }

        return $parts ? implode(' · ', $parts) : 'Tüm iş ilanları';
    }
}
