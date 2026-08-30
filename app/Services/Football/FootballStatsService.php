<?php

namespace App\Services\Football;

use App\Enums\FootballMatchStatus;
use App\Enums\FootballMemberStatus;
use App\Enums\FootballResultStatus;
use App\Models\FootballMatch;
use App\Models\FootballPlayerProfile;
use App\Models\FootballPlayerRequest;
use App\Models\FootballTeam;
use App\Models\FootballTeamMember;
use App\Models\FootballVenue;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class FootballStatsService
{
    public function __construct(private readonly FootballLeagueService $leagueService) {}

    /**
     * Şehir için genel spor topluluğu göstergelerini döndürür.
     *
     * @return array{
     *     teams_count: int,
     *     players_count: int,
     *     venues_count: int,
     *     weekly_matches_count: int,
     *     verified_matches_count: int,
     *     open_requests_count: int,
     *     recent_matches: Collection<int, FootballMatch>,
     *     featured_match: ?FootballMatch
     * }
     */
    public function getCityMetrics(string $city): array
    {
        $city = trim($city);
        $cacheKey = 'football_city_metrics_'.md5(mb_strtolower($city));

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($city) {
            $weekStart = Carbon::now()->startOfWeek();
            $weekEnd = Carbon::now()->endOfWeek();

            $teamsCount = FootballTeam::query()->active()->city($city)->count();

            $playersCount = FootballTeamMember::query()
                ->where('status', FootballMemberStatus::Aktif->value)
                ->whereHas('team', fn ($q) => $q->where('is_active', true)->whereRaw('LOWER(city) = ?', [mb_strtolower(trim($city))]))
                ->distinct('user_id')
                ->count('user_id');

            // Eğer takım üyesi yoksa bireysel futbol profillerini de say
            if ($playersCount === 0) {
                $playersCount = FootballPlayerProfile::query()->city($city)->count();
            }

            $venuesCount = FootballVenue::query()->active()->city($city)->count();

            $weeklyMatchesCount = FootballMatch::query()
                ->city($city)
                ->whereBetween('match_date', [$weekStart, $weekEnd])
                ->count();

            $verifiedMatchesCount = FootballMatch::query()
                ->city($city)
                ->where('result_status', FootballResultStatus::Dogrulandi->value)
                ->count();

            $openRequestsCount = FootballPlayerRequest::query()
                ->active()
                ->city($city)
                ->count();

            $recentMatches = FootballMatch::query()
                ->city($city)
                ->where('result_status', FootballResultStatus::Dogrulandi->value)
                ->with(['homeTeam', 'awayTeam', 'venue', 'mvpPlayer'])
                ->orderBy('match_date', 'desc')
                ->take(6)
                ->get();

            $featuredMatch = FootballMatch::query()
                ->city($city)
                ->where('is_featured', true)
                ->where('result_status', FootballResultStatus::Dogrulandi->value)
                ->with(['homeTeam', 'awayTeam', 'venue', 'mvpPlayer'])
                ->latest('match_date')
                ->first();

            if (! $featuredMatch && $recentMatches->isNotEmpty()) {
                $featuredMatch = $recentMatches->first();
            }

            return [
                'teams_count' => $teamsCount,
                'players_count' => $playersCount,
                'venues_count' => $venuesCount,
                'weekly_matches_count' => $weeklyMatchesCount,
                'verified_matches_count' => $verifiedMatchesCount,
                'open_requests_count' => $openRequestsCount,
                'recent_matches' => $recentMatches,
                'featured_match' => $featuredMatch,
            ];
        });
    }

    /**
     * Doğrulanmış bir maçın sonuçlarını takım ve oyuncu istatistiklerine işler.
     */
    public function applyVerifiedMatchStats(FootballMatch $match): void
    {
        if ($match->result_status !== FootballResultStatus::Dogrulandi) {
            return;
        }

        DB::transaction(function () use ($match) {
            // Ev Sahibi Takım
            $homeTeam = $match->homeTeam;
            if ($homeTeam) {
                $this->recalculateTeamStats($homeTeam);
            }

            // Deplasman Takımı
            $awayTeam = $match->awayTeam;
            if ($awayTeam) {
                $this->recalculateTeamStats($awayTeam);
            }

            // Gol atan oyuncuların istatistiklerini güncelle
            if (! empty($match->home_scorers) && is_array($match->home_scorers)) {
                foreach ($match->home_scorers as $scorer) {
                    if (! empty($scorer['user_id']) && ! empty($scorer['goals'])) {
                        $profile = FootballPlayerProfile::firstOrCreate(['user_id' => $scorer['user_id']]);
                        $profile->increment('goals', (int) $scorer['goals']);
                    }
                }
            }

            if (! empty($match->away_scorers) && is_array($match->away_scorers)) {
                foreach ($match->away_scorers as $scorer) {
                    if (! empty($scorer['user_id']) && ! empty($scorer['goals'])) {
                        $profile = FootballPlayerProfile::firstOrCreate(['user_id' => $scorer['user_id']]);
                        $profile->increment('goals', (int) $scorer['goals']);
                    }
                }
            }

            $this->leagueService->clearCityCache($match->city);
            Cache::forget('football_city_metrics_'.md5(mb_strtolower($match->city)));
            Cache::forget('football_home_sports_data');
        });
    }

    /**
     * Takımın tüm doğrulanmış maçlarından istatistikleri sıfırdan toplar ve günceller.
     */
    public function recalculateTeamStats(FootballTeam $team): void
    {
        $matches = FootballMatch::query()
            ->where('status', FootballMatchStatus::Oynandi->value)
            ->where('result_status', FootballResultStatus::Dogrulandi->value)
            ->where(function ($q) use ($team) {
                $q->where('home_team_id', $team->id)
                    ->orWhere('away_team_id', $team->id);
            })
            ->get();

        $played = $matches->count();
        $wins = 0;
        $draws = 0;
        $losses = 0;
        $gf = 0;
        $ga = 0;

        foreach ($matches as $m) {
            $isHome = (int) $m->home_team_id === (int) $team->id;
            $teamScore = $isHome ? (int) $m->home_score : (int) $m->away_score;
            $oppScore = $isHome ? (int) $m->away_score : (int) $m->home_score;

            $gf += $teamScore;
            $ga += $oppScore;

            if ($teamScore > $oppScore) {
                $wins++;
            } elseif ($teamScore === $oppScore) {
                $draws++;
            } else {
                $losses++;
            }
        }

        $points = ($wins * 3) + ($draws * 1);

        $team->update([
            'matches_count' => $played,
            'wins_count' => $wins,
            'draws_count' => $draws,
            'losses_count' => $losses,
            'goals_for' => $gf,
            'goals_against' => $ga,
            'points' => $points,
        ]);
    }
}
