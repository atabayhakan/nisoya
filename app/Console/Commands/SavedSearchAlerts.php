<?php

namespace App\Console\Commands;

use App\Models\SavedSearch;
use App\Notifications\SavedSearchAlert;
use Illuminate\Console\Command;

class SavedSearchAlerts extends Command
{
    protected $signature = 'alerts:saved-searches';

    protected $description = 'Kayıtlı aramalara uyan yeni ilanları e-postayla bildirir.';

    public function handle(): int
    {
        $notified = 0;

        SavedSearch::query()->with('user')->chunkById(100, function ($searches) use (&$notified) {
            foreach ($searches as $search) {
                if (! $search->user) {
                    continue;
                }

                $since = $search->last_notified_at ?? $search->created_at;

                $new = $search->matchingQuery()
                    ->where('created_at', '>', $since)
                    ->latest()
                    ->limit(10)
                    ->get();

                if ($new->isNotEmpty()) {
                    $search->user->notify(new SavedSearchAlert($search, $new));
                    $notified++;
                }

                $search->forceFill(['last_notified_at' => now()])->save();
            }
        });

        $this->info("{$notified} kullanıcıya uyarı gönderildi.");

        return self::SUCCESS;
    }
}
