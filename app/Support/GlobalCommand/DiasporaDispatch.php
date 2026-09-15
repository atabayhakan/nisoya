<?php

namespace App\Support\GlobalCommand;

use App\Enums\UserStatus;
use App\Jobs\GlobalCommand\SyncDiasporaAccount;
use App\Models\DiasporaAccount;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class DiasporaDispatch
{
    /** @param  Builder<DiasporaAccount>  $accounts */
    public function enqueue(User $actor, Builder $accounts): int
    {
        abort_unless($actor->isAdmin() && $actor->status === UserStatus::Aktif, 403);
        $connection = config('global-command.diaspora_sync_queue_connection', 'database');
        $cache = config('global-command.diaspora_sync_cache_store', 'database');
        if (! config('global-command.diaspora_sync_enabled') || ! config('services.rapidapi.key')) {
            throw ValidationException::withMessages(['sync' => 'Tarama entegrasyonu hazır değil. API anahtarını ve diaspora tarama ayarını kontrol edin.']);
        }
        if (! in_array(config("queue.connections.{$connection}.driver"), ['database', 'redis'], true)
            || ! in_array(config("cache.stores.{$cache}.driver"), ['database', 'redis'], true)) {
            throw ValidationException::withMessages(['sync' => 'Ortak database/Redis kuyruğu ve cache gerekli.']);
        }
        $count = 0;
        $accounts->where('is_active', true)->where('is_verified', true)->chunkById(100, function ($batch) use ($actor, $connection, &$count): void {
            foreach ($batch as $account) {
                SyncDiasporaAccount::dispatch($account->id, $actor->id, $account->country_code, $account->city)
                    ->onConnection($connection)->onQueue('diaspora-sync');
                $count++;
            }
        });

        return $count;
    }
}
