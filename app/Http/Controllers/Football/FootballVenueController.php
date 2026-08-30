<?php

namespace App\Http\Controllers\Football;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\FootballMatch;
use App\Models\FootballVenue;
use App\Models\FootballVenueReview;
use App\Services\ImageService;
use App\Services\ProfanityFilterService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class FootballVenueController extends Controller
{
    public function __construct(
        private readonly ImageService $imageService,
        private readonly ProfanityFilterService $profanityFilter,
    ) {}

    public function index(Request $request, string $city): View
    {
        $city = trim(str_replace('-', ' ', $city));
        $cityName = mb_convert_case($city, MB_CASE_TITLE, 'UTF-8');

        $query = FootballVenue::query()
            ->active()
            ->city($cityName);

        if ($request->filled('q')) {
            $q = $request->string('q');
            $query->where(function ($b) use ($q) {
                $b->where('name', 'like', "%{$q}%")
                    ->orWhere('address', 'like', "%{$q}%");
            });
        }

        if ($request->filled('pitch_type')) {
            $query->where('pitch_type', $request->string('pitch_type'));
        }

        if ($request->filled('surface_type')) {
            $query->where('surface_type', $request->string('surface_type'));
        }

        $venues = $query->orderBy('rating', 'desc')->paginate(12)->withQueryString();

        return view('football.venues.index', [
            'currentCity' => $cityName,
            'venues' => $venues,
        ]);
    }

    public function show(string $city, FootballVenue $venue): View
    {
        $venue->load(['creator', 'country', 'publishedReviews.user']);

        $recentMatches = FootballMatch::query()
            ->verified()
            ->where('venue_id', $venue->id)
            ->with(['homeTeam', 'awayTeam'])
            ->latest('match_date')
            ->take(5)
            ->get();

        $userReview = auth()->check()
            ? FootballVenueReview::where('venue_id', $venue->id)->where('user_id', auth()->id())->first()
            : null;

        return view('football.venues.show', [
            'currentCity' => $venue->city,
            'venue' => $venue,
            'recentMatches' => $recentMatches,
            'userReview' => $userReview,
        ]);
    }

    public function create(Request $request): View
    {
        $countries = Country::query()->where('is_active', true)->orderBy('sort_order')->get();
        $userCity = $request->user()->city ?: 'Berlin';
        $userCountry = $request->user()->country_code ?: 'DE';

        return view('football.venues.create', [
            'countries' => $countries,
            'defaultCity' => $userCity,
            'defaultCountry' => $userCountry,
            'features' => FootballVenue::FEATURE_OPTIONS,
            'pitchTypes' => FootballVenue::PITCH_TYPES,
            'surfaceTypes' => FootballVenue::SURFACE_TYPES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:80'],
            'city' => ['required', 'string', 'max:50'],
            'country_code' => ['required', 'string', 'size:2', 'exists:countries,code'],
            'address' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'website' => ['nullable', 'url', 'max:255'],
            'pitch_type' => ['required', 'string', 'in:kapali,acik,yari_acik'],
            'surface_type' => ['required', 'string', 'in:suni_cim,dogal_cim,parke,hali'],
            'features' => ['nullable', 'array'],
            'opening_hours' => ['nullable', 'string', 'max:100'],
            'price_info' => ['nullable', 'string', 'max:100'],
            'cover_image' => ['nullable', 'image', 'max:4096'],
        ]);

        if ($this->profanityFilter->hasProfanity($validated['name'])) {
            return back()->withInput()->withErrors(['name' => 'Saha ismi uygunsuz kelimeler içeremez.']);
        }

        $coverPath = null;
        if ($request->hasFile('cover_image')) {
            $processed = $this->imageService->storeOptimized($request->file('cover_image'), 'football/venues');
            $coverPath = $processed['medium'] ?? ($processed['thumb'] ?? null);
        }

        $venue = FootballVenue::create([
            'created_by_id' => $request->user()->id,
            'name' => $validated['name'],
            'city' => mb_convert_case(trim($validated['city']), MB_CASE_TITLE, 'UTF-8'),
            'country_code' => strtoupper($validated['country_code']),
            'address' => $validated['address'],
            'phone' => $validated['phone'] ?? null,
            'website' => $validated['website'] ?? null,
            'pitch_type' => $validated['pitch_type'],
            'surface_type' => $validated['surface_type'],
            'features' => $validated['features'] ?? [],
            'opening_hours' => $validated['opening_hours'] ?? null,
            'price_info' => $validated['price_info'] ?? null,
            'cover_image_path' => $coverPath,
            'is_active' => true,
        ]);

        return to_route('football.venues.show', ['city' => Str::slug($venue->city), 'venue' => $venue->slug])
            ->with('status', 'Halı saha başarıyla eklendi.');
    }

    public function storeReview(Request $request, FootballVenue $venue): RedirectResponse
    {
        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'saha_kalitesi' => ['nullable', 'integer', 'min:1', 'max:5'],
            'temizlik' => ['nullable', 'integer', 'min:1', 'max:5'],
            'dus_soyunma' => ['nullable', 'integer', 'min:1', 'max:5'],
            'fiyat_performans' => ['nullable', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $subRatings = array_filter([
            'saha_kalitesi' => $validated['saha_kalitesi'] ?? null,
            'temizlik' => $validated['temizlik'] ?? null,
            'dus_soyunma' => $validated['dus_soyunma'] ?? null,
            'fiyat_performans' => $validated['fiyat_performans'] ?? null,
        ]);

        FootballVenueReview::updateOrCreate(
            ['venue_id' => $venue->id, 'user_id' => $request->user()->id],
            [
                'rating' => $validated['rating'],
                'sub_ratings' => $subRatings ?: null,
                'comment' => $validated['comment'] ?? null,
                'status' => 'yayinda',
            ]
        );

        return back()->with('status', 'Halı saha değerlendirmeniz kaydedildi.');
    }
}
