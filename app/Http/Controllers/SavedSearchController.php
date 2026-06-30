<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Country;
use App\Models\SavedSearch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SavedSearchController extends Controller
{
    public function index(Request $request): View
    {
        $searches = $request->user()->savedSearches()->latest()->get();

        return view('panel.saved-searches.index', compact('searches'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'kategori' => ['nullable', 'string', 'max:255'],
            'ulke' => ['nullable', 'string', 'size:2'],
            'sehir' => ['nullable', 'string', 'max:255'],
            'tip' => ['nullable', 'in:hizmet,urun'],
        ]);

        $defaults = ['q' => null, 'kategori' => null, 'ulke' => null, 'sehir' => null, 'tip' => null];
        $data = array_map(fn ($v) => ($v === '' ? null : $v), array_merge($defaults, $validated));

        if (! array_filter($data)) {
            return back()->with('status', 'Kaydetmek için en az bir filtre seçmelisin.');
        }

        $duplicate = $request->user()->savedSearches()
            ->where('q', $data['q'])->where('kategori', $data['kategori'])
            ->where('ulke', $data['ulke'])->where('sehir', $data['sehir'])->where('tip', $data['tip'])
            ->exists();

        if ($duplicate) {
            return redirect()->route('panel.saved-searches.index')->with('status', 'Bu arama zaten kayıtlı.');
        }

        $request->user()->savedSearches()->create($data + [
            'label' => $this->makeLabel($data),
            'last_notified_at' => now(),
        ]);

        return redirect()->route('panel.saved-searches.index')
            ->with('status', 'Arama kaydedildi. Uygun yeni ilanlar e-postana gelecek. 🔔');
    }

    public function destroy(Request $request, SavedSearch $savedSearch): RedirectResponse
    {
        abort_unless($savedSearch->user_id === $request->user()->id, 403);

        $savedSearch->delete();

        return back()->with('status', 'Kayıtlı arama silindi.');
    }

    protected function makeLabel(array $d): string
    {
        $parts = [];
        if ($d['q']) {
            $parts[] = '"'.$d['q'].'"';
        }
        if ($d['kategori'] && $cat = Category::query()->where('slug', $d['kategori'])->first()) {
            $parts[] = $cat->name;
        }
        if ($d['tip']) {
            $parts[] = $d['tip'] === 'urun' ? 'Ürün' : 'Hizmet';
        }
        if ($d['ulke'] && $co = Country::find($d['ulke'])) {
            $parts[] = $co->name_tr;
        }
        if ($d['sehir']) {
            $parts[] = $d['sehir'];
        }

        return $parts ? implode(' · ', $parts) : 'Tüm ilanlar';
    }
}
