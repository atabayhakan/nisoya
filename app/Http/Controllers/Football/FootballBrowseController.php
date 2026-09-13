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

        $totw = $this->getTotwCards($cityName);
        $viralFeed = $this->getViralFeed($cityName);

        return view('football.index', [
            'currentCity' => $cityName,
            'metrics' => $metrics,
            'standings' => $standings,
            'teams' => $teams,
            'venues' => $venues,
            'requests' => $requests,
            'cities' => $cities,
            'totw' => $totw,
            'viralFeed' => $viralFeed,
        ]);
    }

    /**
     * EA FC Ultimate Team Haftanın Halı Saha Karması (TOTW).
     *
     * @return array<int, array<string, mixed>>
     */
    protected function getTotwCards(string $cityName): array
    {
        return [
            [
                'name' => 'Emre K.',
                'team' => 'Kreuzberg Panterleri',
                'position' => 'FOR',
                'ovr' => 89,
                'pac' => 91,
                'sho' => 92,
                'pas' => 84,
                'dri' => 88,
                'def' => 45,
                'phy' => 83,
                'badge' => '🔥 Haftanın Golcüsü (5 Gol)',
                'card_type' => 'gold_totw',
            ],
            [
                'name' => 'Caner D.',
                'team' => 'FC Boğaziçi',
                'position' => 'OS',
                'ovr' => 87,
                'pac' => 82,
                'sho' => 84,
                'pas' => 93,
                'dri' => 89,
                'def' => 74,
                'phy' => 80,
                'badge' => '🎯 Asist Kralı (4 Asist)',
                'card_type' => 'gold_totw',
            ],
            [
                'name' => 'Burak T.',
                'team' => 'Hilal United',
                'position' => 'DEF',
                'ovr' => 86,
                'pac' => 79,
                'sho' => 65,
                'pas' => 78,
                'dri' => 75,
                'def' => 91,
                'phy' => 90,
                'badge' => '🛡️ Geçilmez Duvar',
                'card_type' => 'gold_totw',
            ],
            [
                'name' => 'Tolga S.',
                'team' => 'Anadolu Yıldızları',
                'position' => 'KL',
                'ovr' => 88,
                'pac' => 86,
                'sho' => 85,
                'pas' => 80,
                'dri' => 88,
                'def' => 50,
                'phy' => 87,
                'badge' => '🧤 Haftanın Kalecisi (14 Kurtarış)',
                'card_type' => 'gold_totw',
            ],
        ];
    }

    /**
     * Kings League & Baller League İlhamlı Viral Halı Saha Vitrini.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function getViralFeed(string $cityName): array
    {
        return [
            [
                'title' => '90+2 Doksana Takılan Akrep Vuruşu!',
                'team' => 'FC Boğaziçi Fırtınası',
                'author' => 'Kaptan Selim',
                'views' => '14.2K',
                'likes' => 842,
                'tag' => '🔥 Haftanın Golü',
                'tag_color' => 'amber',
                'summary' => 'Maçın son saniyelerinde sol kanattan gelen ortada arka direkte muazzam vole golü.',
            ],
            [
                'title' => 'Kings League Kurallarıyla 6v6 Derbi Nefes Kesti',
                'team' => 'Kreuzberg Panterleri vs Hilal United',
                'author' => 'Halı Saha Medya Ekibi',
                'views' => '22.8K',
                'likes' => '1.5K',
                'tag' => '⭐ Haftanın Maçı',
                'tag_color' => 'emerald',
                'summary' => 'Son 5 dakikada uygulanan 1v1 altın gol kuralıyla derbi unutulmaz anlara sahne oldu.',
            ],
            [
                'title' => '14 Kurtarış Yapan Kaleci Tek Başına Puanı Aldı',
                'team' => 'Anadolu Yıldızları',
                'author' => 'Kaptan Tolga',
                'views' => '9.8K',
                'likes' => 630,
                'tag' => '🧤 Maçın MVP\'si',
                'tag_color' => 'purple',
                'summary' => 'Üst üste 3 penaltı kurtaran kalecinin performansı sosyal medyada viral oldu.',
            ],
        ];
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
