<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\City;
use App\Models\Country;
use App\Models\Listing;
use App\Services\NabizService;
use App\Support\CategoryIcon;
use Illuminate\Support\Str;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(NabizService $nabiz): View
    {
        return view('home', [
            'categories' => Category::query()->whereNull('parent_id')->where('is_active', true)
                ->orderBy('sort_order')->get(),
            'countries' => Country::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'stats' => [
                'countries' => Country::query()->where('is_active', true)->count(),
                'categories' => Category::query()->where('is_active', true)->count(),
                'cities' => City::query()->count(),
            ],
            'latestListings' => Listing::query()->active()
                ->with(['coverImage', 'category.parent', 'country', 'user'])
                ->latest()
                ->take(8)
                ->get(),
            'activityFeed' => Listing::query()->active()
                ->with(['category.parent', 'country', 'user'])
                ->latest()
                ->take(10)
                ->get()
                ->map(fn (Listing $listing) => [
                    'firstName' => Str::before($listing->user->name, ' '),
                    'categoryName' => $listing->category->name,
                    'categoryIcon' => CategoryIcon::heroicon($listing->category?->parent?->icon ?? $listing->category?->icon),
                    'flag' => $listing->country?->emoji,
                    'place' => $listing->city ?: $listing->country?->name_tr,
                    'timeAgo' => $listing->created_at->diffForHumans(),
                    'href' => route('listings.show', [$listing, $listing->slug]),
                ]),
            'nabizGoal' => $nabiz->goalProgress(),
            'nabizAmbassadors' => $nabiz->cityAmbassadors(3),
        ]);
    }
}
