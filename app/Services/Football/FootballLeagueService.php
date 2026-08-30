<?php

namespace App\Services\Football;

use App\Enums\FootballMatchStatus;
use App\Enums\FootballResultStatus;
use App\Models\FootballMatch;
use App\Models\FootballTeam;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class FootballLeagueService
{
    /**
     * Şehir halı saha puan tablosunu döndürür.
     *
     * @return Collection<int, array{
     *     rank: int,
     *     team: FootballTeam,
     *     played: int,
     *     won: int,
     *     drawn: int,
     *     lost: int,
     *     goals_for: int,
     *     goals_against: int,
     *     goal_diff: int,
     *     points: int,
     *     form: array<int, string>
     * }>
     */
    public function getCityStandings(string $city): Collection
    {
        $city = trim($city);
        if ($city === '') {
            return collect();
        }

        $cacheKey = 'football_standings_'.md5(mb_strtolower($city));

        try {
            $cached = Cache::get($cacheKey);
            if ($cached instanceof Collection) {
                return $cached;
            }
            if ($cached !== null) {
                Cache::forget($cacheKey);
            }
        } catch (\Throwable) {
            Cache::forget($cacheKey);
        }

        $standingsResult = $this->calculateCityStandings($city);

        try {
            Cache::put($cacheKey, $standingsResult, now()->addMinutes(5));
        } catch (\Throwable) {
            // cache put error shouldn't crash page
        }

        return $standingsResult;
    }

    private function calculateCityStandings(string $city): Collection
    {
            $teams = FootballTeam::query()
                ->active()
                ->city($city)
                ->get();

            if ($teams->isEmpty()) {
                return collect();
            }

            // Doğrulanmış maçları çek
            $verifiedMatches = FootballMatch::query()
                ->where('status', FootballMatchStatus::Oynandi->value)
                ->where('result_status', FootballResultStatus::Dogrulandi->value)
                ->city($city)
                ->orderBy('match_date', 'desc')
                ->get();

            $standings = $teams->map(function (FootballTeam $team) use ($verifiedMatches) {
                $teamMatches = $verifiedMatches->filter(function (FootballMatch $m) use ($team) {
                    return (int) $m->home_team_id === (int) $team->id || (int) $m->away_team_id === (int) $team->id;
                });

                $played = $teamMatches->count();
                $won = 0;
                $drawn = 0;
                $lost = 0;
                $gf = 0;
                $ga = 0;
                $form = [];

                foreach ($teamMatches->take(5) as $match) {
                    $isHome = (int) $match->home_team_id === (int) $team->id;
                    $teamScore = $isHome ? (int) $match->home_score : (int) $match->away_score;
                    $oppScore = $isHome ? (int) $match->away_score : (int) $match->home_score;

                    if ($teamScore > $oppScore) {
                        $form[] = 'G'; // Galibiyet
                    } elseif ($teamScore === $oppScore) {
                        $form[] = 'B'; // Beraberlik
                    } else {
                        $form[] = 'M'; // Mağlubiyet
                    }
                }

                foreach ($teamMatches as $match) {
                    $isHome = (int) $match->home_team_id === (int) $team->id;
                    $teamScore = $isHome ? (int) $match->home_score : (int) $match->away_score;
                    $oppScore = $isHome ? (int) $match->away_score : (int) $match->home_score;

                    $gf += $teamScore;
                    $ga += $oppScore;

                    if ($teamScore > $oppScore) {
                        $won++;
                    } elseif ($teamScore === $oppScore) {
                        $drawn++;
                    } else {
                        $lost++;
                    }
                }

                $points = ($won * 3) + ($drawn * 1);
                $gd = $gf - $ga;

                return [
                    'team' => $team,
                    'played' => $played,
                    'won' => $won,
                    'drawn' => $drawn,
                    'lost' => $lost,
                    'goals_for' => $gf,
                    'goals_against' => $ga,
                    'goal_diff' => $gd,
                    'points' => $points,
                    'form' => array_reverse($form),
                ];
            });

            // Sıralama kuralları: Puan DESC, Averaj DESC, Atılan Gol DESC, Takım Adı ASC
            $sorted = $standings->sort(function ($a, $b) {
                if ($a['points'] !== $b['points']) {
                    return $b['points'] <=> $a['points'];
                }
                if ($a['goal_diff'] !== $b['goal_diff']) {
                    return $b['goal_diff'] <=> $a['goal_diff'];
                }
                if ($a['goals_for'] !== $b['goals_for']) {
                    return $b['goals_for'] <=> $a['goals_for'];
                }

                return strnatcasecmp($a['team']->name, $b['team']->name);
            })->values();

            return $sorted->map(function ($row, $index) {
                $row['rank'] = $index + 1;

                return $row;
            });
    }

    public function clearCityCache(string $city): void
    {
        Cache::forget('football_standings_'.md5(mb_strtolower(trim($city))));
    }
}
