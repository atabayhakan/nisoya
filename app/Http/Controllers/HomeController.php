<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Country;
use App\Models\Listing;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        return view('home', [
            'categories' => Category::query()->whereNull('parent_id')->where('is_active', true)
                ->orderBy('sort_order')->get(),
            'countries' => Country::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'latestListings' => Listing::query()->active()
                ->with(['coverImage', 'category.parent', 'country', 'user'])
                ->latest()
                ->take(8)
                ->get(),
        ]);
    }
}
