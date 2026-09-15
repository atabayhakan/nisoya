<?php

namespace App\Console\Commands;

use App\Support\GlobalCommand\CountryLiquidity;
use App\Support\GlobalCommand\GeoContext;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PlanColdStart extends Command
{
    protected $signature = 'global-command:plan-cold-start';

    protected $description = 'Boş pazarlar için haftalık, tekrarsız iç büyüme görevleri hazırlar.';

    public function handle(CountryLiquidity $liquidity): int
    {
        if (! config('global-command.enabled')) {
            $this->info('Küresel operasyon merkezi kapalı.');

            return self::SUCCESS;
        }
        $created = 0;
        foreach ($liquidity->snapshot(GeoContext::global())['rows'] as $row) {
            if ($row['stage'] !== 'cold_start') {
                continue;
            }
            $created += DB::table('global_growth_tasks')->insertOrIgnore([
                'country_code' => $row['code'],
                'action_key' => $row['accounts'] === 0 ? 'discover_community' : 'draft_local_campaign',
                'period_start' => now('UTC')->startOfWeek()->toDateString(),
                'recommendation' => $row['next_action'], 'status' => 'suggested',
                'metric_version' => config('global-command.liquidity_version', 'supply-v1'),
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        $this->info($created.' iç görev oluşturuldu. Dış mesaj gönderilmedi.');

        return self::SUCCESS;
    }
}
