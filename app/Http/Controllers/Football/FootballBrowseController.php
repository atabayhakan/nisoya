<?php

namespace App\Http\Controllers\Football;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Country;
use App\Models\FootballMatch;
use App\Models\FootballPlayerProfile;
use App\Models\FootballPlayerRequest;
use App\Models\FootballTeam;
use App\Models\FootballVenue;
use App\Services\Football\FootballLeagueService;
use App\Services\Football\FootballStatsService;
use App\Services\VisitorLocationService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
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
            ->where('is_active', true)
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
        if ($request->user() && filled($request->user()->city)) {
            return $request->user()->city;
        }

        $visitor = app(VisitorLocationService::class)->resolve($request);
        if ($visitor && $visitor->code === 'DE') {
            return 'Berlin';
        }

        return 'Berlin';
    }
}
