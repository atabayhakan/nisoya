<?php

namespace App\Console\Commands;

use App\Models\JobSavedSearch;
use App\Notifications\JobSavedSearchAlert;
use Illuminate\Console\Command;

class JobSavedSearchAlerts extends Command
{
    protected $signature = 'job-alerts:saved-searches';

    protected $description = 'Kayıtlı iş aramalarına uyan yeni ilanları e-postayla bildirir.';

    public function handle(): int
    {
        $notified = 0;

        JobSavedSearch::query()->with('user')->chunkById(100, function ($searches) use (&$notified) {
            foreach ($searches as $search) {
                if (! $search->user) {
                    continue;
                }

                $since = $search->last_notified_at ?? $search->created_at;

                $new = $search->matchingQuery()
                    ->with('company')
                    ->where('created_at', '>', $since)
                    ->latest()
                    ->limit(10)
                    ->get();

                if ($new->isNotEmpty()) {
                    $search->user->notify(new JobSavedSearchAlert($search, $new));
                    $notified++;
                }

                $search->forceFill(['last_notified_at' => now()])->save();
            }
        });

        $this->info("{$notified} kullanıcıya iş uyarısı gönderildi.");

        return self::SUCCESS;
    }
}
