<?php

namespace App\Http\Controllers\Football;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\FootballMatch;
use App\Models\FootballVenue;
use App\Models\FootballVenueReview;
use App\Services\ImageService;
use App\Services\ProfanityFilterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
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

        $userLat = $request->filled('lat') ? (float) $request->input('lat') : null;
        $userLng = $request->filled('lng') ? (float) $request->input('lng') : null;

        if ($userLat !== null && $userLng !== null) {
            $allVenues = $query->get();
            $sorted = $allVenues->sortBy(function (FootballVenue $v) use ($userLat, $userLng) {
                return $v->distanceFrom($userLat, $userLng) ?? 99999;
            })->values();

            $page = (int) $request->input('page', 1);
            $perPage = 12;
            $venues = new LengthAwarePaginator(
                $sorted->forPage($page, $perPage),
                $sorted->count(),
                $perPage,
                $page,
                ['path' => $request->url(), 'query' => $request->query()]
            );
        } else {
            $venues = $query->orderBy('rating', 'desc')->paginate(12)->withQueryString();
        }

        return view('football.venues.index', [
            'currentCity' => $cityName,
            'venues' => $venues,
            'userLat' => $userLat,
            'userLng' => $userLng,
            'featureOptions' => FootballVenue::FEATURE_OPTIONS,
            'pitchTypes' => FootballVenue::PITCH_TYPES,
            'surfaceTypes' => FootballVenue::SURFACE_TYPES,
        ]);
    }

    public function aiRecommend(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'city' => ['nullable', 'string', 'max:50'],
            'pitch_type' => ['nullable', 'string', 'in:kapali,acik,yari_acik'],
            'surface_type' => ['nullable', 'string', 'in:suni_cim,dogal_cim,parke,hali'],
            'features' => ['nullable', 'array'],
            'features.*' => ['string'],
            'lat' => ['nullable', 'numeric'],
            'lng' => ['nullable', 'numeric'],
        ]);

        $city = ! empty($validated['city']) ? trim($validated['city']) : null;
        $userLat = isset($validated['lat']) ? (float) $validated['lat'] : null;
        $userLng = isset($validated['lng']) ? (float) $validated['lng'] : null;

        $query = FootballVenue::query()->active();
        if ($city) {
            $query->city($city);
        }

        if (! empty($validated['pitch_type'])) {
            $query->where('pitch_type', $validated['pitch_type']);
        }
        if (! empty($validated['surface_type'])) {
            $query->where('surface_type', $validated['surface_type']);
        }

        $venues = $query->get();

        // Kriterlere, zemin puanına ve mesafeye göre puanla
        $desiredFeatures = $validated['features'] ?? [];
        $scored = $venues->map(function (FootballVenue $venue) use ($desiredFeatures, $userLat, $userLng) {
            $score = ($venue->rating ? (float) $venue->rating : 4.0) * 10;
            $featureMatches = 0;
            $venueFeatures = $venue->features ?: [];
            foreach ($desiredFeatures as $feat) {
                if (in_array($feat, $venueFeatures, true)) {
                    $score += 15;
                    $featureMatches++;
                }
            }

            $dist = $venue->distanceFrom($userLat, $userLng);
            if ($dist !== null) {
                if ($dist <= 5) {
                    $score += 20;
                } elseif ($dist <= 10) {
                    $score += 10;
                } elseif ($dist <= 20) {
                    $score += 5;
                }
            }

            return [
                'venue' => $venue,
                'score' => $score,
                'distance' => $dist,
                'feature_matches' => $featureMatches,
            ];
        })->sortByDesc('score')->take(3)->values();

        $comments = [
            0 => '🎯 Yapay zeka kriterlerinize ve maç temposuna en yüksek uyumu gösteren 1. öncelikli tesis!',
            1 => '⭐ Yüksek zemin kalitesi ve oyuncu memnuniyetiyle öne çıkan alternatif saha.',
            2 => '⚽ Harika lokasyon ve maç sonrası olanaklarıyla ideal halı saha tercihi.',
        ];

        $recommendations = $scored->map(function ($item, $index) use ($comments) {
            /** @var FootballVenue $v */
            $v = $item['venue'];
            $distText = $item['distance'] !== null ? "{$item['distance']} km mesafede" : null;

            $badges = [];
            if ($v->pitch_type === 'kapali') {
                $badges[] = '🌧️ Yağmur Geçirmez Kapalı';
            }
            if ($v->surface_type === 'suni_cim') {
                $badges[] = '⚡ Yeni Nesil Suni Çim';
            }
            if (in_array('otopark', $v->features ?: [], true)) {
                $badges[] = '🚗 Ücretsiz Otopark';
            }
            if (in_array('gece_aydinlatmasi', $v->features ?: [], true)) {
                $badges[] = '💡 HD Gece Aydınlatması';
            }

            return [
                'id' => $v->id,
                'name' => $v->name,
                'city' => $v->city,
                'address' => $v->address,
                'rating' => (string) $v->rating,
                'pitch_type_label' => FootballVenue::PITCH_TYPES[$v->pitch_type] ?? $v->pitch_type,
                'surface_type_label' => FootballVenue::SURFACE_TYPES[$v->surface_type] ?? $v->surface_type,
                'distance_text' => $distText,
                'badges' => $badges,
                'ai_comment' => $comments[$index] ?? 'Önerilen saha.',
                'google_maps_url' => $v->getGoogleMapsUrl(),
                'yandex_maps_url' => $v->getYandexMapsUrl(),
                'url' => route('football.venues.show', ['city' => Str::slug($v->city), 'venue' => $v->slug]),
            ];
        });

        return response()->json([
            'success' => true,
            'count' => $recommendations->count(),
            'recommendations' => $recommendations,
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
