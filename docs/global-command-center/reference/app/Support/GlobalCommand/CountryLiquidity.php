<?php

namespace App\Support\GlobalCommand;

use App\Enums\UserStatus;
use App\Models\DiasporaAccount;
use App\Models\DiasporaReel;
use App\Models\JobListing;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

final class CountryLiquidity
{
    public function snapshot(GeoContext $context): array
    {
        $version = config('global-command.liquidity_version', 'supply-v1');

        return Cache::remember('gcc:'.$version.':'.$context->key(),
            (int) config('global-command.metrics_ttl_seconds', 60), function () use ($context): array {
                // One aggregate per source, independent of the number of countries.
                $counts = [
                    'listings' => $this->counts($context->apply(Listing::query()->active()->gercek())),
                    'jobs' => $this->counts($context->apply(JobListing::query()->active()
                        ->where(fn ($q) => $q->whereNull('deadline')->orWhereDate('deadline', '>=', today())))),
                    'users' => $this->counts($context->apply(User::query()->gercek()->where('status', UserStatus::Aktif->value))),
                    'accounts' => $this->counts($context->apply(DiasporaAccount::query()->active()->verified())),
                    'reels' => $this->counts($context->apply(DiasporaReel::query()->active())),
                ];
                $languages = DB::table('geo_country_language')->join('geo_languages', 'language_tag', '=', 'tag')
                    ->orderByDesc('is_default')->get(['country_code', 'name_tr'])->groupBy('country_code');
                $rows = $context->countries()->orderBy('name_tr')->get(['code', 'name_tr', 'default_currency'])
                    ->map(function ($country) use ($counts, $languages): array {
                        $metrics = [];
                        foreach ($counts as $name => $values) {
                            $metrics[$name] = (int) ($values[$country->code] ?? 0);
                        }

                        return [
                            'code' => $country->code, 'name' => $country->name_tr,
                            'currency' => $country->default_currency,
                            'languages' => ($languages[$country->code] ?? collect())->pluck('name_tr')->all(),
                            ...$metrics, ...LiquidityScore::calculate($metrics['listings'], $metrics['jobs'], $metrics['users'], $metrics['accounts']),
                        ];
                    })->all();

                return ['measured_at' => now()->toIso8601String(), 'rows' => $rows];
            });
    }

    private function counts(Builder $query): array
    {
        return $query->reorder()->select('country_code')->selectRaw('COUNT(*) AS aggregate')
            ->groupBy('country_code')->pluck('aggregate', 'country_code')->all();
    }
}
