<?php

namespace App\Http\Controllers\Football;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\FootballMatch;
use App\Models\FootballPlayerRequest;
use App\Models\FootballTeam;
use App\Models\FootballVenue;
use App\Services\Football\FootballLeagueService;
use App\Services\Football\FootballStatsService;
use App\Services\VisitorLocationService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FootballBrowseController extends Controller
{
    public function __construct(
        private readonly FootballStatsService $statsService,
        private readonly FootballLeagueService $leagueService,
    ) {}

    /**
     * Şehir parametresiz ana spor giriş noktası — ziyaretçinin şehrine yönlendirir veya genel hub'ı açar.
     */
    public function index(Request $request): View
    {
        $resolvedCity = $this->resolveCurrentCity($request);

        return $this->city($request, $resolvedCity);
    }

    /**
     * Belirli bir şehir için Futbol & Halı Saha Hub sayfası.
     */
    public function city(Request $request, string $city): View
    {
        $city = trim(str_replace('-', ' ', $city));
        $cityName = mb_convert_case($city, MB_CASE_TITLE, 'UTF-8');

        $currentCityModel = City::query()
            ->where('is_active', true)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($cityName, 'UTF-8')])
            ->first();

        $countryCode = $request->query('ulke')
            ?? $request->session()->get('visitor_country_code')
            ?? $request->user()?->country_code;

        if (! $countryCode) {
            $visitor = app(VisitorLocationService::class)->resolve($request);
            $countryCode = $visitor->code ?? null;
        }

        $activeCountryCode = $currentCityModel->country_code ?? ($countryCode ? strtoupper(substr((string) $countryCode, 0, 2)) : 'DE');

        $metrics = $this->statsService->getCityMetrics($cityName);
        $standings = $this->leagueService->getCityStandings($cityName)->take(5);

        $teams = FootballTeam::query()
            ->active()
            ->city($cityName)
            ->with(['captain', 'country'])
            ->withCount('activeMembers')
            ->orderBy('points', 'desc')
            ->take(6)
            ->get();

        $venues = FootballVenue::query()
            ->active()
            ->city($cityName)
            ->orderBy('rating', 'desc')
            ->take(4)
            ->get();

        $requests = FootballPlayerRequest::query()
            ->active()
            ->city($cityName)
            ->with(['user', 'team'])
            ->latest()
            ->take(6)
            ->get();

        $cities = City::query()
            ->with('country')
            ->where('is_active', true)
            ->orderByRaw('CASE WHEN country_code = ? THEN 0 ELSE 1 END', [$activeCountryCode])
            ->orderBy('sort_order')
            ->get();

        return view('football.index', [
            'currentCity' => $cityName,
            'metrics' => $metrics,
            'standings' => $standings,
            'teams' => $teams,
            'venues' => $venues,
            'requests' => $requests,
            'cities' => $cities,
        ]);
    }

    /**
     * Şehir Halı Saha Ligi puan tablosu sayfası.
     */
    public function league(Request $request, string $city): View
    {
        $city = trim(str_replace('-', ' ', $city));
        $cityName = mb_convert_case($city, MB_CASE_TITLE, 'UTF-8');

        $standings = $this->leagueService->getCityStandings($cityName);
        $metrics = $this->statsService->getCityMetrics($cityName);

        $recentMatches = FootballMatch::query()
            ->city($cityName)
            ->verified()
            ->with(['homeTeam', 'awayTeam', 'venue', 'mvpPlayer'])
            ->orderBy('match_date', 'desc')
            ->paginate(15);

        return view('football.league.index', [
            'currentCity' => $cityName,
            'standings' => $standings,
            'metrics' => $metrics,
            'recentMatches' => $recentMatches,
        ]);
    }

    protected function resolveCurrentCity(Request $request): string
    {
        if ($request->filled('city')) {
            $cityParam = trim(str_replace('-', ' ', (string) $request->input('city')));
            $found = City::query()
                ->where('is_active', true)
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($cityParam, 'UTF-8')])
                ->first();
            if ($found) {
                return $found->name;
            }
        }

        if ($request->user() && filled($request->user()->city)) {
            $userCity = City::query()
                ->where('is_active', true)
                ->whereRaw('LOWER(name) = ?', [mb_strtolower((string) $request->user()->city, 'UTF-8')])
                ->value('name');

            return $userCity ?? (string) $request->user()->city;
        }

        $countryCode = $request->query('ulke')
            ?? $request->session()->get('visitor_country_code')
            ?? $request->user()?->country_code;

        if (! $countryCode) {
            $visitor = app(VisitorLocationService::class)->resolve($request);
            $countryCode = $visitor->code ?? null;
        }

        if ($countryCode) {
            $code = strtoupper(substr((string) $countryCode, 0, 2));
            $cityInCountry = City::query()
                ->whereHas('country', fn ($q) => $q->where('code', $code))
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->value('name');

            if ($cityInCountry) {
                return $cityInCountry;
            }
        }

        return City::query()->where('is_active', true)->orderBy('sort_order')->value('name') ?? 'Berlin';
    }
}
